<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'login',
        'email',
        'phone',
        'password',
        'failed_login_attempts',
        'locked_until',
        'last_login_ip',
        'last_login_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_enabled',
        'two_factor_enabled_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_enabled_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the businesses owned by the user
     */
    public function businesses()
    {
        return $this->hasMany(\App\Models\Business::class);
    }

    /**
     * Get the businesses the user is a member of
     */
    public function teamBusinesses()
    {
        return $this->belongsToMany(\App\Models\Business::class, 'business_user')
            ->withPivot('role', 'permissions', 'invited_at', 'accepted_at')
            ->withTimestamps();
    }

    /**
     * Get the user's settings
     */
    public function settings()
    {
        return $this->hasOne(\App\Models\UserSetting::class);
    }

    /**
     * Get user's role in a specific business
     */
    public function getRoleInBusiness($businessId): ?string
    {
        $pivot = $this->teamBusinesses()->where('business_id', $businessId)->first();
        return $pivot ? $pivot->pivot->role : null;
    }

    /**
     * Check if user has permission in a business
     */
    public function hasPermission($businessId, $permission): bool
    {
        $role = $this->getRoleInBusiness($businessId);

        if (!$role) {
            return false;
        }

        // Permission matrix based on roles
        $permissions = [
            'owner' => [
                'view:dashboard', 'view:analytics', 'manage:leads', 'manage:customers',
                'manage:orders', 'manage:chatbot', 'view:reports', 'generate:reports',
                'manage:integrations', 'manage:team', 'manage:billing', 'delete:business'
            ],
            'admin' => [
                'view:dashboard', 'view:analytics', 'manage:leads', 'manage:customers',
                'manage:orders', 'manage:chatbot', 'view:reports', 'generate:reports',
                'manage:integrations', 'manage:team'
            ],
            'manager' => [
                'view:dashboard', 'view:analytics', 'manage:leads', 'manage:customers',
                'view:reports', 'generate:reports', 'manage:chatbot'
            ],
            'member' => [
                'view:dashboard', 'view:analytics', 'manage:leads', 'manage:customers',
                'view:reports'
            ],
            'viewer' => [
                'view:dashboard', 'view:analytics', 'view:reports'
            ],
        ];

        return in_array($permission, $permissions[$role] ?? []);
    }

    /**
     * Check if user is owner of a business
     */
    public function isOwnerOf($businessId): bool
    {
        return $this->getRoleInBusiness($businessId) === 'owner';
    }

    /**
     * Check if user is admin or owner of a business
     */
    public function isAdminOf($businessId): bool
    {
        $role = $this->getRoleInBusiness($businessId);
        return in_array($role, ['owner', 'admin']);
    }

    /**
     * Get all businesses (owned + member of)
     */
    public function allBusinesses()
    {
        return $this->businesses()->get()->merge($this->teamBusinesses);
    }

    /**
     * Get the current business from session or first available
     */
    public function getCurrentBusinessAttribute()
    {
        $currentBusinessId = session('current_business_id');

        if ($currentBusinessId) {
            // Try to find in owned businesses
            $business = $this->businesses()->find($currentBusinessId);
            if ($business) {
                return $business;
            }

            // Try to find in team businesses
            $business = $this->teamBusinesses()->find($currentBusinessId);
            if ($business) {
                return $business;
            }
        }

        // Fallback to first owned business
        $firstOwned = $this->businesses()->first();
        if ($firstOwned) {
            session(['current_business_id' => $firstOwned->id]);
            return $firstOwned;
        }

        // Fallback to first team business
        $firstTeam = $this->teamBusinesses()->first();
        if ($firstTeam) {
            session(['current_business_id' => $firstTeam->id]);
            return $firstTeam;
        }

        return null;
    }

    /**
     * Set the current business
     */
    public function setCurrentBusiness($businessId)
    {
        session(['current_business_id' => $businessId]);
    }
}
