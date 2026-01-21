<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
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
            'password' => 'hashed',
            'timezone' => 'string',
        ];
    }

    /**
     * Get the user's timezone or default to UTC
     */
    public function getTimezoneAttribute($value)
    {
        return $value ?: config('app.timezone', 'UTC');
    }

    /**
     * Convert datetime to user's timezone
     */
    public function convertToUserTimezone($datetime)
    {
        if (!$datetime) return null;
        
        return $datetime->setTimezone($this->timezone);
    }

    /**
     * Convert datetime from user's timezone to UTC
     */
    public function convertFromUserTimezone($datetime)
    {
        if (!$datetime) return null;
        
        return $datetime->setTimezone('UTC');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole($roleSlug)
    {
        if (is_string($roleSlug)) {
            return $this->roles->contains('slug', $roleSlug);
        }
        
        return $this->roles->pluck('slug')->intersect($roleSlug)->count() > 0;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isTenant()
    {
        return $this->hasRole('tenant');
    }

    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        
        return false;
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function engagements()
    {
        return $this->hasMany(Engagement::class);
    }

    public function automationRules()
    {
        return $this->hasMany(AutomationRule::class);
    }

    // Relationship with businesses
    public function businesses()
    {
        return $this->belongsToMany(Business::class, 'business_user')
                    ->withPivot('role', 'is_primary')
                    ->withTimestamps();
    }

    // Get user's primary business
    public function getPrimaryBusiness()
    {
        return $this->businesses()->wherePivot('is_primary', true)->first();
    }

    // Check if user has a specific role in a business
    public function hasBusinessRole($businessId, $role)
    {
        $pivot = $this->businesses()->where('business_id', $businessId)->first()?->pivot;
        return $pivot ? $pivot->role === $role : false;
    }

    // Check if user is admin of a business
    public function isBusinessAdmin($businessId)
    {
        return $this->hasBusinessRole($businessId, 'admin');
    }

    // Check if user is member of a business
    public function isBusinessMember($businessId)
    {
        $pivot = $this->businesses()->where('business_id', $businessId)->first()?->pivot;
        return $pivot !== null;
    }


}