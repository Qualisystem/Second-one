<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
    ];

    public function institutions(): HasMany
    {
        return $this->hasMany(\App\Models\Institution::class);
    }
}