<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpdSigner extends Model
{
    protected $fillable = [
        'opd_id',
        'bidang_name',
        'title',
        'name',
        'nip',
        'nik',
        'rank',
        'eselon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }
}
