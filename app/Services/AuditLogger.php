<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /**
     * Record an audit log entry.
     *
     * @param string $action Action key (e.g. login, logout, create_meeting, delete_meeting, sign_tte)
     * @param string $description Human-readable description
     * @param User|null $user The user who performed the action (defaults to auth()->user())
     * @return AuditLog|null
     */
    public static function log(string $action, string $description, ?User $user = null): ?AuditLog
    {
        try {
            $user = $user ?? (auth()->check() ? auth()->user() : null);

            return AuditLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? ($user?->nip ? "NIP: {$user->nip}" : 'Sistem/Tamu'),
                'user_nip' => $user?->nip,
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Gagal mencatat Audit Log [{$action}]: " . $e->getMessage());
            return null;
        }
    }
}
