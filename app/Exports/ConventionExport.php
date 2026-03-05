<?php

namespace App\Exports;

use App\Models\Convention;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ConventionExport implements WithMultipleSheets
{
    public function __construct(protected Convention $convention)
    {
        // Load all related data
        $this->convention->load([
            'floors.sections',
            'users',
            'attendancePeriods.reports.section.floor',
            'attendancePeriods.reports.reportedBy',
        ]);
    }

    public function sheets(): array
    {
        return [
            new ConventionSheet($this->convention),
            new FloorsAndSectionsSheet($this->convention),
            new AttendanceHistorySheet($this->convention),
            new UsersSheet($this->convention),
        ];
    }
}
