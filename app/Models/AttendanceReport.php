<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceReport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'attendance_period_id',
        'section_id',
        'attendance',
        'reported_by',
        'reported_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance' => 'integer',
            'reported_at' => 'datetime',
        ];
    }

    /**
     * Get the attendance period that owns the report.
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class, 'attendance_period_id');
    }

    /**
     * Get the section that this report is for.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the user who reported the attendance.
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
