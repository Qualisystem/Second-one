<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'abbreviation',
        'label',
        'sector_id',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sector::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }
}