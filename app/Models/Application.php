<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'notes',
        'economic_impact',
        'recovery_time',
        'availability_of_alternatives',
        'name',
        'type',
        'description',
        'institution_id',
        'custodian',
        'location',
        'service',
        'tier',
        'status',
        'logo',
        'impact_users_affected',
        'availability_impact',
        'confidentiality_impact',
        'integrity_impact',
        'financial_impact',
        'regulatory_compliance_impact',
        'reputational_damage_impact',
        'health_safety_impact',
        'environmental_impact',
        'cross_sector_dependencies',
        'dependencies',
        'url',
        // 'vendor',
        'owner_name',
    ];

    protected $casts = [
        'tier' => 'integer',
        'impact_users_affected' => 'integer',
        'availability_impact' => 'integer',
        'confidentiality_impact' => 'integer',
        'integrity_impact' => 'integer',
        'financial_impact' => 'integer',
        'regulatory_compliance_impact' => 'integer',
        'reputational_damage_impact' => 'integer',
        'health_safety_impact' => 'integer',
        'environmental_impact' => 'integer',
        'cross_sector_dependencies' => 'integer',
        // 'dependencies' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    // public function vendor(): BelongsTo
    // {
    //     return $this->belongsTo(Vendor::class);
    // }
    
    public function geolocations(): HasMany
    {
        return $this->hasMany(ApplicationGeolocation::class);
    }

    protected static function booted()
    {
        // static::creating(function ($application) {
        //     if (empty($application->code)) {
        //         // Generate a unique code, e.g., AST-B7C1D2
        //         $application->code = 'AST-' . strtoupper(Str::random(6));
        //     }
        //     // Ensure user_code is set to the current user (this part is commented out but can be enabled to allow only authenticated users to set user_code and see the assets they created)
        //     // if (empty($application->user_code) && Auth::check()) {
        //     //     $application->user_code = Auth::user()->code ?? (string) Auth::id();
        //     // }
        // });

        static::saving(function (self $application) {
            if (! Schema::hasColumn($application->getTable(), 'score')) {
                return;
            }

            $fields = [
                $application->impact_users_affected,
                $application->economic_impact,
                $application->recovery_time,
                $application->availability_of_alternatives,
                $application->cross_sector_dependencies,
            ];

            $application->score = collect($fields)->contains(null)
                ? null
                : array_sum(array_map('intval', $fields));
        });
    }
}
