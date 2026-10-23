<?php

namespace Tests\Feature;

use App\Models\FacultyInvite;
use App\Models\Student;
use App\Notifications\ResetPasswordWithRole;
use App\Support\NewAccountPassword;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * Stage 1's security work: the old app's open pages are gone, faculty reach
 * only their own courses, sign-in checks the account and renews the session,
 * and no password is written in the code.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public static function removedPages(): array
    {
        return [
            'clear caches' => ['/clear-cache'],
            'storage link' => ['/run-storage-link'],
            'deadline close' => ['/cron-jobs/simulation-synchronization'],
            'reminders' => ['/cron-jobs/simulation-decision-reminder'],
            'test mail' => ['/testMail'],
            'email template' => ['/emailtemplate'],
            'KPI debug' => ['/student/simulation/debug/kpi'],
            'KPI export' => ['/student/simulation/debug/kpi/export'],
            'dashboard dump' => ['/student/simulation/dashboardDump'],
        ];
    }

    #[DataProvider('removedPages')]
    public function test_the_old_open_pages_are_gone(string $path): void
    {
        $this->get($path)->assertNotFound();
    }

    public function test_admins_cannot_wipe_the_database_from_a_page(): void
    {
        $admin = $this->admin();

        $this->withSession($this->adminSession($admin))->get('/admin/run-setup')->assertNotFound();
        $this->withSession($this->adminSession($admin))->get('/admin/pending-migrations')->assertNotFound();
    }

    public function test_a_faculty_member_cannot_open_a_course_they_dont_teach(): void
    {
        [$courseId] = $this->course();
        $outsider = $this->faculty('outsider@example.test');

        $this->withSession($this->facultySession($outsider))
            ->get("/faculty/courses/{$courseId}")
            ->assertForbidden()
            ->assertSee('You don’t teach this course.')
            ->assertSee('Go to my home page');
    }

    public function test_the_faculty_member_who_teaches_a_course_gets_past_the_check(): void
    {
        [$courseId, $teacher] = $this->course();

        $status = $this->withSession($this->facultySession($teacher))
            ->get("/faculty/courses/{$courseId}")
            ->getStatusCode();

        $this->assertNotSame(403, $status);
    }

    public function test_a_faculty_member_cannot_open_a_student_from_another_course(): void
    {
        [$courseId, $teacher] = $this->course();
        $stranger = $this->student('stranger@example.test');

        $this->withSession($this->facultySession($teacher))
            ->get("/faculty/courses/{$courseId}/student-profile/{$stranger}")
            ->assertForbidden();
    }

    public function test_an_inactive_account_cannot_sign_in(): void
    {
        $this->student('away@example.test', status: 'Inactive');

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->postJson('/student/submit-login', ['email' => 'away@example.test', 'password' => 'correct horse'])
            ->assertForbidden()
            ->assertJson(['success' => false]);
    }

    public function test_signing_in_starts_a_new_session(): void
    {
        $this->student('here@example.test');

        $this->withoutMiddleware(ValidateCsrfToken::class)->get('/student/login');
        $before = session()->getId();

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->postJson('/student/submit-login', ['email' => 'here@example.test', 'password' => 'correct horse'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_sign_in_attempts_are_limited(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        foreach (range(1, 10) as $i) {
            $this->postJson('/faculty/submit-login', ['email' => 'nobody@example.test', 'password' => 'wrong']);
        }

        $this->postJson('/faculty/submit-login', ['email' => 'nobody@example.test', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_signed_in_people_go_to_their_own_home_from_the_front_page(): void
    {
        $faculty = $this->faculty('home@example.test');

        $this->withSession($this->facultySession($faculty))->get('/')->assertRedirect(route('faculty.dashboard'));
    }

    public function test_no_seeder_writes_a_password_in_the_code(): void
    {
        foreach (glob(database_path('seeders/*.php')) as $file) {
            $this->assertDoesNotMatchRegularExpression(
                "/(Hash::make|bcrypt)\\(\\s*['\"]/",
                file_get_contents($file),
                basename($file).' writes a password in the code.'
            );
        }
    }

    public function test_demo_accounts_are_never_made_on_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);

        (new DemoSeeder)->run();
    }

    public function test_the_test_team_uses_test_students_only(): void
    {
        [$courseId, $teacher] = $this->course();
        $real = $this->student('real@example.test');
        $test = $this->student('test@example.test', isTest: true);

        $this->withSession($this->facultySession($teacher))
            ->getJson("/faculty/import-students/{$courseId}")
            ->assertOk();

        $enrolled = DB::table('student_enrolls')->where('simulation_id', $courseId)->pluck('student_id')->all();

        $this->assertContains($test, $enrolled);
        $this->assertNotContains($real, $enrolled);
    }

    public function test_invite_passwords_are_not_stored_readable(): void
    {
        [$courseId, $teacher] = $this->course();

        $invite = FacultyInvite::create([
            'faculty_id' => $teacher->id,
            'simulation_id' => $courseId,
            'email' => 'invited@example.test',
        ]);

        $this->assertTrue(Hash::isHashed($invite->password_generated));
    }

    public function test_a_student_added_by_faculty_gets_their_own_password_link_not_a_shared_password(): void
    {
        Notification::fake();
        [$courseId, $teacher] = $this->course();

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->withSession($this->facultySession($teacher))
            ->post("/faculty/courses/{$courseId}/store-student", [
                'simulation_id' => $courseId, 'first_name' => 'New', 'last_name' => 'Student', 'email' => 'new@example.test',
            ])
            ->assertSessionHas('success', 'Student added. We’ve emailed them a link to set their password.');

        $student = Student::where('email', 'new@example.test')->firstOrFail();

        $this->assertTrue(Hash::isHashed($student->password));
        Notification::assertSentTo(
            $student,
            ResetPasswordWithRole::class,
            fn ($n) => $n->newAccount && $n->role === 'student',
        );
    }

    public function test_two_new_accounts_never_share_a_password(): void
    {
        $this->assertFalse(Hash::check(
            'anything',
            NewAccountPassword::placeholder()
        ));
        $this->assertNotSame(NewAccountPassword::placeholder(), NewAccountPassword::placeholder());
    }

    public function test_no_controller_writes_a_password_in_the_code(): void
    {
        foreach (glob(app_path('Http/Controllers/*.php')) as $file) {
            $this->assertDoesNotMatchRegularExpression(
                "/(Hash::make|bcrypt)\\(\\s*['\"]/",
                file_get_contents($file),
                basename($file).' writes a password in the code.'
            );
        }
    }

    public function test_create_admin_makes_an_active_admin_with_a_hashed_password(): void
    {
        $this->artisan('scm:create-admin', ['email' => 'Head@Example.test', '--first' => 'Head', '--super' => true])
            ->expectsQuestion('Password (leave empty for a random one)', 'a long enough password')
            ->assertSuccessful();

        $admin = DB::table('admins')->where('email', 'head@example.test')->first();

        $this->assertSame('super-admin', $admin->role);
        $this->assertSame('Active', $admin->status);
        $this->assertTrue(Hash::check('a long enough password', $admin->password));
    }

    // ------------------------------------------------------------------

    private function admin(): object
    {
        $id = DB::table('admins')->insertGetId([
            'first_name' => 'Ada', 'last_name' => 'Admin', 'email' => 'admin@example.test',
            'password' => Hash::make('correct horse'), 'role' => 'super-admin',
        ]);

        return DB::table('admins')->find($id);
    }

    private function faculty(string $email): object
    {
        $id = DB::table('faculties')->insertGetId([
            'faculty_code' => 'F'.crc32($email), 'first_name' => 'Fay', 'last_name' => 'Faculty',
            'email' => $email, 'password' => Hash::make('correct horse'),
        ]);

        return DB::table('faculties')->find($id);
    }

    private function student(string $email, string $status = 'Active', bool $isTest = false): int
    {
        return DB::table('students')->insertGetId([
            'student_code' => 'S'.crc32($email), 'first_name' => 'Sam', 'last_name' => 'Student',
            'email' => $email, 'password' => Hash::make('correct horse'), 'join_date' => '2026-01-01',
            'status' => $status, 'is_test' => $isTest,
        ]);
    }

    /** A course taught by one faculty member: [course id, faculty]. */
    private function course(): array
    {
        $teacher = $this->faculty('teacher@example.test');

        $courseId = DB::table('simulations')->insertGetId([
            'simulation_code' => '10000001', 'share_code' => 'ABCD-E1F', 'title' => 'Test course',
            'quarters' => 5, 'firms' => 1, 'teams' => 3, 'start_date' => '2026-01-12', 'end_date' => '2026-02-28',
            'created_by' => 1,
        ]);

        DB::table('simulation_faculties')->insert([
            'simulation_id' => $courseId, 'faculty_id' => $teacher->id, 'role' => 'faculty',
        ]);

        return [$courseId, $teacher];
    }

    private function adminSession(object $a): array
    {
        return ['admin.logged_in' => true, 'admin.user' => ['first_name' => $a->first_name, 'last_name' => $a->last_name, 'role' => $a->role, 'id' => $a->id]];
    }

    private function facultySession(object $f): array
    {
        return ['faculty.logged_in' => true, 'faculty.has_course_access' => true,
            'faculty.user' => ['first_name' => $f->first_name, 'last_name' => $f->last_name, 'id' => $f->id]];
    }
}
