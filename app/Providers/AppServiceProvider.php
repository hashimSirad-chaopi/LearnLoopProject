<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Security trail: every admin sign-in, sign-out and failed sign-in goes
        // into the Activity Log (Admin > Activity). Members are not logged here.
        Event::listen(Login::class, function (Login $event) {
            if (($event->user->role ?? null) === 'admin') {
                $this->logAdminEvent($event->user->id, 'admin.login', 'Signed in from '.request()->ip());
            }
        });

        Event::listen(Logout::class, function (Logout $event) {
            if (($event->user->role ?? null) === 'admin') {
                $this->logAdminEvent($event->user->id, 'admin.logout', 'Signed out');
            }
        });

        Event::listen(Failed::class, function (Failed $event) {
            $admin = $event->user ?? User::where('email', $event->credentials['email'] ?? '')->first();

            if ($admin && $admin->role === 'admin') {
                $this->logAdminEvent($admin->id, 'admin.login_failed', 'Wrong password from '.request()->ip());
            }
        });
    }

    private function logAdminEvent(int $userId, string $action, string $description): void
    {
        try {
            ActivityLog::create(['user_id' => $userId, 'action' => $action, 'description' => $description]);
        } catch (\Throwable $e) {
            // Logging must never stop someone from signing in or out.
        }
    }
}
