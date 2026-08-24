<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use Prunable;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_nip',
        'action',
        'description',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the prunable model query (hapus log yang berumur lebih dari 90 hari).
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(90));
    }

    /**
     * Relation to the User who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get user-friendly label for action.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'login' => 'Login',
            'logout' => 'Logout',
            'create_meeting' => 'Buat Rapat',
            'delete_meeting' => 'Hapus Rapat',
            'sign_tte' => 'TTE',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get UI badge color classes for action.
     */
    public function getActionBadgeClassAttribute(): string
    {
        return match ($this->action) {
            'login' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'logout' => 'bg-slate-100 text-slate-700 border-slate-200',
            'create_meeting' => 'bg-primary-50 text-primary-700 border-primary-200',
            'delete_meeting' => 'bg-rose-50 text-rose-700 border-rose-200',
            'sign_tte' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }
}
