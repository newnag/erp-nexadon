<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthEvents
{
    /**
     * Handle user login events.
     */
    public function handleLogin(Login $event): void
    {
        if ($event->user) {
            ActivityLogger::logLogin($event->user);
        }
    }

    /**
     * Handle user logout events.
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            ActivityLogger::logLogout($event->user);
        }
    }
}
