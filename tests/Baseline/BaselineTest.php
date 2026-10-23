<?php

namespace Tests\Baseline;

use App\Models\Admin;
use App\Models\Faculty;
use App\Models\Simulation;
use App\Models\Student;
use App\Services\MailjetService;
use App\Services\Reports\BalanceSheetService;
use App\Services\Reports\CashFlowService;
use App\Services\Reports\CorporatePnlService;
use App\Services\Reports\FlexScorecardService;
use App\Services\Reports\ProductPnlService;
use Carbon\Carbon;
use Dotenv\Dotenv;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * The rebuild against the live code's recorded figures (scm_baseline_v1.json).
 *
 * Plays the same fixed course the recording played on the live code: admin creates it, faculty adds
 * three students (one per team) and publishes (warm-up Q0-Q3), teams 2 and 3
 * change decisions for Q4, and faculty closes Q4 with the close button.
 * Every figure is compared with scm_baseline_v1.json. On a difference, the
 * new figures are written to storage/baseline-actual.json and the test lists
 * the first differences.
 *
 * Run it on its own. It uses MySQL with the host, user and password in
 * .env, and its own database (BASELINE_DB_DATABASE, default scm_baseline),
 * which it creates and rebuilds each run:
 *
 *     php artisan test --group=baseline
 *
 * Determinism: mt_srand() before each step, Carbon::setTestNow() for the clock,
 * a fresh database each run, outbound HTTP faked, Mailjet faked.
 */
#[Group('baseline')]
class BaselineTest extends TestCase
{
    private const TZ = 'America/New_York';

    private array $log = [];

    /** Reference-data seeders only (no accounts, no Faker students). */
    private const SEEDERS = [
        'IndustrySeeder', 'FirmSeeder', 'RegionSeeder', 'DemandChannelSeeder', 'PackagingSeeder',
        'SupplyChannelSeeder', 'DecisionSeeder', 'IndustryInitialDemandSeeder', 'RawMaterialSeeder',
        'RawMaterialCostSeeder', 'RawMaterialVolumeDiscountSeeder', 'RawMaterialSafetyStockSeeder',
        'SubAssemblyComponentSeeder', 'SubAssemblyTransportationCostSeeder', 'SubAssemblyVolumeDiscountSeeder',
        'ServiceOutsourcingSeeder', 'ServiceOutsourcingCostSeeder', 'CarrierSeeder', 'CarrierChannelSeeder',
        'CarrierCrossDockingSeeder', 'CarrierCostSeeder', 'ManufacturingCostSeeder',
        'ManufacturingCostAdditionalSeeder', 'AiPromptSeeder', 'ConsultingFeeSeeder', 'SupplierSeeder',
        'SupplierComponentSeeder', 'ProductSeeder', 'ProductMaterialSeeder',
    ];

    /** Columns that are random or clock-of-the-machine by design, left out of the record. */
    private const EXCLUDED_COLUMNS = [
        'id', 'created_at', 'updated_at', 'password', 'password_generated', 'remember_token', 'invite_token', 'token',
        'share_code', 'simulation_code', 'student_code', 'faculty_code', 'join_code',
    ];

