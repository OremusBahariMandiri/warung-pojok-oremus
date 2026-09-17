<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin
        User::updateOrCreate(
            ['email' => 'admin@warjok.com'],
            [
                'nrk'           => '0000',
                'employee_name' => 'Super Admin',
                'email'         => 'admin@warjok.com',
                'password'      => Hash::make('00000000'),
                'is_admin'      => true,
            ]
        );

        // Regular User
        // User::updateOrCreate(
        //     ['email' => 'user@example.com'],
        //     [
        //         'nrk'           => 'USR001',
        //         'employee_name' => 'Regular User',
        //         'email'         => 'user@example.com',
        //         'password'      => Hash::make('password'),
        //         'is_admin'      => false,
        //     ]
        // );
    }
}