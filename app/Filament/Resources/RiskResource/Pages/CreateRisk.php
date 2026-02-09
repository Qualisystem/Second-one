<?php

namespace App\Filament\Resources\RiskResource\Pages;

use App\Filament\Resources\RiskResource;
use App\Models\Risk;
use Dom\Text;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class CreateRisk extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = RiskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        // Set institution from logged-in user
        if ($user && $user->institution_id) {
            $data['institution_id'] = $user->institution_id;
        }
        // $data['inherent_risk'] = $data['inherent_likelihood'] * $data['inherent_impact'];
        // $data['residual_risk'] = $data['residual_likelihood'] * $data['residual_impact'];
        
        // --- START OF CHANGES ---

        // 1. Set the required 'name' field from the 'vulnerability' field.
        $data['name'] = $data['vulnerability'] ?? 'Unnamed Risk';
        $data['description'] = $data['risk_description'] ?? '';

        // --- END OF CHANGES ---

        // 1. Map the form's "s_n" field to the database "code" column
        if (isset($data['s_n'])) {
            $data['code'] = $data['s_n'];
            unset($data['s_n']); // Remove the temporary 's_n' key
        }

        // 2. Map your new 'eval_*' fields to the 'inherent_*' database columns
        if (isset($data['eval_likelihood'])) {
            $data['inherent_likelihood'] = (int) $data['eval_likelihood'];
            unset($data['eval_likelihood']); // Remove the temporary key
        }
        if (isset($data['eval_impact'])) {
            $data['inherent_impact'] = (int) $data['eval_impact'];
            unset($data['eval_impact']); // Remove the temporary key
        }

        // 3. Calculate inherent and residual risk scores
        $data['inherent_risk'] = (int)($data['inherent_likelihood'] ?? 0) * (int)($data['inherent_impact'] ?? 0);
        
        // Use the same values for residual risk as a fallback
        $data['residual_likelihood'] = (int)($data['residual_likelihood'] ?? $data['inherent_likelihood'] ?? 0);
        $data['residual_impact'] = (int)($data['residual_impact'] ?? $data['inherent_impact'] ?? 0);
        $data['residual_risk'] = $data['residual_likelihood'] * $data['residual_impact'];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Created new Risk';
    }

    /**
     * Generate the risk matrix HTML for the form
     */
    private function getRiskMatrixHtml($rangeText): string
    {
        // Get all existing risks
        $risks = Risk::all();
        $grid = array_fill(0, 5, array_fill(0, 5, []));
        
        // Populate the grid with existing risks
        foreach ($risks as $risk) {
            $likelihoodIndex = $risk->inherent_likelihood - 1;
            $impactIndex = $risk->inherent_impact - 1;
            
            if (isset($grid[$impactIndex][$likelihoodIndex])) {
                $grid[$impactIndex][$likelihoodIndex][] = $risk;
            }
        }

        // Generate the HTML
        $html = '
        <div style="width: 100%; display: flex; flex-direction: column; gap: 20px;">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" style="flex-grow: 1;">
            <h3 class="text-lg font-semibold text-center mb-4">Risk Matrix</h3>
            
            <div class="flex h-[300px]">
                <!-- Impact label -->
                <div style="width: 40px;" class="flex items-center justify-center">
                    <div class="transform -rotate-90 text-sm font-bold">Impact</div>
                </div>
                
                <!-- Row labels + grid -->
                <div class="flex-1 flex">
                    <!-- Row labels -->
                    <div class="w-10">
                        <div style="height: 61.5px; display:flex; align-items: center; justify-content: center; text-xs; padding-right: 8px;">5</div>
                        <div style="height: 61.5px; display:flex; align-items: center; justify-content: center; text-xs; padding-right: 8px;">4</div>
                        <div style="height: 61.5px; display:flex; align-items: center; justify-content: center; text-xs; padding-right: 8px;">3</div>
                        <div style="height: 61.5px; display:flex; align-items: center; justify-content: center; text-xs; padding-right: 8px;">2</div>
                        <div style="height: 61.5px; display:flex; align-items: center; justify-content: center; text-xs; padding-right: 8px;">1</div>
                    </div>
                    
                    <!-- Grid -->
                <div class="flex-1 grid grid-cols-5 gap-0.5">';
        
        for ($impact = 5; $impact >= 1; $impact--) {
            for ($likelihood = 1; $likelihood <= 5; $likelihood++) {
                $value = $impact * $likelihood;
                // color helper: use existing helper if available, fallback to neutral color
                if (method_exists(\App\Filament\Resources\RiskResource::class, 'getRiskColor')) {
                    $colorClass = \App\Filament\Resources\RiskResource::getRiskColor($likelihood, $impact, 200);
                } else {
                    $colorClass = 'bg-gray-100';
                }

                $html .= '<div class="flex items-center justify-center ' . $colorClass . '" style="height: 60px;">';
                $html .= '<span class="text-md">' . $value . '</span>';
                $html .= '</div>';
            }
        }
        
        $html .= '
                    </div>
                </div>
            </div>
            
            <!-- Bottom labels -->
            <div class="flex mt-2">
                <div style="width: 80px; flex-shrink: 0;"></div>
                <div class="flex-1 grid grid-cols-5 text-center">
                    <div class="text-xs mt-2">5</div>
                    <div class="text-xs mt-2">4</div>
                    <div class="text-xs mt-2">3</div>
                    <div class="text-xs mt-2">2</div>
                    <div class="text-xs mt-2">1</div>
                </div>
            </div>
            <div class="text-center" style="margin-top: 20px;">
                <span class="text-sm font-bold">Likelihood</span>
            </div>
        </div>
            <div style="margin-top: 5px">
                <div class="text-sm text-center">*Risk ' . $rangeText . '</div>
            </div>
        </div>';
        
        return $html;
    }

    public function getSteps(): array
    {
        return [
            Step::make('Risk Identification')
                ->columns(4)
                ->schema([
                    TextInput::make('code')
                        ->label('S/N')
                        ->prefix('S/N')
                        ->numeric()
                        // ->disabled()
                        ->readOnly()
                        ->columnSpan(2)
                        ->dehydrated(true)
                        ->minValue(0)
                        ->integer()
                        ->default(fn () => \Illuminate\Support\Carbon::now()->valueOf())
                        ->helperText('Unique code for this risk')
                        ->unique('risks', 'code')
                        ->required(),
                    Select::make('application_id')
                        ->relationship(
                            name: 'application',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) => $this->scopeApplicationsByInstitution($query),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(2)
                        ->label('Asset')
                        ->helperText('Give the risk a short but descriptive name')
                        ->required(),
                    TextInput::make('threat')
                        ->columnSpan(2)
                        ->maxLength(255)
                        ->label('Threat')
                        ->helperText('Give a short name to the threat')
                        ->required(),
                    TextInput::make('vulnerability')
                        ->columnSpan(2)
                        ->maxLength(255)
                        ->label('Vulnerability')
                        ->helperText('Give a short name to the vulnerability')
                        ->required(),
                    Textarea::make('risk_description')
                        ->label('Risk (Description)')
                        ->columnSpan(4)
                        ->maxLength(4096)
                        ->helperText('Provide a description of the risk that will help others understand it'),
                    // RiskResource::taxonomySelect('Department')
                    //     ->nullable()
                    //     ->columnSpan(2)
                    //     ->helperText('Select the department responsible for this risk'),
                    // RiskResource::taxonomySelect('Scope')
                    //     ->nullable()
                    //     ->columnSpan(2)
                    //     ->helperText('Select the scope this risk applies to'),
                    TextInput::make('existing_controls')
                        ->columnSpan(2)
                        ->maxLength(255)
                        ->label('Existing Controls')
                        ->helperText('Give a short name to the existing controls')
                        ->required(),
                    Select::make('status_of_existing_controls')
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

            Step::make('Risk Analysis')
                ->columns(2)
                ->schema([

                    // Placeholder::make('InherentRisk')
                    //     ->hiddenLabel(true)
                    //     ->columnSpan(4)
                    //     ->content('Inherent risk is the risk that exists before you apply any controls. 
                    //     Use your best judgement to answer the following questions.'),

                    Section::make('LIKELIHOOD')
                        ->columnSpan(1)
                        ->columns(1)
                        ->extraAttributes(['class' => 'flex flex-col items-center text-center'])
                        ->schema([
                            Placeholder::make('InherentLikelihood')
                                ->hiddenLabel(true)
                                ->columnSpan(4)
                                ->content(fn () => new \Illuminate\Support\HtmlString('<strong>Likelihood</strong> is the probability or chance that a particular risk event will occur within a specified time frame or under certain conditions. It reflects how often or how likely it is that the event might happen, based on evidence, trends, and expert judgment.')),
                            Placeholder::make('InherentImpactTable')
                                ->columnSpan(4)
                                ->view('components.misc.inherent_likelihood'),
                            ToggleButtons::make('inherent_likelihood')
                                ->reactive()
                                ->columnSpan(4)
                                ->label('Likelihood Score')
                                ->options([
                                    1 => new \Illuminate\Support\HtmlString('Very Low<br><span class="text-xs font-semibold block mt-3">1</span>'),
                                    2 => new \Illuminate\Support\HtmlString('Low<br><span class="text-xs font-semibold block mt-3">2</span>'),
                                    3 => new \Illuminate\Support\HtmlString('Moderate<br><span class="text-xs font-semibold block mt-3">3</span>'),
                                    4 => new \Illuminate\Support\HtmlString('High<br><span class="text-xs font-semibold block mt-3">4</span>'),
                                    5 => new \Illuminate\Support\HtmlString('Very High<br><span class="text-xs font-semibold block mt-3">5</span>'),
                                ])
                                ->default('3')
                                ->colors(
                                    [
                                        1 => 'success',  // Very Low
                                        2 => 'info',  // Low
                                        3 => 'primary',  // Moderate
                                        4 => 'warning',  // High
                                        5 => 'danger',  // Very High
                                    ]
                                )
                                ->grouped()
                                ->required(),
                        ]),

                    Section::make('IMPACT')
                        ->columns(1)
                        ->columnSpan(1)
                        ->extraAttributes(['class' => 'flex flex-col items-center text-center'])
                        ->schema([
                            Placeholder::make('InherentImpact')
                                ->hiddenLabel(true)
                                ->columnSpan(4)
                                ->content(fn () => new \Illuminate\Support\HtmlString('<strong>Impact</strong> refers to the potential severity or magnitude of the consequences if a risk event occurs. It measures the extent of damage or disruption that could result, considering factors such as financial loss, operational downtime, reputational harm, safety issues, or legal implications.')),
                            Placeholder::make('InherentImpactTable')
                                ->columnSpan(4)
                                ->view('components.misc.inherent_impact'),
                            ToggleButtons::make('inherent_impact')
                                ->reactive()
                                ->columnSpan(4)
                                ->label('Impact Score')
                                ->options([
                                    1 => new \Illuminate\Support\HtmlString('Low<br><span class="text-xs font-semibold block mt-3">1</span>'),
                                    2 => new \Illuminate\Support\HtmlString('Medium<br><span class="text-xs font-semibold block mt-3">2</span>'),
                                    3 => new \Illuminate\Support\HtmlString('High<br><span class="text-xs font-semibold block mt-3">3</span>'),
                                    4 => new \Illuminate\Support\HtmlString('Very High<br><span class="text-xs font-semibold block mt-3">4</span>'),
                                    5 => new \Illuminate\Support\HtmlString('Extreme<br><span class="text-xs font-semibold block mt-3">5</span>'),
                                ])
                                ->default('3')
                                ->colors(
                                    [
                                        1 => 'success',  // Very Low
                                        2 => 'info',  // Low
                                        3 => 'primary',  // Moderate
                                        4 => 'warning',  // High
                                        5 => 'danger',  // Very High
                                    ]
                                )
                                ->grouped()
                                ->required(),
                        ]),

                ]),

            Step::make('Risk Evaluation')
                ->columns(4)
                ->schema([
                    Section::make('')
                        ->columns(4)
                        ->columnSpan(4)
                        ->schema([
                            Placeholder::make('RiskMatrixDisplay')
                                ->hiddenLabel(true)
                                ->columnSpan(3)
                                ->content(function ($get) {
                                    $impact = (int) ($get('inherent_impact') ?? 0);
                                    $likelihood = (int) ($get('inherent_likelihood') ?? 0);
                                    $score = $impact * $likelihood;
                                    if ($score >= 10) {
                                        $rangeText = 'is in the 10-25 range (High)';
                                    } elseif ($score >= 5) {
                                        $rangeText = 'is in the 5-9 range (Medium)';
                                    } else {
                                        $rangeText = 'is in the 1-4 range (Low)';
                                    }
                                    return new \Illuminate\Support\HtmlString($this->getRiskMatrixHtml($rangeText));
                                }),

                            Placeholder::make('dynamic_risk_acceptance')
                                ->hiddenLabel(true)
                                ->columnSpan(1)
                                ->content(function () {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div style="height: 340px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                            <div class="border border-gray-300 rounded-md bg-gray-50 py-2 px-4 text-sm text-center">
                                                <strong>Risk acceptance</strong>
                                            </div>
                                            <div class="mt-2 text-sm text-center">Risk is &le; 4</div>
                                        </div>'
                                    );
                                }),
                        ]),
                    Section::make('')
                        ->columnSpan(4)
                        ->columns(4)
                        ->schema([
                            Placeholder::make('eval_impact')
                                ->hiddenLabel(true)
                                ->columnSpan(4)
                                ->content(function ($get) {
                                    $value = $get('inherent_impact') ?? 'N/A';
                                    return new \Illuminate\Support\HtmlString('<div class="text-sm">Impact value: <strong class="text-lg">' . $value . '</strong></div>');
                                }),

                            Placeholder::make('eval_likelihood')
                                ->hiddenLabel(true)
                                ->columnSpan(4)
                                ->content(function ($get) {
                                    $value = $get('inherent_likelihood') ?? 'N/A';
                                    return new \Illuminate\Support\HtmlString('<div class="text-sm">Likelihood value: <strong class="text-lg">' . $value . '</strong></div>');
                                }),

                            Placeholder::make('risk_value')
                                ->hiddenLabel(true)
                                ->columnSpan(4)
                                ->content(function ($get) {
                                    $impact = (int) ($get('inherent_impact') ?? 0);
                                    $likelihood = (int) ($get('inherent_likelihood') ?? 0);
                                    $value = $impact * $likelihood;
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="text-sm">Risk Value: <strong class="text-lg">' . $value . '</strong></div>'
                                    );
                                }),

                            Placeholder::make('risk_valuation')
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

                            TextInput::make('risk_owner')
                                ->label('Risk Owner')
                                ->maxLength(255)
                                ->columnSpan(4)
                                ->required(),
                        ]),
                ]),
            Step::make('Risk Treatment Plan')
                ->columns(4)
                ->schema([
                    Select::make('treatment_options')
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

                    // // Appears when 'Transfer' is selected
                    // Select::make('transfer_to')
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
                    // Placeholder::make('accept_info')
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
                    TextInput::make('treatment_description')
                        ->columnSpan(2)
                        ->maxLength(255)
                        ->label('Treatment Description')
                        ->helperText('Describe the treatment plan for this risk')
                        ->required(),
                        // ->visible(fn ($get) => $get('treatment_options') !== 'Accept')
                    TextInput::make('acceptable_control_from_any_standard')
                        ->columnSpan(2)
                        ->maxLength(255)
                        ->label('Applicable Control from Any Standard')
                        ->helperText('Describe the applicable control from any standard')
                        ->required(),
                        // ->visible(fn ($get) => $get('treatment_options') !== 'Accept')
                    TextInput::make('responsible')
                        ->columnSpan(2)
                        ->maxLength(255)
                        ->label('Responsible')
                        ->helperText('Responsible person for this risk')
                        ->required(),
                        // ->visible(fn ($get) => $get('treatment_options') !== 'Accept')
                    Select::make('implementation_status')
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
                    TextInput::make('comment_on_closure')
                        ->columnSpan(4)
                        ->maxLength(255)
                        ->label('Comment on Closure')
                        ->helperText('Give the comment on closure for this risk')
                        ->required(),
                        // ->visible(fn ($get) => $get('treatment_options') !== 'Accept')
                    DatePicker::make('target_completion_date')
                        ->label('Target Date of Completion')
                        ->native(false)
                        ->displayFormat('Y-m-d')
                        // ->minDate(\Illuminate\Support\Carbon::today()) // optional: prevent past dates
                        ->required()
                        // ->visible(fn ($get) => $get('treatment_options') !== 'Accept')
                        ->columnSpan(2),
            ]),
                    
            Step::make('Residual Risk Analysis')
            ->visible(fn ($get) => $get('implementation_status') === 'Closed')
            ->columns(4)
            ->schema([
                Section::make('RESIDUAL LIKELIHOOD')
                        ->columnSpan(2)
                        ->columns(4)
                        ->extraAttributes(['class' => 'flex flex-col items-center text-center'])
                        ->schema([
                            Placeholder::make('ResidualLikelihood')
                                ->hiddenLabel(true)
                                ->columnSpan(4)
                                ->content(fn () => new \Illuminate\Support\HtmlString('<strong>Residual Likelihood</strong> is the probability or chance that a particular risk event will occur after considering the effectiveness of existing controls and mitigation measures. It reflects the adjusted likelihood of the event happening, taking into account the impact of implemented strategies to reduce or manage the risk.')),
                            Placeholder::make('ResidualImpactTable')
                                ->columnSpan(4)
                                ->view('components.misc.inherent_likelihood'),
                            ToggleButtons::make('residual_likelihood')
                                ->reactive()
                                ->columnSpan(4)
                                ->label('Residual Likelihood Score')
                                ->options([
                                    1 => new \Illuminate\Support\HtmlString('Very Low<br><span class="text-xs font-semibold block mt-3">1</span>'),
                                    2 => new \Illuminate\Support\HtmlString('Low<br><span class="text-xs font-semibold block mt-3">2</span>'),
                                    3 => new \Illuminate\Support\HtmlString('Moderate<br><span class="text-xs font-semibold block mt-3">3</span>'),
                                    4 => new \Illuminate\Support\HtmlString('High<br><span class="text-xs font-semibold block mt-3">4</span>'),
                                    5 => new \Illuminate\Support\HtmlString('Very High<br><span class="text-xs font-semibold block mt-3">5</span>'),
                                ])
                                ->default('3')
                                ->colors(
                                    [
                                        1 => 'success',  // Very Low
                                        2 => 'info',  // Low
                                        3 => 'primary',  // Moderate
                                        4 => 'warning',  // High
                                        5 => 'danger',  // Very High
                                    ]
                                )
                                ->grouped()
                                ->required(),
                        ]),

                    Section::make('RESIDUAL IMPACT')
                        ->columns(4)
                        ->columnSpan(2)
                        ->extraAttributes(['class' => 'flex flex-col items-center text-center'])
                        ->schema([
                            Placeholder::make('ResidualImpact')
                                ->hiddenLabel(true)
                                ->columnSpan(4)
                                ->content(fn () => new \Illuminate\Support\HtmlString('<strong>Residual Impact</strong> refers to the potential severity or magnitude of the consequences if a risk event occurs. It measures the extent of damage or disruption that could result, considering factors such as financial loss, operational downtime, reputational harm, safety issues, or legal implications.')),
                            Placeholder::make('ResidualImpactTable')
                                ->columnSpan(4)
                                ->view('components.misc.inherent_impact'),
                            ToggleButtons::make('residual_impact')
                                ->reactive()
                                ->columnSpan(4)
                                ->label('Residual Impact Score')
                                ->options([
                                    1 => new \Illuminate\Support\HtmlString('Very Low<br><span class="text-xs font-semibold block mt-3">1</span>'),
                                    2 => new \Illuminate\Support\HtmlString('Low<br><span class="text-xs font-semibold block mt-3">2</span>'),
                                    3 => new \Illuminate\Support\HtmlString('Moderate<br><span class="text-xs font-semibold block mt-3">3</span>'),
                                    4 => new \Illuminate\Support\HtmlString('High<br><span class="text-xs font-semibold block mt-3">4</span>'),
                                    5 => new \Illuminate\Support\HtmlString('Very High<br><span class="text-xs font-semibold block mt-3">5</span>'),
                                ])
                                ->default('3')
                                ->colors(
                                    [
                                        1 => 'success',  // Very Low
                                        2 => 'info',  // Low
                                        3 => 'primary',  // Moderate
                                        4 => 'warning',  // High
                                        5 => 'danger',  // Very High
                                    ]
                                )
                                ->grouped()
                                ->required(),
                        ]),
            ]),

            Step::make('Residual Risk Evaluation')
                ->visible(fn ($get) => $get('implementation_status') === 'Closed')
                ->columns(4)
                ->schema([
                    Section::make('')
                        ->columns(4)
                        ->columnSpan(4)
                        ->schema([
                            Placeholder::make('RiskMatrixDisplay')
                                ->hiddenLabel(true)
                                ->columnSpan(3)
                                ->content(function ($get) {
                                    $impact = (int) ($get('residual_impact') ?? 0);
                                    $likelihood = (int) ($get('residual_likelihood') ?? 0);
                                    $score = $impact * $likelihood;
                                    if ($score >= 10) {
                                        $rangeText = 'is in the 10-25 range (High)';
                                    } elseif ($score >= 5) {
                                        $rangeText = 'is in the 5-9 range (Medium)';
                                    } else {
                                        $rangeText = 'is in the 1-4 range (Low)';
                                    }
                                    return new \Illuminate\Support\HtmlString($this->getRiskMatrixHtml($rangeText));
                                }),

                            Placeholder::make('dynamic_risk_acceptance')
                                ->hiddenLabel(true)
                                ->columnSpan(1)
                                ->content(function () {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div style="height: 340px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                            <div class="border border-gray-300 rounded-md bg-gray-50 py-2 px-4 text-sm text-center">
                                                <strong>Risk acceptance</strong>
                                            </div>
                                            <div class="mt-2 text-sm text-center">Risk is &le; 4</div>
                                        </div>'
                                    );
                                }),
                        ]),
                    Section::make('')
                        ->columnSpan(4)
                        ->columns(4)
                        ->schema([
                            Placeholder::make('residual_eval_impact')
                                ->hiddenLabel(true)
                                ->columnSpan(4)
                                ->content(function ($get) {
                                    $value = $get('residual_impact') ?? 'N/A';
                                    return new \Illuminate\Support\HtmlString('<div class="text-sm">Residual Impact value: <strong class="text-lg">' . $value . '</strong></div>');
                                }),

                            Placeholder::make('residual_eval_likelihood')
                                ->hiddenLabel(true)
                                ->columnSpan(4)
                                ->content(function ($get) {
                                    $value = $get('residual_likelihood') ?? 'N/A';
                                    return new \Illuminate\Support\HtmlString('<div class="text-sm">Residual Likelihood value: <strong class="text-lg">' . $value . '</strong></div>');
                                }),

                            Placeholder::make('residual_risk_value')
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

                            Placeholder::make('residual_risk_valuation')
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

                            TextInput::make('residual_risk_owner')
                                ->label('Residual Risk Owner')
                                ->maxLength(255)
                                ->columnSpan(4)
                                ->required(),
                        ]),
            ]),

        ];
    }

    protected function scopeApplicationsByInstitution(Builder $query): Builder
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
