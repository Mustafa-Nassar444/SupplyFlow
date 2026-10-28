<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LanguagesSeeder::class);

        User::insert(
            [[
                'name' => 'Admin',
                'email' => 'mustafa@admin.com',
                'role' => 'admin',
                'password' => bcrypt('12345678'),
            ],
                [
                    'name' => 'Sales',
                    'email' => 'mustafa@sales.com',
                    'role' => 'sales',
                    'password' => bcrypt('12345678'),
                ],
                [
                    'name' => 'Warehouse',
                    'email' => 'mustafa@warehouse.com',
                    'role' => 'warehouse',
                    'password' => bcrypt('12345678'),
                ]]
        );
    }
}
