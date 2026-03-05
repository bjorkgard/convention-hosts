<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile',
        'password',
        'email_confirmed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'email_confirmed' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the conventions associated with the user.
     */
    public function conventions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Convention::class, 'convention_user')
            ->withPivot('created_at');
    }

    /**
     * Get the floors associated with the user.
     */
    public function floors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Floor::class, 'floor_user')
            ->withPivot('created_at');
    }

    /**
     * Get the sections associated with the user.
     */
    public function sections(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'section_user')
            ->withPivot('created_at');
    }

    /**
     * Get the roles for a specific convention.
     */
    public function rolesForConvention(Convention $convention): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->where('user_id', $this->id)
            ->pluck('role');
    }

    /**
     * Check if the user has a specific role in a convention.
     */
    public function hasRole(Convention $convention, string $role): bool
    {
        return $this->rolesForConvention($convention)->contains($role);
    }

    /**
     * Check if the user has any of the specified roles in a convention.
     */
    public function hasAnyRole(Convention $convention, array $roles): bool
    {
        return $this->rolesForConvention($convention)->intersect($roles)->isNotEmpty();
    }
}
