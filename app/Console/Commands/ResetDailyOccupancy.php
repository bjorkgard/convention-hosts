<?php

namespace App\Console\Commands;

use App\Models\Section;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDailyOccupancy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-daily-occupancy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset daily occupancy for all sections';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Section::query()->count();

        Section::query()->update([
            'occupancy' => 0,
            'available_seats' => DB::raw('number_of_seats'),
            'last_occupancy_updated_by' => null,
            'last_occupancy_updated_at' => null,
        ]);

        $this->info("Successfully reset occupancy for {$count} sections.");

        return self::SUCCESS;
    }
}
