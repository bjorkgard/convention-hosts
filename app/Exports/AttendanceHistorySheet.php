<?php

namespace App\Exports;

use App\Models\Convention;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;

class AttendanceHistorySheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected Convention $convention)
    {
    }

    public function collection(): Collection
    {
        $rows = collect();

        foreach ($this->convention->attendancePeriods as $period) {
            foreach ($period->reports as $report) {
                $rows->push([
                    $period->date->format('Y-m-d'),
                    ucfirst($period->period),
                    $period->locked ? 'Yes' : 'No',
                    $report->section->floor->name,
                    $report->section->name,
                    $report->attendance,
                    $report->reportedBy->first_name.' '.$report->reportedBy->last_name,
                    $report->reported_at->format('Y-m-d H:i:s'),
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Period',
            'Locked',
            'Floor',
            'Section',
            'Attendance',
            'Reported By',
            'Reported At',
        ];
    }

    public function title(): string
    {
        return 'Attendance History';
    }
}
