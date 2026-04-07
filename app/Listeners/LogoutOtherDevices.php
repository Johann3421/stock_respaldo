<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class LogoutOtherDevices
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        try {
            // Cerrar todas las sesiones previas del usuario excepto la actual
            $event->user->logoutOtherDevices(session()->getId());

            Log::info('Usuario logueado, sesiones previas cerradas', [
                'user_id' => $event->user->id,
                'email' => $event->user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cerrar sesiones previas: ' . $e->getMessage());
        }
    }
}
