<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'convention_id',
        'name',
    ];

    /**
     * Get the convention that owns the floor.
     */
    public function convention(): BelongsTo
    {
        return $this->belongsTo(Convention::class);
    }

    /**
     * Get the sections for the floor.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Get the users associated with the floor.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'floor_user')
            ->withPivot('created_at');
    }
}
