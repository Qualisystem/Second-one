<?php

namespace App\Filament\Concerns;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Filament\Forms\Components\Select;

/**
 * Trait for easily adding taxonomy selects to Filament forms using polymorphic relationships
 *
 * Usage in your Filament Resource:
 *
 * use App\Filament\Concerns\HasTaxonomyFields;
 *
 * class YourResource extends Resource
 * {
 *     use HasTaxonomyFields;
 *
 *     public static function form(Form $form): Form
 *     {
 *         return $form->schema([
 *             // Single selection
 *             self::taxonomySelect('Department', required: true),
 *
 *             // Multiple selection
 *             self::taxonomySelect('Scope', multiple: true),
 *
 *             // Hierarchical display
 *             self::hierarchicalTaxonomySelect('Risk Level'),
 *         ]);
 *     }
 * }
 */
trait HasTaxonomyFields
{
    /**
     * Create a select field for taxonomy terms using polymorphic relationships
     *
     * @param  string  $taxonomyName  The name of the taxonomy (e.g., 'Department', 'Scope')
     * @param  string  $fieldName  The form field name (defaults to snake_case of taxonomy name)
     * @param  bool  $multiple  Allow multiple selections
     * @param  bool  $required  Is the field required
     */
    public static function taxonomySelect(
        string $taxonomyName,
        ?string $fieldName = null,
        bool $multiple = false,
        bool $required = false
    ): Select {
        $fieldName = $fieldName ?: strtolower(str_replace(' ', '_', $taxonomyName));

        $select = Select::make($fieldName)
            ->label($taxonomyName)
            ->options(function () use ($taxonomyName) {
                // Find the root taxonomy by name
                $taxonomy = Taxonomy::where('name', $taxonomyName)
                    ->whereNull('parent_id')
                    ->first();
                    
                if (!$taxonomy) {
                    return [];
                }
                
                // Get children (terms) of this taxonomy
                return Taxonomy::where('parent_id', $taxonomy->id)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            })
            ->afterStateHydrated(function (Select $component, $state, $record) use ($taxonomyName) {
                if (!$record) return;
                
                // Find the taxonomy type
                $taxonomy = Taxonomy::where('name', $taxonomyName)
                    ->whereNull('parent_id')
                    ->first();
                    
                if (!$taxonomy) return;
                
                // Get the current taxonomy term for this type
                $currentTerm = $record->taxonomies()
                    ->where('parent_id', $taxonomy->id)
                    ->first();
                    
                $component->state($currentTerm?->id);
            })
            ->saveRelationshipsUsing(function (Select $component, $state) use ($taxonomyName) {
                $record = $component->getRecord();
                if (!$record || !$state) return;
                
                // Find the taxonomy type
                $taxonomy = Taxonomy::where('name', $taxonomyName)
                    ->whereNull('parent_id')
                    ->first();
                    
                if (!$taxonomy) return;
                
                // Detach any existing terms of this taxonomy type
                $existingTermIds = Taxonomy::where('parent_id', $taxonomy->id)->pluck('id');
                $record->taxonomies()->detach($existingTermIds);
                
                // Attach the new term
                $record->taxonomies()->attach($state);
            })
            ->dehydrated(false)
            ->searchable()
            ->preload();

        if ($multiple) {
            $select->multiple();
        }

        if ($required) {
            $select->required();
        }

        return $select;
    }

    /**
     * Create a select field for hierarchical taxonomy terms using polymorphic relationships
     *
     * @param  string  $taxonomyName  The name of the taxonomy
     * @param  string  $fieldName  The form field name (defaults to snake_case of taxonomy name)
     * @param  bool  $multiple  Allow multiple selections
     * @param  bool  $required  Is the field required
     */
    public static function hierarchicalTaxonomySelect(
        string $taxonomyName,
        ?string $fieldName = null,
        bool $multiple = false,
        bool $required = false
    ): Select {
        $fieldName = $fieldName ?: strtolower(str_replace(' ', '_', $taxonomyName));

        $select = Select::make($fieldName)
            ->label($taxonomyName)
            ->relationship(
                name: 'taxonomies',
                titleAttribute: 'name',
                modifyQueryUsing: function ($query) use ($taxonomyName) {
                    // Find the root taxonomy by name
                    $taxonomy = Taxonomy::where('name', $taxonomyName)
                        ->whereNull('parent_id')
                        ->first();
                        
                    if (!$taxonomy) {
                        return $query->whereRaw('1 = 0'); // Return empty result
                    }
                    
                    // Only show children (terms) of this taxonomy
                    return $query->where('parent_id', $taxonomy->id)
                        ->with('parent')
                        ->orderBy('name');
                }
            )
            ->getOptionLabelFromRecordUsing(function (Taxonomy $record) {
                // Show hierarchical format: Parent → Child
                return $record->parent 
                    ? $record->parent->name . ' → ' . $record->name
                    : $record->name;
            })
            ->searchable()
            ->preload();

        if ($multiple) {
            $select->multiple();
        }

        if ($required) {
            $select->required();
        }

        return $select;
    }

    /**
     * Handle saving taxonomy relationships from form data
     *
     * @param  Model  $record  The model instance
     * @param  array  $data  The form data
     */
    public static function saveTaxonomyRelationships($record, array $data): void
    {
        // List of known taxonomy field names and their corresponding taxonomy names
        $taxonomyFields = [
            'department' => 'Department',
            'scope' => 'Scope',
            // Add more as needed
        ];
        
        foreach ($taxonomyFields as $fieldName => $taxonomyName) {
            if (!isset($data[$fieldName]) || !$data[$fieldName]) {
                continue;
            }
            
            $value = $data[$fieldName];
            
            // Find the root taxonomy by name
            $taxonomy = Taxonomy::where('name', $taxonomyName)
                ->whereNull('parent_id')
                ->first();
                
            if (!$taxonomy) {
                continue;
            }
            
            // Detach any existing terms of this taxonomy type
            $existingTermIds = Taxonomy::where('parent_id', $taxonomy->id)->pluck('id');
            $record->taxonomies()->detach($existingTermIds);
            
            // Attach the new term(s)
            if (is_array($value)) {
                $record->taxonomies()->attach($value);
            } else {
                $record->taxonomies()->attach($value);
            }
        }
    }
}