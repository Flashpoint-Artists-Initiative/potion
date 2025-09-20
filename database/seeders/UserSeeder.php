<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\UniqueConstraintViolationException;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment(['local', 'testing', 'development'])) {
            try {
                User::factory()->unverified()->create([
                    'legal_name' => 'Unverified User',
                    'preferred_name' => 'Unverified Name',
                    'email' => 'unverified@example.com',
                    'password' => 'unverified',
                ]);

                User::factory()->create([
                    'legal_name' => 'Regular User',
                    'email' => 'regular@example.com',
                    'password' => 'regular',
                ]);

                User::factory()->create([
                    'legal_name' => 'Regular Admin User',
                    'email' => 'admin@example.com',
                    'password' => 'admin',
                ])->assignRole(RolesEnum::Admin);

                User::factory()->create([
                    'legal_name' => 'Event Manager User',
                    'preferred_name' => 'Event Manager Name',
                    'email' => 'eventmanager@example.com',
                    'password' => 'eventmanager',
                ])->assignRole(RolesEnum::EventManager);

                User::factory()->create([
                    'legal_name' => 'Box Office User',
                    'email' => 'boxoffice@example.com',
                    'password' => 'boxoffice',
                ])->assignRole(RolesEnum::BoxOffice);

                User::factory()->create([
                    'legal_name' => 'Art Grant Reviewer User',
                    'email' => 'artgrants@example.com',
                    'password' => 'artgrants',
                ])->assignRole(RolesEnum::ArtGrantReviewer);

                User::factory()->create([
                    'legal_name' => 'Volunteer Coordinator User',
                    'email' => 'volunteer@example.com',
                    'password' => 'volunteer',
                ])->assignRole(RolesEnum::VolunteerCoordinator);

                User::factory()->create([
                    'legal_name' => 'Gate Staff User',
                    'email' => 'gatestaff@example.com',
                    'password' => 'gatestaff',
                ])->assignRole(RolesEnum::GateStaff);
            } catch (UniqueConstraintViolationException $e) {
                // Catch this exception so we can seed data locally as much as we want
            }
        }
    }
}
