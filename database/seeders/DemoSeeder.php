<?php

namespace Database\Seeders;

use App\Models\AttendancePeriod;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seed the application with demo data.
     */
    public function run(): void
    {
        // Demo Owner account
        $owner = User::factory()->create([
            'first_name' => 'Demo',
            'last_name' => 'Owner',
            'email' => 'owner@example.com',
            'mobile' => '+1-555-0100',
            'password' => Hash::make('Password1!'),
            'email_confirmed' => true,
        ]);

        // Convention users
        $conventionUser = User::factory()->create([
            'first_name' => 'Convention',
            'last_name' => 'Manager',
            'email' => 'manager@example.com',
            'mobile' => '+1-555-0101',
            'password' => Hash::make('Password1!'),
            'email_confirmed' => true,
        ]);

        $floorUser = User::factory()->create([
            'first_name' => 'Floor',
            'last_name' => 'Supervisor',
            'email' => 'floor@example.com',
            'mobile' => '+1-555-0102',
            'password' => Hash::make('Password1!'),
            'email_confirmed' => true,
        ]);

        $sectionUser = User::factory()->create([
            'first_name' => 'Section',
            'last_name' => 'Attendant',
            'email' => 'section@example.com',
            'mobile' => '+1-555-0103',
            'password' => Hash::make('Password1!'),
            'email_confirmed' => true,
        ]);

        // Create sample convention
        $convention = Convention::create([
            'name' => 'Annual Tech Convention 2026',
            'city' => 'San Francisco',
            'country' => 'United States',
            'address' => '747 Howard St, San Francisco, CA 94103',
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'other_info' => 'A multi-day technology convention with keynotes, workshops, and networking events.',
        ]);

        // Attach all users to convention
        $allUsers = [$owner, $conventionUser, $floorUser, $sectionUser];
        foreach ($allUsers as $user) {
            $convention->users()->attach($user->id);
        }

        // Assign roles
        $roles = [
            [$owner->id, 'Owner'],
            [$owner->id, 'ConventionUser'],
            [$conventionUser->id, 'ConventionUser'],
            [$floorUser->id, 'FloorUser'],
            [$sectionUser->id, 'SectionUser'],
        ];

        foreach ($roles as [$userId, $role]) {
            DB::table('convention_user_roles')->insert([
                'convention_id' => $convention->id,
                'user_id' => $userId,
                'role' => $role,
                'created_at' => now(),
            ]);
        }

        // Create floors
        $mainHall = Floor::create(['convention_id' => $convention->id, 'name' => 'Main Hall']);
        $upperLevel = Floor::create(['convention_id' => $convention->id, 'name' => 'Upper Level']);
        $balcony = Floor::create(['convention_id' => $convention->id, 'name' => 'Balcony']);

        // Assign floor user to Main Hall and Upper Level
        $floorUser->floors()->attach([$mainHall->id, $upperLevel->id]);

        // Create sections for Main Hall
        $sectionA = Section::create([
            'floor_id' => $mainHall->id,
            'name' => 'Section A',
            'number_of_seats' => 200,
            'elder_friendly' => true,
            'handicap_friendly' => true,
            'information' => 'Ground level, wheelchair accessible, near restrooms.',
        ]);

        $sectionB = Section::create([
            'floor_id' => $mainHall->id,
            'name' => 'Section B',
            'number_of_seats' => 150,
            'elder_friendly' => false,
            'handicap_friendly' => false,
        ]);

        $sectionC = Section::create([
            'floor_id' => $mainHall->id,
            'name' => 'Section C',
            'number_of_seats' => 100,
            'elder_friendly' => true,
            'handicap_friendly' => false,
            'information' => 'Quiet zone with padded seating.',
        ]);

        // Create sections for Upper Level
        $sectionD = Section::create([
            'floor_id' => $upperLevel->id,
            'name' => 'Section D',
            'number_of_seats' => 120,
            'elder_friendly' => false,
            'handicap_friendly' => false,
        ]);

        $sectionE = Section::create([
            'floor_id' => $upperLevel->id,
            'name' => 'Section E',
            'number_of_seats' => 80,
            'elder_friendly' => false,
            'handicap_friendly' => true,
            'information' => 'Elevator access available.',
        ]);

        // Create sections for Balcony
        Section::create([
            'floor_id' => $balcony->id,
            'name' => 'Section F',
            'number_of_seats' => 60,
            'elder_friendly' => false,
            'handicap_friendly' => false,
            'information' => 'Stairs only, no elevator access.',
        ]);

        // Assign section user to Section A
        $sectionUser->sections()->attach([$sectionA->id]);

        // Create a locked attendance period with sample reports
        $lockedPeriod = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => now()->addDays(7)->toDateString(),
            'period' => 'morning',
            'locked' => true,
        ]);

        $lockedPeriod->reports()->createMany([
            ['section_id' => $sectionA->id, 'attendance' => 180, 'reported_by' => $sectionUser->id, 'reported_at' => now()],
            ['section_id' => $sectionB->id, 'attendance' => 95, 'reported_by' => $conventionUser->id, 'reported_at' => now()],
            ['section_id' => $sectionC->id, 'attendance' => 72, 'reported_by' => $conventionUser->id, 'reported_at' => now()],
            ['section_id' => $sectionD->id, 'attendance' => 110, 'reported_by' => $floorUser->id, 'reported_at' => now()],
        ]);
    }
}
