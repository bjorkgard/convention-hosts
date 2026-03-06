<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'floor_id',
        'name',
        'number_of_seats',
        'occupancy',
        'available_seats',
        'elder_friendly',
        'handicap_friendly',
        'information',
        'last_occupancy_updated_by',
        'last_occupancy_updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occupancy' => 'integer',
            'available_seats' => 'integer',
            'number_of_seats' => 'integer',
            'elder_friendly' => 'boolean',
            'handicap_friendly' => 'boolean',
            'last_occupancy_updated_at' => 'datetime',
        ];
    }

    /**
     * Get the floor that owns the section.
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * Get the users associated with the section.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'section_user')
            ->withPivot('created_at');
    }

    /**
     * Get the user who last updated the occupancy.
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_occupancy_updated_by');
    }

    /**
     * Get the attendance reports for the section.
     */
    public function attendanceReports(): HasMany
    {
        return $this->hasMany(AttendanceReport::class);
    }

    /**
     * Scope: sections with occupancy below 90% (available for search).
     */
    public function scopeAvailable($query)
    {
        return $query->where('occupancy', '<', 90);
    }

}
