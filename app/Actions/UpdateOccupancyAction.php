<?php

namespace App\Actions;

use App\Models\Section;
use App\Models\User;

class UpdateOccupancyAction
{
    /**
     * Update section occupancy and available seats.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Section $section, array $data, User $user): Section
    {
        // Calculate occupancy percentage if available_seats provided
        if (isset($data['available_seats'])) {
            $availableSeats = (int) $data['available_seats'];

            // Calculate raw occupancy: 100 - ((available_seats / number_of_seats) * 100)
            $rawOccupancy = 100 - (($availableSeats / $section->number_of_seats) * 100);
            $rawOccupancy = max(0, min(100, $rawOccupancy));

            // Snap to closest dropdown option
            $options = [0, 10, 25, 50, 75, 100];
            $occupancy = $options[0];
            $minDiff = abs($rawOccupancy - $options[0]);
            foreach ($options as $option) {
                $diff = abs($rawOccupancy - $option);
                if ($diff < $minDiff) {
                    $minDiff = $diff;
                    $occupancy = $option;
                }
            }

            $section->occupancy = $occupancy;
            $section->available_seats = $availableSeats;
        }
        // Calculate available_seats if occupancy percentage provided
        elseif (isset($data['occupancy'])) {
            $occupancy = (int) $data['occupancy'];

            // Calculate available seats from occupancy percentage
            $availableSeats = $section->number_of_seats * (1 - ($occupancy / 100));

            // Round and ensure non-negative
            $availableSeats = max(0, round($availableSeats));

            $section->occupancy = $occupancy;
            $section->available_seats = (int) $availableSeats;
        }

        // Record metadata
        $section->last_occupancy_updated_by = $user->id;
        $section->last_occupancy_updated_at = now();

        // Save the section
        $section->save();

        return $section->fresh();
    }
}
