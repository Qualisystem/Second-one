<?php

namespace App\Filament\Resources;

use App\Enums\RiskStatus;
use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Resources\RiskResource\Pages;
use App\Filament\Resources\RiskResource\RelationManagers\ImplementationsRelationManager;
use App\Models\Risk;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class RiskResource extends Resource
{
    use HasTaxonomyFields;
    
    protected static ?string $model = Risk::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('risk-management.navigation_label');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['institution.sector']);

        if (app()->runningInConsole()) {
            return $query;
        }

        $user = Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Super admin can see all risks
        if ($user->hasRole('Super Admin')) {
            return $query;
        }

        // Show only risks from the user's institution
        if ($user->institution_id) {
            return $query->where('institution_id', $user->institution_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function form(Form $form): Form
    {

        return $form
            ->columns(4)
            ->schema([
                Forms\Components\Section::make('Risk Identification')
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('S/N')
                            ->unique('risks', 'code', ignoreRecord: true)
                            ->columnSpan(2)
                            ->readOnly()
                            ->disabled()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Vulnerability')
                            ->columnSpan(2)
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->label('Risk (Description)'),
                        Forms\Components\Select::make('application_id')
                            ->relationship(
                                name: 'application',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => self::scopeApplicationsByInstitution($query),
                            )
                            ->searchable()
                            ->preload()
                            ->label('Asset')
                            ->columnSpan(2)
                            ->required(),
                        Forms\Components\TextInput::make('threat')
                            ->label('Threat')
                            ->columnSpan(2)
                            ->required(),
                        Forms\Components\TextInput::make('existing_controls')
                            ->label('Existing Controls')
                            ->columnSpan(2)
                            ->required(),
                        Forms\Components\Select::make('status_of_existing_controls')
                            ->label('Status of Existing Controls')
                            ->options([
                                'Effective' => 'Effective',
                                'Not Effective' => 'Not Effective',
                            ])
                            ->placeholder('Select a status')
                            ->helperText('Select the status of the existing controls')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                    ]),

                Forms\Components\Section::make('Risk Analysis')
                    ->columns(4)
                    ->schema([
                        Forms\Components\ToggleButtons::make('inherent_likelihood')
                            ->label('Likelihood')
                            ->columnSpan(2)
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                        Forms\Components\ToggleButtons::make('inherent_impact')
                            ->label('Impact')
                            ->columnSpan(2)
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Risk Evaluation')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Placeholder::make('eval_impact')
                            ->hiddenLabel(true)
                            ->columnSpan(2)
                            ->content(function ($get) {
                                $value = $get('inherent_impact') ?? 'N/A';
                                return new \Illuminate\Support\HtmlString('<div class="text-sm">Impact value: <strong class="text-lg">' . $value . '</strong></div>');
                            }),

                        Forms\Components\Placeholder::make('eval_likelihood')
                            ->hiddenLabel(true)
                            ->columnSpan(2)
                            ->content(function ($get) {
                                $value = $get('inherent_likelihood') ?? 'N/A';
                                return new \Illuminate\Support\HtmlString('<div class="text-sm">Likelihood value: <strong class="text-lg">' . $value . '</strong></div>');
                            }),

                        Forms\Components\Placeholder::make('risk_value')
                            ->hiddenLabel(true)
                            ->columnSpan(2)
                            ->content(function ($get) {
                                $impact = (int) ($get('inherent_impact') ?? 0);
                                $likelihood = (int) ($get('inherent_likelihood') ?? 0);
                                $value = $impact * $likelihood;
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="text-sm">Risk Value: <strong class="text-lg">' . $value . '</strong></div>'
                                );
                            }),

                        Forms\Components\Placeholder::make('risk_valuation')
                            ->hiddenLabel(true)
                            ->columnSpan(2)
                            ->content(function ($get) {
                                $impact = (int) ($get('inherent_impact') ?? 0);
                                $likelihood = (int) ($get('inherent_likelihood') ?? 0);
                                $score = $impact * $likelihood;
                                
                                if ($score >= 10) {
                                    $label = 'High';
                                } elseif ($score >= 5) {
                                    $label = 'Medium';
                                } else {
                                    $label = 'Low';
                                }
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="text-sm">Risk Valuation: <strong class="text-base">' . $label . '</strong></div>'
                                );
                            }),

                        Forms\Components\TextInput::make('risk_owner')
                            ->label('Risk Owner')
                            ->maxLength(255)
                            ->columnSpan(4)
                            ->required(),
                    ]),
                
                Forms\Components\Section::make('Risk Treatment Plan')
                ->columns(4)
                ->schema([
                        Forms\Components\Select::make('treatment_options')
                            ->label('Treatment Options')
                            ->options([
                                'Accept' => 'Accept',
                                'Avoid' => 'Avoid',
                                'Reduce' => 'Reduce',
                                'Transfer' => 'Transfer',
                            ])
                            ->placeholder('Select a treatment option')
                            ->reactive() // This makes the field trigger updates on change
                            ->helperText('Select the treatment option for this risk')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(2),

                        // --- Fields that appear conditionally ---

                        // Appears when 'Transfer' is selected
                        // Forms\Components\Select::make('transfer_to')
                        //     ->label('Transfer to who?')
                        //     ->options(['Assurance' => 'Assurance'])
                        //     ->placeholder('Select a party')
                        //     ->required()
                        //     ->searchable()
                        //     ->preload()
                        //     ->nullable()
                        //     ->columnSpan(2)
                        //     ->visible(fn ($get) => $get('treatment_options') === 'Transfer'),

                        // Appears when 'Accept' is selected
                        // Forms\Components\Placeholder::make('accept_info')
                        //     ->label('Accepted')
                        //     ->columnSpan(4)
                        //     // ->visible(fn ($get) => $get('treatment_options') === 'Accept')
                        //     ->content(new \Illuminate\Support\HtmlString(
                        //         '<div class="fi-input-wrp">
                        //             <div class="fi-input-wrapper flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20">
                        //                 <div class="min-w-0 flex-1 self-center">
                        //                     <p class="p-3 text-sm">Continuous monitoring will be applied.</p>
                        //                 </div>
                        //             </div>
                        //         </div>'
                        //     )),

                        // All fields below appear only when 'Reduce' is selected
                        Forms\Components\TextInput::make('treatment_description')
                            ->columnSpan(2)
                            ->maxLength(255)
                            ->label('Treatment Description')
                            ->helperText('Describe the treatment plan for this risk')
                            ->required(),
                            // ->visible(fn ($get) => $get('treatment_options') !== 'Accept')
                        Forms\Components\TextInput::make('acceptable_control_from_any_standard')
                            ->columnSpan(2)
                            ->maxLength(255)
                            ->label('Applicable Control from Any Standard')
                            ->helperText('Describe the applicable control from any standard')
                            ->required(),
                            // ->visible(fn ($get) => $get('treatment_options') !== 'Accept')
                        Forms\Components\TextInput::make('responsible')
                            ->columnSpan(2)
                            ->maxLength(255)
                            ->label('Responsible')
                            ->helperText('Responsible person for this risk')
                            ->required(),
                            // ->visible(fn ($get) => $get('treatment_options') !== 'Accept')
                        Forms\Components\Select::make('implementation_status')
                            ->label('Implementation Status')
                            ->options([
                                'Open' => 'Open',
                                'Work-in-Progress' => 'Work-in-Progress',
                                'Closed' => 'Closed',
                            ])
                            ->placeholder('Implementation Status')
                            ->searchable()
                            ->preload()
                            ->helperText('Select the implementation status for this risk')
                            ->columnSpan(2)
                            ->required()
                            ->reactive(),
                            // ->visible(fn ($get) => $get('treatment_options') !== 'Accept')
                        Forms\Components\TextInput::make('comment_on_closure')
                            ->columnSpan(4)
                            ->maxLength(255)
                            ->label('Comment on Closure')
                            ->helperText('Give the comment on closure for this risk')
                            ->required(),
                            // ->visible(fn ($get) => $get('treatment_options') !== 'Accept')
                        Forms\Components\DatePicker::make('target_completion_date')
                            ->label('Target Date of Completion')
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            // ->minDate(\Illuminate\Support\Carbon::today()) // optional: prevent past dates
                            ->required()
                            // ->visible(fn ($get) => $get('treatment_options') !== 'Accept')
                            ->columnSpan(2),
                    ]),
                
                Forms\Components\Section::make('Residual Risk Analysis')
                    ->visible(fn ($get) => $get('implementation_status') === 'Closed')
                    ->columns(4)
                    ->schema([
                        Forms\Components\ToggleButtons::make('residual_likelihood')
                            ->label('Residual Likelihood')
                            ->columnSpan(2)
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                        Forms\Components\ToggleButtons::make('residual_impact')
                            ->label('Residual Impact')
                            ->columnSpan(2)
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Residual Risk Evaluation')
                    ->visible(fn ($get) => $get('implementation_status') === 'Closed')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Placeholder::make('residual_eval_impact')
                                    ->hiddenLabel(true)
                                    ->columnSpan(4)
                                    ->content(function ($get) {
                                        $value = $get('residual_impact') ?? 'N/A';
                                        return new \Illuminate\Support\HtmlString('<div class="text-sm">Residual Impact value: <strong class="text-lg">' . $value . '</strong></div>');
                                    }),

                                Forms\Components\Placeholder::make('residual_eval_likelihood')
                                    ->hiddenLabel(true)
                                    ->columnSpan(4)
                                    ->content(function ($get) {
                                        $value = $get('residual_likelihood') ?? 'N/A';
                                        return new \Illuminate\Support\HtmlString('<div class="text-sm">Residual Likelihood value: <strong class="text-lg">' . $value . '</strong></div>');
                                    }),

                                Forms\Components\Placeholder::make('residual_risk_value')
                                    ->hiddenLabel(true)
                                    ->columnSpan(4)
                                    ->content(function ($get) {
                                        $impact = (int) ($get('residual_impact') ?? 0);
                                        $likelihood = (int) ($get('residual_likelihood') ?? 0);
                                        $value = $impact * $likelihood;
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="text-sm">Residual Risk Value: <strong class="text-lg">' . $value . '</strong></div>'
                                        );
                                    }),

                                Forms\Components\Placeholder::make('residual_risk_valuation')
                                    ->hiddenLabel(true)
                                    ->columnSpan(2)
                                    ->content(function ($get) {
                                        $impact = (int) ($get('residual_impact') ?? 0);
                                        $likelihood = (int) ($get('residual_likelihood') ?? 0);
                                        $score = $impact * $likelihood;
                                        
                                        if ($score >= 10) {
                                            $label = 'High';
                                        } elseif ($score >= 5) {
                                            $label = 'Medium';
                                        } else {
                                            $label = 'Low';
                                        }
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="text-sm">Risk Valuation: <strong class="text-base">' . $label . '</strong></div>'
                                        );
                                    }),

                                Forms\Components\TextInput::make('residual_risk_owner')
                                    ->label('Residual Risk Owner')
                                    ->maxLength(255)
                                    ->columnSpan(4)
                                    ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('residual_risk', 'desc')
            ->emptyStateHeading('No Risks Identified Yet')
            ->emptyStateDescription('Add and analyse your first risk by clicking the "Track New Risk" button above.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->wrap()
                    ->formatStateUsing(function ($state) {
                        // Insert a zero-width space every 30 characters in long words
                        return preg_replace_callback('/\S{30,}/', function ($matches) {
                            return wordwrap($matches[0], 30, "\u{200B}", true);
                        }, $state);
                    })
                    ->limit(100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->wrap()
                    ->limit(250)
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        // Insert a zero-width space every 50 characters in long words
                        return preg_replace_callback('/\S{50,}/', function ($matches) {
                            return wordwrap($matches[0], 50, "\u{200B}", true);
                        }, $state);
                    }),
                Tables\Columns\TextColumn::make('institution.label')
                    ->label('Institution')
                    ->searchable()
                    ->limit(250)
                    ->sortable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('institution.sector.label')
                    ->label('Sector')
                    ->searchable()
                    ->limit(250)
                    ->sortable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('inherent_risk')
                    ->label('Inherent Risk')
                    ->sortable()
                    ->color(function (Risk $record) {
                        return self::getRiskColor($record->inherent_likelihood, $record->inherent_impact);
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('residual_risk')
                    ->sortable()
                    ->badge()
                    ->color(function (Risk $record) {
                        return self::getRiskColor($record->residual_likelihood, $record->residual_impact);
                    }),
                // Tables\Columns\TextColumn::make('department')
                //     ->label('Department')
                //     ->formatStateUsing(function (Risk $record) {
                //         $department = $record->taxonomies()
                //             ->whereHas('parent', function ($query) {
                //                 $query->where('name', 'Department');
                //             })
                //             ->first();
                //         return $department?->name ?? 'Not assigned';
                //     })
                //     ->sortable()
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('scope')
                //     ->label('Scope')
                //     ->formatStateUsing(function (Risk $record) {
                //         $scope = $record->taxonomies()
                //             ->whereHas('parent', function ($query) {
                //                 $query->where('name', 'Scope');
                //             })
                //             ->first();
                //         return $scope?->name ?? 'Not assigned';
                //     })
                //     ->sortable()
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tables\Filters\SelectFilter::make('department')
                //     ->label('Department')
                //     ->options(function () {
                //         $taxonomy = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('name', 'Department')
                //             ->whereNull('parent_id')
                //             ->first();
                        
                //         if (!$taxonomy) {
                //             return [];
                //         }
                        
                //         return \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('parent_id', $taxonomy->id)
                //             ->orderBy('name')
                //             ->pluck('name', 'id')
                //             ->toArray();
                //     })
                //     ->query(function ($query, array $data) {
                //         if (!$data['value']) {
                //             return;
                //         }
                        
                //         $query->whereHas('taxonomies', function ($query) use ($data) {
                //             $query->where('taxonomy_id', $data['value']);
                //         });
                //     }),
                // Tables\Filters\SelectFilter::make('scope')
                //     ->label('Scope')
                //     ->options(function () {
                //         $taxonomy = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('name', 'Scope')
                //             ->whereNull('parent_id')
                //             ->first();
                        
                //         if (!$taxonomy) {
                //             return [];
                //         }
                        
                //         return \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('parent_id', $taxonomy->id)
                //             ->orderBy('name')
                //             ->pluck('name', 'id')
                //             ->toArray();
                //     })
                //     ->query(function ($query, array $data) {
                //         if (!$data['value']) {
                //             return;
                //         }
                        
                //         $query->whereHas('taxonomies', function ($query) use ($data) {
                //             $query->where('taxonomy_id', $data['value']);
                //         });
                //     }),
                // Sector filter
                Tables\Filters\SelectFilter::make('sector_id')
                    ->label('Sector')
                    ->options(fn () =>
                        \App\Models\Sector::orderBy('label')->pluck('label', 'id')->toArray()
                    )
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('institution.sector', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }
                    }),

                // Institution filter (options depend on sector filter)
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Institution')
                    ->options(function () {
                        $sectorId = request()->input('tableFilters.sector_id.value');
                        $institutionsQuery = \App\Models\Institution::query();
                        if ($sectorId) {
                            $institutionsQuery->where('sector_id', $sectorId);
                        }
                        return $institutionsQuery->orderBy('label')->pluck('label', 'id')->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('institution_id', $data['value']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->hidden(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'implementations' => ImplementationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRisks::route('/'),
            'create' => Pages\CreateRisk::route('/create'),
            // 'edit' => Pages\EditRisk::route('/{record}/edit'),
            'view' => Pages\ViewRisk::route('/{record}'),
        ];
    }

    /**
     * @param  Risk  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return "$record->name";
    }

    /**
     * @param  Risk  $record
     */
    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return RiskResource::getUrl('view', ['record' => $record]);
    }

    /**
     * @param  Risk  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Risk' => $record->id,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description'];
    }

    // Mentioning the following classes to prevent them from being removed.
    // bg-grcblue-200 bg-red-200 bg-orange-200 bg-yellow-200 bg-green-200
    // bg-grcblue-500 bg-red-500 bg-orange-500 bg-yellow-500 bg-green-500

    public static function getRiskColor(int $likelihood, int $impact, int $weight = 200): string
    {
        // $average = round(($likelihood + $impact) / 2);

        // if ($average >= 5) {
        //     return "bg-red-$weight"; // High risk
        // } elseif ($average >= 4) {
        //     return "bg-orange-$weight"; // Moderate-High risk
        // } elseif ($average >= 3) {
        //     return "bg-yellow-$weight"; // Moderate risk
        // } elseif ($average >= 2) {
        //     return "bg-grcblue-$weight"; // Moderate risk
        // } else {
        //     return "bg-green-$weight"; // Low risk
        // }

        $riskScore = $likelihood * $impact;

        if ($riskScore >= 10) {
            return "bg-red-$weight"; // 10 to 15 (High)
        } elseif ($riskScore >= 5) {
            return "bg-yellow-$weight"; // 5 to 9 (Medium)
        } else {
            return "bg-green-$weight"; // 1 to 4 (Low)
        }
    }

    protected static function scopeApplicationsByInstitution(Builder $query): Builder
{
    $user = Auth::user();

    // Super admin sees all applications
    if ($user->hasRole('Super Admin')) {
        return $query;
    }

    // Regular users see only their institution's applications
    if ($user->institution_id) {
        return $query->where('institution_id', $user->institution_id);
    }

    return $query->whereRaw('1 = 0');
}
}