    public function test_the_rebuild_reproduces_the_live_figures(): void
    {
        $started = microtime(true);
        $out = storage_path('baseline-actual.json');

        $this->freshDatabase();

        $mail = new FakeMailjetService;
        $this->app->instance(MailjetService::class, $mail);
        Http::fake(['*' => Http::response('', 200)]);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        // ---- Accounts (fixed ids 1) ----
        $this->clockAt('2026-01-05 09:00:00');
        mt_srand(1000);
        $admin = Admin::forceCreate(['first_name' => 'Base', 'last_name' => 'Admin', 'email' => 'admin@baseline.test',
            'password' => Hash::make('x'), 'role' => 'super-admin']);
        $faculty = Faculty::forceCreate(['first_name' => 'Base', 'last_name' => 'Faculty', 'email' => 'faculty@baseline.test',
            'password' => Hash::make('x'), 'faculty_code' => '10000001']);

        // ---- 1. Admin creates the course: 2 playable quarters, 3 teams, 7-day quarters ----
        $this->asAdmin($admin);
        mt_srand(1001);
        $this->step('admin creates course', $this->post('/admin/simulation', [
            'title' => 'Baseline course', 'organization' => 'Baseline', 'start_date' => '01-12-2026',
            'end_date' => '02-28-2026', 'quarters' => 2, 'firms' => 3, 'quarter_duration' => 7,
            'timezone' => self::TZ,
        ]));
        $sim = Simulation::firstOrFail();
        $this->step('admin manage quarter page', $this->get("/admin/courses/{$sim->id}/manage-quarter"));

        // ---- 2. Admin generates initial demand (GDP 3%) ----
        mt_srand(1002);
        $this->step('admin generates initial demand', $this->post("/admin/courses/{$sim->id}/generate-initial-demand", [
            'simulation_id' => $sim->id, 'gdp_percentage' => 3,
        ]));

        // ---- 3. Admin assigns the faculty and deploys to faculty ----
        $this->step('admin saves faculty', $this->post("/admin/courses/{$sim->id}/save-faculty", [
            'simulation_id' => $sim->id, 'faculty' => $faculty->id,
        ]));
        $this->step('admin deploys to faculty', $this->post("/admin/courses/{$sim->id}/deploy-simulation", [
            'simulation_id' => $sim->id, 'fee' => 100, 'licence_count' => 3,
        ]));

        // ---- 4. Faculty adds three students and puts one in each team ----
        $this->clockAt('2026-01-07 10:00:00');
        $this->asFaculty($faculty);
        mt_srand(1003);
        foreach ([1, 2, 3] as $n) {
            $this->step("faculty adds student $n", $this->post("/faculty/courses/{$sim->id}/store-student", [
                'simulation_id' => $sim->id, 'first_name' => 'Student', 'last_name' => "Team$n",
                'email' => "student$n@baseline.test",
            ]));
        }
        foreach ([1, 2, 3] as $n) {
            $student = Student::where('email', "student$n@baseline.test")->firstOrFail();
            $this->step("faculty assigns student $n to team $n", $this->post("/faculty/courses/{$sim->id}/groups/assign-student", [
                'course_id' => $sim->id, 'group_number' => $n, 'student_id' => $student->id,
            ]));
        }

        // ---- 5. Faculty publishes: warm-up Q0-Q3 runs for each team ----
        $this->clockAt('2026-01-08 10:00:00');
        mt_srand(1004);
        $this->step('faculty publishes (warm-up)', $this->get("/faculty/courses/{$sim->id}/deploy-simulation"));

        // ---- 6. Students decide for Q4 (open 12-18 Jan) ----
        $this->clockAt('2026-01-14 10:00:00');
        mt_srand(1005);
        // Team 1 submits nothing (carry-forward).
        // Team 2: forecast +10% and procurement +10% on Q3's figures.
        $this->asStudent(Student::where('email', 'student2@baseline.test')->first());
        $this->step('team 2 saves forecasting', $this->post("/student/simulation/{$sim->id}/quarter/4/save-forecasting",
            $this->forecastPayload($sim->id, 2, 1.10)));
        foreach ($this->procurementPayloads($sim->id, 2, 1.10) as $regionId => $payload) {
            $this->step("team 2 saves procurement region $regionId",
                $this->post("/student/simulation/{$sim->id}/quarter/4/save-procurement", $payload));
        }
        // Team 3: forecast -10% on Q3's figures.
        $this->asStudent(Student::where('email', 'student3@baseline.test')->first());
        $this->step('team 3 saves forecasting', $this->post("/student/simulation/{$sim->id}/quarter/4/save-forecasting",
            $this->forecastPayload($sim->id, 3, 0.90)));

        // ---- 7. Faculty closes Q4 with the close button ----
        $this->clockAt('2026-01-14 15:00:00');
        $this->asFaculty($faculty);
        mt_srand(1006);
        $this->step('faculty closes quarter 4', $this->get("/faculty/courses/{$sim->id}/quarter-close/4"));

        // ---- 8. Record ----
        $this->clockAt('2026-01-14 16:00:00');
        mt_srand(1007);
        $record = [
            'meta' => [
                'baseline' => 'scm_baseline_v1',
                'code' => 'Laravel 13 rebuild',
                'close_path' => 'faculty close button: GET /faculty/courses/{id}/quarter-close/4 (quartersClosed)',
                'timezone' => self::TZ,
                'steps' => $this->log,
                'emails_suppressed' => array_map(fn ($m) => $m['subject'], $mail->sent),
                'http_calls_stubbed' => collect(Http::recorded())->map(fn ($p) => $p[0]->method().' '.preg_replace('/\?.*/', '', $p[0]->url()))->countBy()->sortKeys()->all(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
            'simulation' => $this->clean((array) DB::table('simulations')->where('id', $sim->id)->first()),
            'tables' => $this->dumpTables($sim->id),
            'reports' => $this->reports($sim->id, $faculty->id),
        ];

        $record = $this->sortDeep($record);
        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)."\n";
        file_put_contents($out, $json);

        $expected = json_decode(file_get_contents(__DIR__.'/scm_baseline_v1.json'), true);
        $actual = json_decode($json, true);

        // These describe the run, not the figures.
        foreach (['baseline', 'code'] as $k) {
            unset($expected['meta'][$k], $actual['meta'][$k]);
        }

        $expected = $this->withIntendedChanges($expected);

        $differences = $this->differences($expected, $actual);
        fwrite(STDERR, sprintf("\nBaseline compared in %.1fs: %d differences\n", microtime(true) - $started, count($differences)));

        $this->assertSame([], array_slice($differences, 0, 40), "The rebuild's figures differ from the live recording. Full output: $out");

        // After the figures: every page renders for the person it's for.
        $broken = $this->pagesThatFail($sim->id, $admin, $faculty, Student::where('email', 'student2@baseline.test')->first());
        $this->assertSame([], $broken, 'These pages fail with a server error.');
    }

    /**
     * Opens every GET page of the app as the person it's for (admin pages as
     * the admin, faculty pages as the faculty member, student pages as Team
     * 2's student), with the recorded course's ids, and lists any that give
     * a server error. Pages that change something (close, deploy, remove,
     * sign out, mimic) and pages that need a token or a file are left out.
     */
    private function pagesThatFail(int $simId, Admin $admin, Faculty $faculty, Student $student): array
    {
        $values = [
            'simulation_id' => $simId, 'course_id' => $simId, 'courses_id' => $simId, 'simulation' => $simId,
            'quarter_id' => 4, 'group_id' => 2, 'student_id' => $student->id, 'faculty_id' => $faculty->id,
            'role' => 'faculty',
        ];
        $skip = '/logout|remove|close|deploy|delete|mimic|import-students|clear|toggle|reset|run-|checkout|success|cancel|download|export/i';

        $broken = [];
        foreach (app('router')->getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true) || preg_match($skip, $route->uri())) {
                continue;
            }

            $uri = $route->uri();
            preg_match_all('/\{(\w+)\??\}/', $uri, $m);
            if (array_diff($m[1], array_keys($values))) {
                continue;
            }
            foreach ($m[1] as $p) {
                $uri = preg_replace('/\{'.$p.'\??\}/', (string) $values[$p], $uri);
            }

            match (true) {
                str_starts_with($uri, 'admin') => $this->asAdmin($admin),
                str_starts_with($uri, 'faculty') => $this->asFaculty($faculty),
                str_starts_with($uri, 'student') => $this->asStudent($student),
                default => $this->flushSession(),
            };

            $response = $this->get('/'.ltrim($uri, '/'));
            if ($response->getStatusCode() >= 500) {
                $e = $response->exception;
                $broken[] = "/$uri: ".($e ? class_basename($e).': '.$e->getMessage() : $response->getStatusCode());
            }
        }

