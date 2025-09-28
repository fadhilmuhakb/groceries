<?php

namespace Database\Seeders;

use App\Models\tb_units;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TbUnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
                [
                    "unit_name" => "Pcs",
                    "description" => "Per buah"
                ],
                [
                    "unit_name" => "Kg",
                    "description" => "Kilogram"
                ],
                [
                    "unit_name" => "Liter",
                    "description" => "Liter"
                ],
                [
                    "unit_name" => "Botol",
                    "description" => "Per botol"
                ],
                [
                    "unit_name" => "Dus",
                    "description" => "Per dus"
                ],
                [
                    "unit_name" => "Roll",
                    "description" => "Per roll"
                ]
            ];

            tb_units::insert($units);
    }
}
