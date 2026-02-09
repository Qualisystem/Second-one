<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class GeneralSchema
{
    public static function schema(): array
    {
        return [
            Section::make('General Configuration')
                ->schema([
                    TextInput::make('general.name')
                        ->default('ets')
                        ->minLength(2)
                        ->maxLength(16)
                        ->label('Application Name')
                        ->helperText('The name of your application')
                        ->required(),
                    TextInput::make('general.url')
                        ->default('http://localhost')
                        ->url()
                        ->label('Application URL')
                        ->helperText('The URL of your application')
                        ->required(),
                    TextInput::make('general.repo')
                        ->default('https://repo.openirm.com')
                        ->url()
                        ->label('Update Repository URL')
                        ->helperText('The URL of the repository to check for content updates')
                        ->required(),
                ]),

            // Section::make('Report Configuration')
            //     ->schema([
            //         FileUpload::make('report.logo')
            //             ->label('Custom Report Logo (Optional)')
            //             ->image()
            //             ->disk(fn () => config('filesystems.default'))
            //             ->directory('report-assets')
            //             ->visibility('private')
            //             ->imageResizeMode('contain')
            //             ->imageCropAspectRatio('16:9')
            //             ->imageResizeTargetWidth('512')
            //             ->imageResizeTargetHeight('512')
            //             ->helperText('Upload a custom logo to be used in reports. Recommended size: 512x512px')
            //             ->deleteUploadedFileUsing(function ($state) {
            //                 if ($state) {
            //                     Storage::disk(config('filesystems.default'))->delete($state);
            //                 }
            //             }),
            //     ]),

            Section::make('Storage Configuration')
                ->schema([
                    Select::make('storage.driver')
                        ->label('Storage Driver')
                        ->options([
                            'private' => 'Local Private Storage',
                            's3' => 'Amazon S3',
                        ])
                        ->default('private')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            if ($state === 'private') {
                                config()->set('filesystems.default', 'private');
                            }
                        }),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('storage.s3.key')
                                ->label('AWS Access Key ID')
                                ->visible(fn ($get) => $get('storage.driver') === 's3')
                                ->required(fn ($get) => $get('storage.driver') === 's3')
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    if (filled($state)) {
                                        try {
                                            $component->state(Crypt::decryptString($state));
                                        } catch (\Exception $e) {
                                            $component->state('');
                                        }
                                    }
                                }),

                            TextInput::make('storage.s3.secret')
                                ->label('AWS Secret Access Key')
                                ->password()
                                ->visible(fn ($get) => $get('storage.driver') === 's3')
                                ->required(fn ($get) => $get('storage.driver') === 's3')
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    if (filled($state)) {
                                        try {
                                            $component->state(Crypt::decryptString($state));
                                        } catch (\Exception $e) {
                                            $component->state('');
                                        }
                                    }
                                }),

                            TextInput::make('storage.s3.region')
                                ->label('AWS Region')
                                ->placeholder('us-east-1')
                                ->visible(fn ($get) => $get('storage.driver') === 's3')
                                ->required(fn ($get) => $get('storage.driver') === 's3'),

                            TextInput::make('storage.s3.bucket')
                                ->label('S3 Bucket Name')
                                ->visible(fn ($get) => $get('storage.driver') === 's3')
                                ->required(fn ($get) => $get('storage.driver') === 's3'),
                        ]),

                    Actions::make([
                        Action::make('testS3Connection')
                            ->label('Test S3 Connection')
                            ->color('primary')
                            ->action(function ($livewire) {
                                // Save current form data first
                                $livewire->save();
                                
                                // Then test S3 connection with saved settings
                                static::testS3Connection();
                            })
                            ->visible(fn ($get) => 
                                $get('storage.driver') === 's3' &&
                                filled(setting('storage.s3.key')) &&
                                filled(setting('storage.s3.secret')) &&
                                filled(setting('storage.s3.region')) &&
                                filled(setting('storage.s3.bucket'))
                            ),
                    ]),
                ]),
        ];
    }

    protected static function testS3Connection(): void
    {
        try {
            $s3Config = static::getS3Configuration();
            static::validateS3Configuration($s3Config);
            static::configureS3Settings($s3Config);
            static::testS3Access($s3Config);

            Notification::make()
                ->title('S3 connection test successful!')
                ->body("Successfully connected to bucket: {$s3Config['bucket']}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            static::handleS3Error($e, $s3Config ?? []);
        }
    }

    protected static function getS3Configuration(): array
    {
        $key = setting('storage.s3.key');
        $secret = setting('storage.s3.secret');

        // Decrypt if encrypted
        try {
            if (filled($key)) {
                $key = Crypt::decryptString($key);
            }
            if (filled($secret)) {
                $secret = Crypt::decryptString($secret);
            }
        } catch (\Exception $e) {
            // If decryption fails, assume they're plain text
        }

        return [
            'key' => $key,
            'secret' => $secret,
            'region' => setting('storage.s3.region'),
            'bucket' => setting('storage.s3.bucket'),
        ];
    }

    protected static function validateS3Configuration(array $s3Config): void
    {
        if (empty($s3Config['key']) || empty($s3Config['secret']) || 
            empty($s3Config['region']) || empty($s3Config['bucket'])) {
            throw new \Exception('S3 configuration is incomplete. Please ensure all fields are filled.');
        }

        if (!str_starts_with($s3Config['key'], 'AKIA')) {
            throw new \Exception('AWS Access Key ID should start with "AKIA". Please verify your credentials.');
        }
    }

    protected static function configureS3Settings(array $s3Config): void
    {
        // Temporarily configure S3 for testing
        config([
            'filesystems.disks.s3' => [
                'driver' => 's3',
                'key' => $s3Config['key'],
                'secret' => $s3Config['secret'],
                'region' => $s3Config['region'],
                'bucket' => $s3Config['bucket'],
                'url' => env('AWS_URL'),
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
                'throw' => false,
            ],
        ]);

        \Log::info("S3 test configuration: Bucket: {$s3Config['bucket']}, Region: {$s3Config['region']}, Key: " . substr($s3Config['key'], 0, 8) . "...");
    }

    protected static function testS3Access(array $s3Config): void
    {
        $disk = Storage::disk('s3');
        
        // Skip the existence check and go directly to read/write test
        // This is more reliable than checking bucket existence
        $testFileName = 'openirm-connection-test-' . uniqid() . '.txt';
        $testContent = 'OpenIRM S3 connection test - ' . date('Y-m-d H:i:s');

        try {
            \Log::info("Starting S3 read/write test with file: {$testFileName}");
            
            // Test: Write, read, and delete a test file
            $disk->put($testFileName, $testContent);
            \Log::info("S3 write test successful: {$testFileName}");

            // Verify file was written by reading it back
            if (!$disk->exists($testFileName)) {
                throw new \Exception("Test file was not found after writing");
            }

            $readContent = $disk->get($testFileName);
            if ($readContent !== $testContent) {
                throw new \Exception('Content mismatch: expected "' . $testContent . '", got "' . $readContent . '"');
            }
            \Log::info("S3 read test successful");

            // Clean up test file
            $disk->delete($testFileName);
            
            // Verify cleanup
            // if ($disk->exists($testFileName)) {
            //     \Log::warning("Test file still exists after deletion attempt");
            // } else {
            //     \Log::info("S3 cleanup test successful");
            // }

        } catch (\Exception $e) {
            // Try to clean up test file if it was created
            try {
                if ($disk->exists($testFileName)) {
                    $disk->delete($testFileName);
                    \Log::info("Cleaned up test file after error");
                }
            } catch (\Exception $cleanupError) {
                \Log::warning("Failed to cleanup test file: " . $cleanupError->getMessage());
            }
            
            // Provide more specific error message
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'InvalidAccessKeyId')) {
                throw new \Exception("Invalid AWS Access Key ID. Please verify your credentials.");
            } elseif (str_contains($errorMsg, 'SignatureDoesNotMatch')) {
                throw new \Exception("Invalid AWS Secret Access Key. Please verify your credentials.");
            } elseif (str_contains($errorMsg, 'NoSuchBucket')) {
                throw new \Exception("S3 bucket '{$s3Config['bucket']}' does not exist or is not accessible in region '{$s3Config['region']}'.");
            } elseif (str_contains($errorMsg, 'AccessDenied')) {
                throw new \Exception("Access denied to S3 bucket '{$s3Config['bucket']}'. Please check IAM permissions.");
            } else {
                throw new \Exception("S3 connection test failed: " . $errorMsg);
            }
        }
    }

    protected static function handleS3Error(\Exception $e, array $s3Config): void
    {
        $errorMessage = $e->getMessage();
        
        // Add helpful hints for common S3 errors
        if (str_contains($errorMessage, 'InvalidAccessKeyId') || str_contains($errorMessage, 'SignatureDoesNotMatch')) {
            $errorMessage .= "\n\nS3 troubleshooting:\n• Verify your AWS Access Key ID and Secret Access Key are correct\n• Ensure the IAM user has proper S3 permissions\n• Check that the credentials haven't expired\n• Make sure you're using the correct AWS region";
        } elseif (str_contains($errorMessage, 'NoSuchBucket')) {
            $errorMessage .= "\n\nS3 troubleshooting:\n• Verify the bucket name is correct and exists\n• Ensure the bucket is in the specified region\n• Check that your IAM user has access to this bucket";
        } elseif (str_contains($errorMessage, 'AccessDenied')) {
            $errorMessage .= "\n\nS3 troubleshooting:\n• Your IAM user needs s3:GetObject, s3:PutObject, s3:DeleteObject permissions\n• Check bucket policies that might be restricting access\n• Verify the IAM user has access to the specific bucket path";
        }
        
        Notification::make()
            ->title('S3 connection test failed')
            ->body($errorMessage)
            ->danger()
            ->send();
    }
}
