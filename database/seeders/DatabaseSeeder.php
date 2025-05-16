<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Item;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'username'      => 'Comshop',
            'email'         => 'ComShop@gmail.com',
            'password'      => Hash::make('comshop123'),
            'phone_number' => '09123456789',
            'role'          => 'admin',
            'street'        => 'Maa',
            'city'          => 'Davao City',
            'region'        => 'Davao',
            'postal_code'   => '8000',
        ]);
    }
}
