<?php

namespace App\Models;

use App\Enums\ResponseStatus;
use App\Traits\Concerns\HasSuperAdmin;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, HasSuperAdmin, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'institution_id',
        'code',
    ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'last_activity',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_activity' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted()
    {
        // static::saving(function ($user) {
        //     if ($user->isDirty('last_activity')) {
        //         Log::debug('Attempt to update last_activity through model save', [
        //             'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
        //             'dirty' => $user->getDirty()
        //         ]);
        //         // Prevent the update of last_activity through normal model operations
        //         $user->last_activity = $user->getOriginal('last_activity');
        //     }
        // });

        // static::updating(function ($user) {
        //     if ($user->isDirty('last_activity')) {
        //         Log::debug('Attempt to update last_activity through model update', [
        //             'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
        //             'dirty' => $user->getDirty()
        //         ]);
        //         // Prevent the update of last_activity through normal model operations
        //         $user->last_activity = $user->getOriginal('last_activity');
        //     }
        // });
        // --- ADD THIS LOGIC ---
        // It will run every time a new user is being created.
        static::creating(function ($user) {
            if (empty($user->code)) {
                // Generate a unique code, e.g., USR-A8B2C1
                $user->code = 'USR-' . strtoupper(Str::random(6));
            }
        });
        static::deleting(function (self $user) {
            // only run on soft-delete (not forceDelete)
            if (! $user->isForceDeleting()) {
                $newEmail = 'deleted_' . $user->id . '_' . time() . '@removed.local';
                DB::table('users')->where('id', $user->id)->update([
                    'email' => $newEmail,
                ]);
            }
        });
    }

    /**
     * Update the user's last activity timestamp.
     *
     * @return void
     */
    public function updateLastActivity(): void
    {
        DB::table('users')
            ->where('id', $this->id)
            ->update(['last_activity' => now()]);

    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function audits(): BelongsToMany
    {
        return $this->belongsToMany(Audit::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(DataRequestResponse::class, 'requestee_id');
    }

    public function openTodos(): HasMany
    {
        return $this->hasMany(DataRequestResponse::class, 'requestee_id')
            ->whereIn('status', [ResponseStatus::PENDING, ResponseStatus::REJECTED]);
    }

    public function managedPrograms(): HasMany
    {
        return $this->hasMany(Program::class, 'program_manager_id');
    }
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
