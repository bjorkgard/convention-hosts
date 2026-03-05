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
        $filename = "exports/{$convention->id}.xlsx";
        
        // Generate Excel file using maatwebsite/excel
        \Maatwebsite\Excel\Facades\Excel::store(
            new \App\Exports\ConventionExport($convention),
            $filename,
            'local'
        );
        
        // Return the actual path where the file is stored
        return storage_path("app/private/{$filename}");
    }

    /**
     * Export convention data to Word document format.
     */
    protected function exportToWord(Convention $convention): string
    {
        $exporter = new \App\Exports\ConventionWordExport($convention);
        return $exporter->generate();
    }

    /**
     * Export convention data to Markdown format.
     */
    protected function exportToMarkdown(Convention $convention): string
    {
        $exporter = new \App\Exports\ConventionMarkdownExport($convention);
        return $exporter->generate();
    }
}
