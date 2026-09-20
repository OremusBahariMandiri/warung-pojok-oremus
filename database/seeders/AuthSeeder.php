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
                'nrk'           => '1111',
                'employee_name' => 'Super Admin',
                'email'         => 'admin@warjok.com',
                'password'      => Hash::make('11111111'),
                'is_admin'      => true,
            ]
        );

        // Regular User
        User::updateOrCreate(
            ['email' => 'afandi@warjok.com'],
            [
                'nrk'           => '0000',
                'employee_name' => 'Afandi',
                'email'         => 'afandi@warjok.com',
                'password'      => Hash::make('00000000'),
                'is_admin'      => false,
            ]
        );
    }
}