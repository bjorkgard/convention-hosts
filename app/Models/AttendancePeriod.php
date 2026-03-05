<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePeriod extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'convention_id',
        'date',
        'period',
        'locked',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'locked' => 'boolean',
        ];
    }

    /**
     * Get the convention that owns the attendance period.
     */
    public function convention(): BelongsTo
    {
        return $this->belongsTo(Convention::class);
    }

    /**
     * Get the attendance reports for this period.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(AttendanceReport::class);
    }

    /**
     * Check if the period is active (not locked).
     */
    public function isActive(): bool
    {
        return ! $this->locked;
    }

    /**
     * Get the total attendance for this period.
     */
    public function totalAttendance(): int
    {
        return $this->reports()->sum('attendance');
    }

    /**
     * Get the count of sections that have reported.
     */
    public function reportedSectionsCount(): int
    {
        return $this->reports()->count();
    }
}
