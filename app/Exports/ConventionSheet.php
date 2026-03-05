<?php

namespace App\Exports;

use App\Models\Convention;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ConventionSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected Convention $convention) {}

    public function collection(): Collection
    {
        return collect([
            [
                $this->convention->name,
                $this->convention->city,
                $this->convention->country,
                $this->convention->address ?? '',
                $this->convention->start_date->format('Y-m-d'),
                $this->convention->end_date->format('Y-m-d'),
                $this->convention->other_info ?? '',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Name',
            'City',
            'Country',
            'Address',
            'Start Date',
            'End Date',
            'Additional Information',
        ];
    }

    public function title(): string
    {
        return 'Convention';
    }
}
