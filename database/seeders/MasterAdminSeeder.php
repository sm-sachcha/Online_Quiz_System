<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Check if master admin already exists
        if (!User::where('email', 'master@admin.com')->exists()) {
            $user = User::create([
                'name' => 'Administrstor',
                'email' => 'master@admin.com',
                'password' => Hash::make('password123'),
                'role' => 'master_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'total_points' => 0,
                'quizzes_attempted' => 0,
                'quizzes_won' => 0,
            ]);
            
            $this->command->info('Master Admin created successfully!');
        } else {
            $this->command->info('Master Admin already exists!');
        }
    }
}