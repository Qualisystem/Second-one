<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationGeolocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'latitude',
        'longitude',
        'label',
        'raw_value',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the application that owns the geolocation.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}