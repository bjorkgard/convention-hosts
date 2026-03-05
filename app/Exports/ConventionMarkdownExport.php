<?php

namespace App\Exports;

use App\Models\Convention;

class ConventionMarkdownExport
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
        $filename = storage_path("app/private/exports/{$this->convention->id}.md");
        
        // Ensure exports directory exists
        if (! is_dir(storage_path('app/private/exports'))) {
            mkdir(storage_path('app/private/exports'), 0755, true);
        }
        
        // Generate Markdown content
        $markdown = $this->generateContent();
        
        // Write to file
        file_put_contents($filename, $markdown);
        
        return $filename;
    }

    protected function generateContent(): string
    {
        $markdown = "# {$this->convention->name}\n\n";
        $markdown .= "**Location:** {$this->convention->city}, {$this->convention->country}\n";
        $markdown .= "**Dates:** {$this->convention->start_date->format('Y-m-d')} to {$this->convention->end_date->format('Y-m-d')}\n\n";
        
        if ($this->convention->address) {
            $markdown .= "**Address:** {$this->convention->address}\n\n";
        }
        
        if ($this->convention->other_info) {
            $markdown .= "**Additional Information:**\n\n{$this->convention->other_info}\n\n";
        }
        
        // Floors & Sections
        $markdown .= "## Floors & Sections\n\n";
        
        if ($this->convention->floors->isEmpty()) {
            $markdown .= "*No floors available.*\n\n";
        } else {
            foreach ($this->convention->floors as $floor) {
                $markdown .= "### {$floor->name}\n\n";
                
                if ($floor->sections->isEmpty()) {
                    $markdown .= "*No sections in this floor.*\n\n";
                    continue;
                }
                
                $markdown .= "| Section | Total Seats | Occupancy | Available Seats | Elder Friendly | Handicap Friendly |\n";
                $markdown .= "|---------|-------------|-----------|-----------------|----------------|-------------------|\n";
                
                foreach ($floor->sections as $section) {
                    $markdown .= "| {$section->name} | {$section->number_of_seats} | ";
                    $markdown .= "{$section->occupancy}% | {$section->available_seats} | ";
                    $markdown .= ($section->elder_friendly ? 'Yes' : 'No')." | ";
                    $markdown .= ($section->handicap_friendly ? 'Yes' : 'No')." |\n";
                }
                $markdown .= "\n";
            }
        }
        
        // Attendance History
        $markdown .= "## Attendance History\n\n";
        
        if ($this->convention->attendancePeriods->isEmpty()) {
            $markdown .= "*No attendance history available.*\n\n";
        } else {
            $markdown .= "| Date | Period | Locked | Floor | Section | Attendance | Reported By | Reported At |\n";
            $markdown .= "|------|--------|--------|-------|---------|------------|-------------|-------------|\n";
            
            foreach ($this->convention->attendancePeriods as $period) {
                foreach ($period->reports as $report) {
                    $markdown .= "| {$period->date->format('Y-m-d')} | ".ucfirst($period->period)." | ";
                    $markdown .= ($period->locked ? 'Yes' : 'No')." | ";
                    $markdown .= "{$report->section->floor->name} | {$report->section->name} | ";
                    $markdown .= "{$report->attendance} | ";
                    $markdown .= "{$report->reportedBy->first_name} {$report->reportedBy->last_name} | ";
                    $markdown .= "{$report->reported_at->format('Y-m-d H:i:s')} |\n";
                }
            }
            $markdown .= "\n";
        }
        
        // Users
        $markdown .= "## Users\n\n";
        
        if ($this->convention->users->isEmpty()) {
            $markdown .= "*No users assigned to this convention.*\n\n";
        } else {
            $markdown .= "| Name | Email | Mobile | Email Confirmed | Roles |\n";
            $markdown .= "|------|-------|--------|-----------------|-------|\n";
            
            foreach ($this->convention->users as $user) {
                $roles = $user->rolesForConvention($this->convention)->pluck('role')->join(', ');
                $markdown .= "| {$user->first_name} {$user->last_name} | {$user->email} | ";
                $markdown .= "{$user->mobile} | ".($user->email_confirmed ? 'Yes' : 'No')." | ";
                $markdown .= "{$roles} |\n";
            }
        }
        
        return $markdown;
    }
}
