<?php

namespace Database\Factories;

use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceReport>
 */
class AttendanceReportFactory extends Factory
{
    protected $model = AttendanceReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_period_id' => AttendancePeriod::factory(),
            'section_id' => Section::factory(),
            'attendance' => $this->faker->numberBetween(0, 200),
            'reported_by' => User::factory(),
            'reported_at' => now(),
        ];
    }
}
