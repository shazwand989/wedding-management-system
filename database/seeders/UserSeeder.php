<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'email' => 'admin@wedding.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'name' => 'System Administrator',
            'phone' => '123456789',
            'status' => 'active',
        ]);

        // Create vendor users
        User::create([
            'email' => 'photographer@example.com',
            'password' => Hash::make('password'),
            'role' => 'vendor',
            'name' => 'John Photography Studio',
            'phone' => '123456780',
            'status' => 'active',
        ]);

        User::create([
            'email' => 'caterer@example.com',
            'password' => Hash::make('password'),
            'role' => 'vendor',
            'name' => 'Delicious Catering',
            'phone' => '123456781',
            'status' => 'active',
        ]);

        User::create([
            'email' => 'decorator@example.com',
            'password' => Hash::make('password'),
            'role' => 'vendor',
            'name' => 'Beautiful Decorations',
            'phone' => '123456782',
            'status' => 'active',
        ]);

        // Create customer demo user
        User::create([
            'email' => 'customer@demo.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'name' => 'John & Jane Doe',
            'phone' => '123456783',
            'status' => 'active',
        ]);
    }
}
