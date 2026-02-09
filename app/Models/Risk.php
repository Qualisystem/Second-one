<?php

namespace App\Models;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use App\Enums\MitigationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Risk extends Model
{
    use HasFactory, HasTaxonomy;

    protected $casts = [
        'inherent_likelihood' => 'integer',
        'inherent_impact' => 'integer',
        'inherent_risk' => 'integer',
        'residual_likelihood' => 'integer',
        'residual_impact' => 'integer',
        'residual_risk' => 'integer',
        'target_completion_date' => 'date',
    ];

    protected $fillable = [
        'code', // This is the database column for S/N
        'application_id',
        'institution_id',
        'threat',
        'vulnerability',
        'risk',
        'existing_controls',
        'status_of_existing_controls',
        'inherent_likelihood',
        'inherent_impact',
        'inherent_risk',
        'residual_likelihood',
        'residual_impact',
        'residual_risk',
        'risk_owner',
        'residual_risk_owner',
        'treatment_options',
        'transfer_to',
        'treatment_description',
        'acceptable_control_from_any_standard',
        'responsible',
        'implementation_status',
        'comment_on_closure',
        'target_completion_date',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Institution::class);
    }

    public function implementations(): BelongsToMany
    {
        return $this->BelongsToMany(Implementation::class);
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'risks_index';
    }

    /**
     * Get the array representation of the model for search.
     */
    public function toSearchableArray(): array
    {
        return $this->toArray();
    }

    public static function next()
    {
        return static::max('id') + 1;
    }
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
