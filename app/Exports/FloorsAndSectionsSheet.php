<?php

namespace App\Exports;

use App\Models\Convention;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class FloorsAndSectionsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected Convention $convention) {}

    public function collection(): Collection
    {
        $rows = collect();

        foreach ($this->convention->floors as $floor) {
            foreach ($floor->sections as $section) {
                $rows->push([
                    $floor->name,
                    $section->name,
                    $section->number_of_seats,
                    $section->occupancy.'%',
                    $section->available_seats,
                    $section->elder_friendly ? 'Yes' : 'No',
                    $section->handicap_friendly ? 'Yes' : 'No',
                    $section->information ?? '',
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Floor',
            'Section',
            'Total Seats',
            'Current Occupancy',
            'Available Seats',
            'Elder Friendly',
            'Handicap Friendly',
            'Information',
        ];
    }

    public function title(): string
    {
        return 'Floors & Sections';
    }
}
