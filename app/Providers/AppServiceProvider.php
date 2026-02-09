<?php

namespace App\Providers;

use App\Models\User;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::unguard();

        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }

        $isInstaller = false;
        if ($this->app->runningInConsole()) {
            $argv = $_SERVER['argv'] ?? [];
            if (isset($argv[1]) && in_array($argv[1], [
                'opengrc:install',
                'opengrc:deploy',
                'package:discover',
                'filament:upgrade',
                'vendor:publish',
            ], true)) {
                $isInstaller = true;
            }
        }

        if (! $isInstaller) {
            if (Schema::hasTable('settings')) {

                Config::set('app.name', setting('general.name', 'OpenGRC'));
                Config::set('app.url', setting('general.url', 'https://opengrc.test'));

                // Only override mail config from DB in non-local environments
                if (! $this->app->environment('local')) {
                    $normalize = static fn ($v) => in_array($v, [null, '', 'null'], true) ? null : $v;

                    $host = $normalize(setting('mail.host'));
                    $port = (int) ($normalize(setting('mail.port')) ?? 587);

                    $encryption = $normalize(setting('mail.encryption'));
                    $encryption = is_string($encryption) ? strtolower($encryption) : $encryption;
                    if ($encryption === 'none') {
                        $encryption = null;
                    } elseif ($encryption === 'starttls') {
                        $encryption = 'tls';
                    }

                    $username = $normalize(setting('mail.username'));
                    $password = $normalize(setting('mail.password'));

                    // Avoid seeded placeholder creds
                    if ($username === 'username' && $password === 'password') {
                        $username = null;
                        $password = null;
                    }

                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), [
                            'transport' => 'smtp',
                            'host' => $host,
                            'port' => $port,
                            'encryption' => $encryption,
                            'username' => $username,
                            'password' => $password,
                        ]),
                        'mail.from' => [
                            'address' => $normalize(setting('mail.from')) ?? config('mail.from.address'),
                            'name' => setting('general.name', config('app.name')),
                        ],
                    ]);

                    app()->forgetInstance('mail.manager');
                }

                // Configure filesystem based on settings
                $storageDriver = setting('storage.driver', 'private');

                config()->set('filesystems.disks.local', array_merge(config('filesystems.disks.local', []), [
                    'driver' => 'private',
                    'root' => storage_path('app'),
                    'throw' => false,
                ]));

                if ($storageDriver === 's3') {
                    $s3Key = setting('storage.s3.key');
                    $s3Secret = setting('storage.s3.secret');

                    try {
                        if (! empty($s3Key)) {
                            $s3Key = Crypt::decryptString($s3Key);
                        }
                        if (! empty($s3Secret)) {
                            $s3Secret = Crypt::decryptString($s3Secret);
                        }
                        config()->set('filesystems.disks.s3', array_merge(config('filesystems.disks.s3', []), [
                            'driver' => 's3',
                            'key' => $s3Key,
                            'secret' => $s3Secret,
                            'region' => setting('storage.s3.region', 'us-east-1'),
                            'bucket' => setting('storage.s3.bucket'),
                            'use_path_style_endpoint' => false,
                        ]));
                    } catch (\Exception $e) {
                        \Log::error('Failed to decrypt S3 credentials: ' . $e->getMessage());
                        $storageDriver = 'private';
                    }
                }

                config()->set('filesystems.default', $storageDriver);
                Config::set('session.lifetime', setting('security.session_timeout', 15));

            } else {
                abort(500, 'OpenGRC was not installed properly. Please review the installation guide at https://docs.opengrc.com to install the app.');
            }
        }

        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch->locales(['en', 'es', 'fr', 'hr']);
        });

        FilamentColor::register([
            'bg-grcblue' => [
                50 => '#eaf3f7',
                100 => '#d4e7ef',
                200 => '#a9cfe0',
                300 => '#7eb7d1',
                400 => '#1375a0',
                500 => '#106689',
                600 => '#0d5773',
                700 => '#0a485d',
                800 => '#374151',
                900 => '#212a3a',
            ],
            'danger' => [
                50 => '254, 242, 242',
                100 => '254, 226, 226',
                200 => '254, 202, 202',
                300 => '252, 165, 165',
                400 => '248, 113, 113',
                500 => '239, 68, 68',
                600 => '220, 38, 38',
                700 => '185, 28, 28',
                800 => '153, 27, 27',
                900 => '127, 29, 29',
                950 => '69, 10, 10',
            ],
        ]);
    }

    public function register(): void
    {
        //
    }
}