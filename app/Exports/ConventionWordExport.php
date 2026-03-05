<?php

namespace App\Exports;

use App\Models\Convention;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Font;

class ConventionWordExport
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

    public function generate(): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Title
        $section->addTitle($this->convention->name, 1);
        $section->addTextBreak(1);

        // Convention Details
        $this->addConventionDetails($section);
        $section->addTextBreak(2);

        // Floors & Sections
        $this->addFloorsAndSections($section);
        $section->addTextBreak(2);

        // Attendance History
        $this->addAttendanceHistory($section);
        $section->addTextBreak(2);

        // Users
        $this->addUsers($section);

        // Save to file
        $filename = storage_path("app/private/exports/{$this->convention->id}.docx");
        
        // Ensure exports directory exists
        if (! is_dir(storage_path('app/private/exports'))) {
            mkdir(storage_path('app/private/exports'), 0755, true);
        }
        
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filename);

        return $filename;
    }

    protected function addConventionDetails($section): void
    {
        $section->addTitle('Convention Details', 2);
        
        $section->addText("Location: {$this->convention->city}, {$this->convention->country}");
        $section->addText("Dates: {$this->convention->start_date->format('Y-m-d')} to {$this->convention->end_date->format('Y-m-d')}");
        
        if ($this->convention->address) {
            $section->addText("Address: {$this->convention->address}");
        }
        
        if ($this->convention->other_info) {
            $section->addText("Additional Information:");
            $section->addText($this->convention->other_info);
        }
    }

    protected function addFloorsAndSections($section): void
    {
        $section->addTitle('Floors & Sections', 2);

        if ($this->convention->floors->isEmpty()) {
            $section->addText('No floors available.');
            return;
        }

        foreach ($this->convention->floors as $floor) {
            $section->addTitle($floor->name, 3);

            if ($floor->sections->isEmpty()) {
                $section->addText('No sections in this floor.');
                continue;
            }

            // Create table
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '999999',
                'cellMargin' => 80,
            ]);

            // Header row
            $table->addRow();
            $table->addCell(2000)->addText('Section', ['bold' => true]);
            $table->addCell(1500)->addText('Total Seats', ['bold' => true]);
            $table->addCell(1500)->addText('Occupancy', ['bold' => true]);
            $table->addCell(1500)->addText('Available', ['bold' => true]);
            $table->addCell(1500)->addText('Elder Friendly', ['bold' => true]);
            $table->addCell(1500)->addText('Handicap Friendly', ['bold' => true]);

            // Data rows
            foreach ($floor->sections as $section_item) {
                $table->addRow();
                $table->addCell(2000)->addText($section_item->name);
                $table->addCell(1500)->addText((string) $section_item->number_of_seats);
                $table->addCell(1500)->addText($section_item->occupancy.'%');
                $table->addCell(1500)->addText((string) $section_item->available_seats);
                $table->addCell(1500)->addText($section_item->elder_friendly ? 'Yes' : 'No');
                $table->addCell(1500)->addText($section_item->handicap_friendly ? 'Yes' : 'No');
            }

            $section->addTextBreak(1);
        }
    }

    protected function addAttendanceHistory($section): void
    {
        $section->addTitle('Attendance History', 2);

        if ($this->convention->attendancePeriods->isEmpty()) {
            $section->addText('No attendance history available.');
            return;
        }

        // Create table
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80,
        ]);

        // Header row
        $table->addRow();
        $table->addCell(1500)->addText('Date', ['bold' => true]);
        $table->addCell(1500)->addText('Period', ['bold' => true]);
        $table->addCell(1000)->addText('Locked', ['bold' => true]);
        $table->addCell(1500)->addText('Floor', ['bold' => true]);
        $table->addCell(1500)->addText('Section', ['bold' => true]);
        $table->addCell(1200)->addText('Attendance', ['bold' => true]);
        $table->addCell(2000)->addText('Reported By', ['bold' => true]);
        $table->addCell(2000)->addText('Reported At', ['bold' => true]);

        // Data rows
        foreach ($this->convention->attendancePeriods as $period) {
            foreach ($period->reports as $report) {
                $table->addRow();
                $table->addCell(1500)->addText($period->date->format('Y-m-d'));
                $table->addCell(1500)->addText(ucfirst($period->period));
                $table->addCell(1000)->addText($period->locked ? 'Yes' : 'No');
                $table->addCell(1500)->addText($report->section->floor->name);
                $table->addCell(1500)->addText($report->section->name);
                $table->addCell(1200)->addText((string) $report->attendance);
                $table->addCell(2000)->addText($report->reportedBy->first_name.' '.$report->reportedBy->last_name);
                $table->addCell(2000)->addText($report->reported_at->format('Y-m-d H:i:s'));
            }
        }
    }

    protected function addUsers($section): void
    {
        $section->addTitle('Users', 2);

        if ($this->convention->users->isEmpty()) {
            $section->addText('No users assigned to this convention.');
            return;
        }

        // Create table
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80,
        ]);

        // Header row
        $table->addRow();
        $table->addCell(2500)->addText('Name', ['bold' => true]);
        $table->addCell(2500)->addText('Email', ['bold' => true]);
        $table->addCell(1500)->addText('Mobile', ['bold' => true]);
        $table->addCell(1500)->addText('Email Confirmed', ['bold' => true]);
        $table->addCell(2000)->addText('Roles', ['bold' => true]);

        // Data rows
        foreach ($this->convention->users as $user) {
            $roles = $user->rolesForConvention($this->convention)->pluck('role')->join(', ');
            
            $table->addRow();
            $table->addCell(2500)->addText($user->first_name.' '.$user->last_name);
            $table->addCell(2500)->addText($user->email);
            $table->addCell(1500)->addText($user->mobile);
            $table->addCell(1500)->addText($user->email_confirmed ? 'Yes' : 'No');
            $table->addCell(2000)->addText($roles);
        }
    }
}
