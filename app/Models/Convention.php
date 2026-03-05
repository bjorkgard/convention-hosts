<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Convention extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'city',
        'country',
        'address',
        'start_date',
        'end_date',
        'other_info',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Get the floors for the convention.
     */
    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    /**
     * Get the users associated with the convention.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'convention_user')
            ->withPivot('created_at');
    }

    /**
     * Get the attendance periods for the convention.
     */
    public function attendancePeriods(): HasMany
    {
        return $this->hasMany(AttendancePeriod::class);
    }

    /**
     * Get the roles for a specific user in this convention.
     */
    public function userRoles(User $user): Collection
    {
        return $this->belongsToMany(User::class, 'convention_user_roles')
            ->wherePivot('user_id', $user->id)
            ->pluck('role');
    }

    /**
     * Check if a user has a specific role in this convention.
     */
    public function hasRole(User $user, string $role): bool
    {
        return $this->userRoles($user)->contains($role);
    }

    /**
     * Check if a user has any of the specified roles in this convention.
     */
    public function hasAnyRole(User $user, array $roles): bool
    {
        return $this->userRoles($user)->intersect($roles)->isNotEmpty();
    }
}
