<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Mailjet\Transport\MailjetApiTransport;
use Tests\TestCase;

class FoundationsTest extends TestCase
{
    public function test_home_page_names_the_sim_and_the_build(): void
    {
        config(['app.name' => 'FLEXEE Supply Chain Management', 'app.commit' => 'abc1234def5678']);

        $this->get('/')
            ->assertOk()
            ->assertSee('FLEXEE: Supply Chain Management')
            ->assertSee('Build abc1234');
    }

    public function test_home_page_leaves_out_the_build_line_when_no_commit_is_set(): void
    {
        config(['app.commit' => null]);

        $this->get('/')->assertOk()->assertDontSee('Build ', false);
    }

    public function test_sites_other_than_production_ask_search_engines_not_to_index(): void
    {
        $this->app['env'] = 'staging';

        $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_production_can_be_indexed(): void
    {
        $this->app['env'] = 'production';

        $this->get('/')->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_mailjet_mailer_uses_the_send_api(): void
    {
        config([
            'mail.mailers.mailjet.key' => 'test-public',
            'mail.mailers.mailjet.secret' => 'test-private',
        ]);

        $transport = Mail::mailer('mailjet')->getSymfonyTransport();

        $this->assertInstanceOf(MailjetApiTransport::class, $transport);
        $this->assertStringStartsWith('mailjet+api://', (string) $transport);
    }

    public function test_one_queue_worker_is_started_every_minute_under_a_lock(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'queue:work'));

        $this->assertCount(1, $events);

        $worker = $events->first();
        $this->assertSame('* * * * *', $worker->expression);
        $this->assertTrue($worker->withoutOverlapping);
        $this->assertSame(30, $worker->expiresAt);
        $this->assertStringContainsString('--stop-when-empty', $worker->command);
    }

    public function test_destructive_database_commands_are_refused_on_live_sites(): void
    {
        $this->app['env'] = 'staging';
        (new AppServiceProvider($this->app))->boot();

        $this->assertSame(1, Artisan::call('db:wipe', ['--force' => true]));

        DB::prohibitDestructiveCommands(false);
    }
}
