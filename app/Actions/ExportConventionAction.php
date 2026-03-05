<?php

namespace App\Actions;

use App\Models\Convention;

class ExportConventionAction
{
    /**
     * Export convention data in the specified format.
     *
     * @param  string  $format  The export format: 'xlsx', 'docx', or 'md'
     * @return string The file path for download
     */
    public function execute(Convention $convention, string $format): string
    {
        // Load all related data
        $convention->load([
            'floors.sections.attendanceReports',
            'users',
            'attendancePeriods.reports.section',
            'attendancePeriods.reports.reportedBy',
        ]);

        // Delegate to format-specific exporter
        return match ($format) {
            'xlsx' => $this->exportToExcel($convention),
            'docx' => $this->exportToWord($convention),
            'md' => $this->exportToMarkdown($convention),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
        };
    }

    /**
     * Export convention data to Excel format.
     */
    protected function exportToExcel(Convention $convention): string
    {
        // This will be implemented in Task 5.2 using maatwebsite/excel
        // For now, return a placeholder path
        $filename = storage_path("app/exports/{$convention->id}.xlsx");
        
        // Ensure exports directory exists
        if (! is_dir(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }
        
        // TODO: Implement Excel export using maatwebsite/excel
        // Excel::download(new ConventionExport($convention), $filename);
        
        return $filename;
    }

    /**
     * Export convention data to Word document format.
     */
    protected function exportToWord(Convention $convention): string
    {
        // This will be implemented in Task 5.3 using phpoffice/phpword
        // For now, return a placeholder path
        $filename = storage_path("app/exports/{$convention->id}.docx");
        
        // Ensure exports directory exists
        if (! is_dir(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }
        
        // TODO: Implement Word export using phpoffice/phpword
        
        return $filename;
    }

    /**
     * Export convention data to Markdown format.
     */
    protected function exportToMarkdown(Convention $convention): string
    {
        $filename = storage_path("app/exports/{$convention->id}.md");
        
        // Ensure exports directory exists
        if (! is_dir(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }
        
        // Generate Markdown content
        $markdown = $this->generateMarkdownContent($convention);
        
        // Write to file
        file_put_contents($filename, $markdown);
        
        return $filename;
    }

    /**
     * Generate Markdown content for the convention.
     */
    protected function generateMarkdownContent(Convention $convention): string
    {
        $markdown = "# {$convention->name}\n\n";
        $markdown .= "**Location:** {$convention->city}, {$convention->country}\n";
        $markdown .= "**Dates:** {$convention->start_date->format('Y-m-d')} to {$convention->end_date->format('Y-m-d')}\n\n";
        
        if ($convention->address) {
            $markdown .= "**Address:** {$convention->address}\n\n";
        }
        
        if ($convention->other_info) {
            $markdown .= "**Additional Information:**\n{$convention->other_info}\n\n";
        }
        
        // Floors & Sections
        $markdown .= "## Floors & Sections\n\n";
        foreach ($convention->floors as $floor) {
            $markdown .= "### {$floor->name}\n\n";
            $markdown .= "| Section | Seats | Occupancy | Available | Elder Friendly | Handicap Friendly |\n";
            $markdown .= "|---------|-------|-----------|-----------|----------------|-------------------|\n";
            
            foreach ($floor->sections as $section) {
                $markdown .= "| {$section->name} | {$section->number_of_seats} | ";
                $markdown .= "{$section->occupancy}% | {$section->available_seats} | ";
                $markdown .= ($section->elder_friendly ? 'Yes' : 'No')." | ";
                $markdown .= ($section->handicap_friendly ? 'Yes' : 'No')." |\n";
            }
            $markdown .= "\n";
        }
        
        // Attendance History
        $markdown .= "## Attendance History\n\n";
        if ($convention->attendancePeriods->isNotEmpty()) {
            $markdown .= "| Date | Period | Floor | Section | Attendance | Reported By | Reported At |\n";
            $markdown .= "|------|--------|-------|---------|------------|-------------|-------------|\n";
            
            foreach ($convention->attendancePeriods as $period) {
                foreach ($period->reports as $report) {
                    $markdown .= "| {$period->date->format('Y-m-d')} | {$period->period} | ";
                    $markdown .= "{$report->section->floor->name} | {$report->section->name} | ";
                    $markdown .= "{$report->attendance} | ";
                    $markdown .= "{$report->reportedBy->first_name} {$report->reportedBy->last_name} | ";
                    $markdown .= "{$report->reported_at->format('Y-m-d H:i:s')} |\n";
                }
            }
        } else {
            $markdown .= "*No attendance history available.*\n";
        }
        $markdown .= "\n";
        
        // Users
        $markdown .= "## Users\n\n";
        $markdown .= "| Name | Email | Roles |\n";
        $markdown .= "|------|-------|-------|\n";
        
        foreach ($convention->users as $user) {
            $roles = $user->rolesForConvention($convention)->join(', ');
            $markdown .= "| {$user->first_name} {$user->last_name} | {$user->email} | {$roles} |\n";
        }
        
        return $markdown;
    }
}
