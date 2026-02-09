<?php

namespace App\Filament\Resources;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Filament\Resources\ApplicationResource\Pages;
use App\Models\Application;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\FileUpload;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Get;
use Filament\Forms\Components\Placeholder;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    public static function getNavigationLabel(): string
    {
        return __('Assets (CIIs)');
    }

    public static function getNavigationGroup(): string
    {
        return __('Entities');
    }

    public static function getModelLabel(): string
    {
        return __('Asset (CII)');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Assets (CIIs)');
    }

    //Restrict access to only the assets created by the logged-in user
    // public static function getEloquentQuery(): Builder
    // {
    //     $query = parent::getEloquentQuery();

    //     // Don’t restrict in console (migrations, seeds, etc.)
    //     if (app()->runningInConsole()) {
    //         return $query;
    //     }

    //     $user = Auth::user();

    //     // Block access if not authenticated
    //     if (!$user) {
    //         return $query->whereRaw('1 = 0');
    //     }

    //     // Restrict to the creator’s code (fallback to user id if no code)
    //     return $query->where('user_code', $user->code ?? (string) $user->id);
    // }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (app()->runningInConsole()) {
            return $query;
        }

        $user = Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Super admin can see all applications
        if ($user->hasRole('Super Admin')) {
            return $query;
        }

        // Show only risks from the user's institution
        if ($user->institution_id) {
            return $query->where('institution_id', $user->institution_id);
        }

        return $query->whereRaw('1 = 0');
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        // Check if a user is authenticated and has a 'code' property
        if ($user && property_exists($user, 'code')) {
            // Use the correct database column 'user_code'
            // Use the user's 'code'
            $data['user_code'] = $user->code;
        }

        // Set institution from logged-in user
        if ($user && $user->institution_id) {
            $data['institution_id'] = $user->institution_id;
        }

        return $data;
    }

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Section::make('Basic Information')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('CII Name')
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\Select::make('type')
                        ->label('Asset Type')
                        ->options([
                            'Networks and Communication' => 'Networks and Communication',
                            'Data' => 'Data',
                            'Hardware and Computing Infrastructure' => 'Hardware and Computing Infrastructure',
                            'Software Systems' => 'Software Systems',
                            'Human' => 'Human',
                            'Facilities' => 'Facilities',
                        ])
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('service')
                        ->label('Service')
                        ->maxLength(255),
                    
                    Forms\Components\Select::make('institution_id')
                        ->relationship('institution', 'label')
                        ->searchable()
                        ->preload()
                        ->required(),
                    
                    Forms\Components\Select::make('status')
                        ->options([
                            'A' => 'Active',
                            'C' => 'Candidate',
                            'R' => 'Retired',
                        ])
                        ->default('A')
                        ->required()
                        ->searchable(),
                    
                    Forms\Components\TextInput::make('custodian')
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('location')
                        ->maxLength(255),
                    
                    Forms\Components\Select::make('tier')
                        ->options([
                            1 => 'Tier 1',
                            2 => 'Tier 2',
                            3 => 'Tier 3',
                            4 => 'Tier 4',
                        ])
                        ->nullable()
                        ->searchable(),
                    
                    Forms\Components\TextInput::make('url')
                        ->label('URL')
                        ->url()
                        ->maxLength(255),
                    
                    // Forms\Components\TextInput::make('vendor')
                    //     ->maxLength(255),
                    
                    Forms\Components\TextInput::make('owner_name')->label('Ownership')->maxLength(255),
                    
                    // TagsInput::make('dependencies')
                    //     ->label('Dependencies (Upstream/Downstream Asset)')
                    //     ->placeholder('Press enter to add')
                    //     ->columnSpanFull(),
                    Forms\Components\Textarea::make('dependencies')
                    ->label('Dependencies (Upstream/Downstream Asset)')
                    ->rows(3)
                    ->maxLength(65535)
                    ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ]),

                FileUpload::make('logo')
                ->label('Logo')
                ->image()
                ->acceptedFileTypes([
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'image/gif',
                    'image/svg+xml',
                ])
                ->disk('public')
                ->directory('applications/logos')
                ->visibility('public'),
                
            
            Section::make('Assessment')
    ->columns(3)
    ->schema([
        Forms\Components\Select::make('impact_users_affected')
            ->label('Impact Users Affected')
            ->options([0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4'])
            ->required()
            ->searchable()
            ->live(),

        Forms\Components\Select::make('economic_impact')
            ->label('Economic Impact')
            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4'])
            ->required()
            ->searchable()
            ->live(),

        Forms\Components\Select::make('recovery_time')
            ->label('Recovery Time')
            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4'])
            ->required()
            ->searchable()
            ->live(),

        Forms\Components\Select::make('availability_of_alternatives')
            ->label('Availability of Alternatives')
            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4'])
            ->required()
            ->searchable()
            ->live(),

        Forms\Components\Select::make('cross_sector_dependencies')
            ->label('Cross-sector Dependencies')
            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4'])
            ->required()
            ->searchable()
            ->live(),

        Placeholder::make('score_preview')
            ->label('Score (auto)')
            ->content(function (Get $get): string {
                $fields = [
                    'impact_users_affected',
                    'economic_impact',
                    'recovery_time',
                    'availability_of_alternatives',
                    'cross_sector_dependencies',
                ];

                foreach ($fields as $field) {
                    if ($get($field) === null || $get($field) === '') {
                        return 'Select all assessment values to see the score.';
                    }
                }

                $sum = (int) $get('impact_users_affected')
                    + (int) $get('economic_impact')
                    + (int) $get('recovery_time')
                    + (int) $get('availability_of_alternatives')
                    + (int) $get('cross_sector_dependencies');

                return (string) $sum;
            })
            ->columnSpanFull(),
    ]),
            
            Section::make('Geolocations')
                ->schema([
                    Repeater::make('geolocations')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('label')
                                ->label('Location Label')
                                ->placeholder('e.g., Main Office, Data Center'),
                            
                            Forms\Components\TextInput::make('latitude')
                                ->numeric()
                                ->step(0.00000001),
                            
                            Forms\Components\TextInput::make('longitude')
                                ->numeric()
                                ->step(0.00000001),
                            
                            Forms\Components\TextInput::make('raw_value')
                                ->label('Google Plus Code')
                                ->placeholder('e.g., MQRG+X22'),
                        ])
                        ->columns(4)
                        ->addActionLabel('Add Geolocation')
                        ->defaultItems(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('code')->label(__('Code'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label(__('Name'))->searchable(),
                Tables\Columns\TextColumn::make('owner_name')->label(__('Owner'))->searchable(),
                Tables\Columns\TextColumn::make('type')
    ->label(__('Type'))
    ->badge()
    ->formatStateUsing(fn (?string $state) => \App\Enums\ApplicationType::tryFrom($state ?? '')?->getLabel() ?? $state)
    ->color(function ($record) {
        $enum = \App\Enums\ApplicationType::tryFrom((string) $record->type);

        if ($enum) {
            return $enum->getColor();
        }

        // fallback for your new CSV-style values (strings)
        return match ((string) $record->type) {
            'Networks and Communication' => 'primary',
            'Data' => 'info',
            'Hardware and Computing Infrastructure' => 'warning',
            'Software Systems' => 'success',
            'Human' => 'danger',
            'Facilities' => 'gray',
            default => 'secondary',
        };
    }),
                Tables\Columns\TextColumn::make('custodian_name')->label(__('Custodian')),
                Tables\Columns\TextColumn::make('status')->label(__('Status'))->badge()->searchable(),
                // Tables\Columns\TextColumn::make('status')->label(__('Status'))->badge()->color(fn ($record) => $record->status->getColor()),
                Tables\Columns\TextColumn::make('url')->label(__('URL'))->url(fn ($record) => $record->url, true),
                Tables\Columns\TextColumn::make('created_at')->label(__('Created'))->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label(__('Updated'))->dateTime()->sortable(),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
            Tables\Actions\Action::make('view_map')
                ->label('See assets on a map')
                ->url(fn (): string => static::getUrl('map'))
                ->icon('heroicon-o-map'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
            'map' => Pages\AssetsMap::route('/map'),
        ];
    }
}
