<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = User::create(
            [
                'id' => "18be2258-bb29-44bf-a2d3-10377f2b337b",
                'name' => "Superadmin",
                'email' => "superadmin@mail.com",
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ],
        );
    }
}
