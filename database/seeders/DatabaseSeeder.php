<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            VisitorCategorySeeder::class,
        ]);

        $users = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@aykon.com',
                'phone' => '0700000001',
                'role' => 'admin',
            ],
            [
                'name' => 'Front Desk Receptionist',
                'email' => 'reception@aykon.com',
                'phone' => '0700000002',
                'role' => 'receptionist',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'role' => $user['role'],
                    'status' => 'free',
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ],
            );
        }
    }
}