        return $broken;
    }

    /**
     * The rebuild's deliberate differences from the live code, applied to the
     * recording before comparing. Each one is a decision, not a figure. Add to
     * this list only with a reason; everything else must match exactly.
     */
    private function withIntendedChanges(array $expected): array
    {
        $steps = &$expected['meta']['steps'];

        foreach ($steps as $i => $step) {
            // Stage 1: new students get a random password and a link to set
            // their own, instead of a shared password written in the code.
            if (str_starts_with($step['step'], 'faculty adds student')) {
                $steps[$i]['success'] = 'Student added. We’ve emailed them a link to set their password.';
                $added[] = $i;
            }
        }
        unset($steps);

        // ...so each of the three students gets a "Set your FLEXEE password" email,
        // sent as they're added (after the faculty welcome, before the quarter closes).
        $emails = $expected['meta']['emails_suppressed'];
        array_splice($emails, 1, 0, array_fill(0, count($added ?? []), 'Set your FLEXEE password'));
        $expected['meta']['emails_suppressed'] = $emails;

        return $expected;
    }

    /** Paths where two records differ, as "path: expected → actual". */
    private function differences($expected, $actual, string $path = ''): array
    {
        if (is_array($expected) && is_array($actual)) {
            $out = [];
            foreach (array_unique(array_merge(array_keys($expected), array_keys($actual))) as $k) {
                $p = $path === '' ? (string) $k : "$path.$k";
                if (! array_key_exists($k, $expected)) {
                    $out[] = "$p: (missing) → ".json_encode($actual[$k]);
                } elseif (! array_key_exists($k, $actual)) {
                    $out[] = "$p: ".json_encode($expected[$k]).' → (missing)';
                } else {
                    array_push($out, ...$this->differences($expected[$k], $actual[$k], $p));
                }
            }

            return $out;
        }

        return $expected === $actual ? [] : ["$path: ".json_encode($expected).' → '.json_encode($actual)];
    }

    // ------------------------------------------------------------------

    /**
     * The baseline needs MySQL, like the server. phpunit.xml points every
     * test at an in-memory SQLite database, so this test switches to MySQL
     * itself, using the host, user and password from .env and a database of
     * its own: BASELINE_DB_DATABASE, or scm_baseline if that isn't set.
     * It's created if missing and rebuilt on every run, so it refuses to use
     * the database .env names for the app.
     */
    private function useBaselineDatabase(): void
    {
        $env = is_file(base_path('.env')) ? Dotenv::parse(file_get_contents(base_path('.env'))) : [];
        $database = getenv('BASELINE_DB_DATABASE') ?: ($env['BASELINE_DB_DATABASE'] ?? 'scm_baseline');

        if ($database === ($env['DB_DATABASE'] ?? null)) {
            $this->fail("The baseline test rebuilds its database. Give it its own: BASELINE_DB_DATABASE ($database) is the app's DB_DATABASE.");
        }

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => getenv('DB_HOST') ?: ($env['DB_HOST'] ?? '127.0.0.1'),
            'database.connections.mysql.port' => getenv('DB_PORT') ?: ($env['DB_PORT'] ?? '3306'),
            'database.connections.mysql.username' => getenv('DB_USERNAME') ?: ($env['DB_USERNAME'] ?? 'root'),
            'database.connections.mysql.password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ($env['DB_PASSWORD'] ?? ''),
            'database.connections.mysql.database' => null,
        ]);
        DB::purge('mysql');
        DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Job batches and failed jobs follow DB_CONNECTION, read when the app boots.
        config([
            'database.connections.mysql.database' => $database,
            'queue.batching.database' => 'mysql',
            'queue.failed.database' => 'mysql',
        ]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
    }

    private function freshDatabase(): void
    {
        $this->useBaselineDatabase();
        Artisan::call('migrate:fresh', ['--force' => true]);
        foreach (self::SEEDERS as $seeder) {
            mt_srand(crc32($seeder));
            Artisan::call('db:seed', ['--class' => "Database\\Seeders\\$seeder", '--force' => true]);
        }
    }

    private function clockAt(string $local): void
    {
        Carbon::setTestNow(Carbon::parse($local, self::TZ));
    }

    private function asAdmin(Admin $a): void
    {
        $this->flushSession();
        $this->withSession(['admin.logged_in' => true,
            'admin.user' => ['first_name' => $a->first_name, 'last_name' => $a->last_name, 'role' => $a->role, 'id' => $a->id]]);
    }

    private function asFaculty(Faculty $f): void
    {
        $this->flushSession();
        $this->withSession(['faculty.logged_in' => true, 'faculty.has_course_access' => true,
            'faculty.user' => ['first_name' => $f->first_name, 'last_name' => $f->last_name, 'id' => $f->id]]);
    }

    private function asStudent(Student $s): void
    {
        $this->flushSession();
        $this->withSession(['student.logged_in' => true,
            'student.user' => ['first_name' => $s->first_name, 'last_name' => $s->last_name, 'id' => $s->id]]);
    }

    /** Logs each step's status, redirect and flashed message, so a silent failure shows in the record. */
    private function step(string $name, $response): void
    {
        $session = $this->app['session.store'];
        $entry = ['step' => $name, 'status' => $response->getStatusCode()];
        if ($response->isRedirect()) {
            $entry['redirect'] = preg_replace('#^https?://[^/]+#', '', $response->headers->get('Location'));
        }
        foreach (['error', 'success', 'message', 'warning'] as $k) {
            if ($session->has($k)) {
                $entry[$k] = $session->get($k);
            }
        }
        if ($session->has('errors')) {
            $entry['validation'] = $session->get('errors')->all();
        }
        $body = $response->getContent();
        if ($response->headers->get('Content-Type') === 'application/json') {
            $entry['json'] = json_decode($body, true);
        }
        $this->log[] = $entry;
        $session->forget(['error', 'success', 'message', 'warning', 'errors']);
    }

    /** Team's Q3 forecast scaled by $factor, in the form's field names. */
    private function forecastPayload(int $simId, int $group, float $factor): array
    {
        $rows = DB::table('forecastings')->where(['simulation_id' => $simId, 'group_number' => $group, 'quarter' => 3])
            ->orderBy('region_id')->orderBy('product_id')->get();
        $payload = ['simulation_id' => $simId, 'quarter_id' => 4];
        foreach ($rows as $r) {
            $payload["region_{$r->region_id}_product_{$r->product_id}_demand_1"] = [(string) (int) round($r->demand_channel_01 * $factor)];
            $payload["region_{$r->region_id}_product_{$r->product_id}_demand_2"] = [(string) (int) round($r->demand_channel_02 * $factor)];
        }

        return $payload;
    }

    /** Team's Q3 procurement per region scaled by $factor, in the form's field names. */
    private function procurementPayloads(int $simId, int $group, float $factor): array
    {
        $out = [];
        $procs = DB::table('procurements')->where(['simulation_id' => $simId, 'group_number' => $group, 'quarter' => 3])
            ->orderBy('region_id')->get();
        foreach ($procs as $p) {
            $payload = ['simulation_id' => $simId, 'quarter_id' => 4, 'region_id' => $p->region_id,
                'procurement_type' => array_values(array_filter(explode(',', (string) $p->procurement_type), 'strlen'))];
            foreach (DB::table('procurement_raw_materials')->where('procurement_id', $p->id)->orderBy('raw_material_id')->get() as $rm) {
                $payload['raw_material'][$rm->raw_material_id] = (string) (int) round($rm->quantity * $factor);
            }
            foreach (DB::table('procurement_sub_assemblies')->where('procurement_id', $p->id)->orderBy('id')->get() as $sa) {
                $payload["sub_assembly_component_{$sa->sub_assembly_component_id}_supplier_{$sa->supplier_id}_supply_channel_{$sa->supply_channel_id}"]
                    = (string) (int) round($sa->total_shipment * $factor);
            }
            $out[$p->region_id] = $payload;
        }

        return $out;
    }

    /** Every table with a simulation_id column, rows for this course, ids and timestamps left out. */
    private function dumpTables(int $simId): array
    {
        $db = DB::getDatabaseName();
        $tables = collect(DB::select(
            "SELECT TABLE_NAME AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = 'simulation_id' ORDER BY TABLE_NAME",
            [$db]
        ))->pluck('t')->all();

        // Child tables keyed by a parent id rather than simulation_id.
        $children = [
            'procurement_raw_materials' => ['procurements', 'procurement_id'],
            'procurement_sub_assemblies' => ['procurements', 'procurement_id'],
        ];

        $out = [];
        foreach ($tables as $t) {
            $rows = DB::table($t)->where('simulation_id', $simId)->get()->map(fn ($r) => $this->clean((array) $r))->all();
            $out[$t] = $this->sortRows($rows);
        }
        foreach ($children as $child => [$parent, $fk]) {
            if (! DB::getSchemaBuilder()->hasTable($child)) {
                continue;
            }
            $rows = DB::table($child)->join($parent, "$parent.id", '=', "$child.$fk")
                ->where("$parent.simulation_id", $simId)
                ->select("$child.*", "$parent.group_number as _group", "$parent.quarter as _quarter", "$parent.region_id as _region")
                ->get()->map(fn ($r) => $this->clean((array) $r, [$fk]))->all();
            $out[$child] = $this->sortRows($rows);
        }

        return $out;
    }

    private function clean(array $row, array $alsoDrop = []): array
    {
        foreach (array_merge(self::EXCLUDED_COLUMNS, $alsoDrop) as $c) {
            unset($row[$c]);
        }
        foreach (array_keys($row) as $c) {
            if (preg_match('/(file|path|_url|request_id|uuid)/', $c)) {
                unset($row[$c]);
            }
        }
        ksort($row);

        return $row;
    }

    private function sortRows(array $rows): array
    {
        usort($rows, fn ($a, $b) => strcmp(json_encode($a), json_encode($b)));

        return $rows;
    }

    /** The report services each page calls, for every team and quarter Q0-Q4. */
    private function reports(int $simId, int $facultyId): array
    {
        $svc = fn ($c) => app($c);
        $out = [];
        foreach ([1, 2, 3] as $g) {
            foreach ([0, 1, 2, 3, 4] as $q) {
                $key = "team{$g}_q{$q}";
                $out[$key] = [
                    'corporate_pnl' => $this->capture(fn () => $svc(CorporatePnlService::class)->corporateProfitLoss($simId, $facultyId, $g, $q)),
                    'product_pnl' => $this->capture(fn () => $svc(ProductPnlService::class)->productPnl($simId, $facultyId, $g, $q)),
                    'balance_sheet' => $this->capture(fn () => $svc(BalanceSheetService::class)->generateBalanceSheet($simId, $facultyId, $g, $q)),
                    'cash_flow' => $this->capture(fn () => $svc(CashFlowService::class)->generateReport($simId, $facultyId, $g, $q)),
                    'scorecard' => $this->capture(fn () => $svc(FlexScorecardService::class)->generateScorecard($simId, $facultyId, $g, $q)),
                ];
            }
        }
        // Benchmarks are stored with each scorecard row (tables.scorecards); FlexScorecardService has no generateBenchmarks().

        return $out;
    }

    /** Runs a report; an exception is recorded as a result, so a failing report is part of the baseline. */
    private function capture(callable $fn)
    {
        try {
            return $this->normalise($fn());
        } catch (\Throwable $e) {
            return ['_exception' => class_basename($e).': '.$e->getMessage(),
                '_at' => str_replace(base_path().'/', '', $e->getFile()).':'.$e->getLine()];
        }
    }

    private function normalise($v)
    {
        if ($v instanceof Collection) {
            $v = $v->all();
        }
        if ($v instanceof Arrayable) {
            $v = $v->toArray();
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d H:i:s');
        }
        if (is_object($v)) {
            $v = get_object_vars($v);
        }
        if (is_array($v)) {
            foreach ($v as $k => $x) {
                if (in_array($k, self::EXCLUDED_COLUMNS, true)) {
                    unset($v[$k]);

                    continue;
                }
                $v[$k] = $this->normalise($x);
            }
        }
        if (is_float($v)) {
            return round($v, 6);
        }

        return $v;
    }

    private function sortDeep($v)
    {
        if (is_array($v)) {
            $isList = array_keys($v) === range(0, count($v) - 1);
            foreach ($v as $k => $x) {
                $v[$k] = $this->sortDeep($x);
            }
            if (! $isList) {
                ksort($v, SORT_STRING);
            }
        }

        return $v;
    }
}
