<?php

namespace App\Exports;

use App\Models\Convention;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class UsersSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected Convention $convention) {}

    public function collection(): Collection
    {
        $rows = collect();

        foreach ($this->convention->users as $user) {
            $roles = $user->rolesForConvention($this->convention)->pluck('role')->join(', ');

            // Get assigned floors if FloorUser
            $assignedFloors = '';
            if ($user->hasRole($this->convention, 'FloorUser')) {
                $assignedFloors = $user->floors()
                    ->whereHas('convention', fn ($q) => $q->where('id', $this->convention->id))
                    ->pluck('name')
                    ->join(', ');
            }

            // Get assigned sections if SectionUser
            $assignedSections = '';
            if ($user->hasRole($this->convention, 'SectionUser')) {
                $assignedSections = $user->sections()
                    ->whereHas('floor.convention', fn ($q) => $q->where('id', $this->convention->id))
                    ->pluck('name')
                    ->join(', ');
            }

            $rows->push([
                $user->first_name.' '.$user->last_name,
                $user->email,
                $user->mobile,
                $user->email_confirmed ? 'Yes' : 'No',
                $roles,
                $assignedFloors,
                $assignedSections,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Mobile',
            'Email Confirmed',
            'Roles',
            'Assigned Floors',
            'Assigned Sections',
        ];
    }

    public function title(): string
    {
        return 'Users';
    }
}
