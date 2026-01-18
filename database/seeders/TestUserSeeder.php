<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin user
        $admin = User::where('login', 'admin')->first();

        if (! $admin) {
            $admin = new User;
            $admin->id = Str::uuid()->toString();
            $admin->login = 'admin';
            $admin->name = 'Super Admin';
            $admin->email = 'admin@biznespilot.uz';
            $admin->phone = '+998901234567';
            $admin->password = Hash::make('admin123');
            $admin->save();
        }

        // Assign super_admin role for platform administration
        if (! $admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        // Create regular test user
        $user1 = User::where('login', 'user1')->first();

        if (! $user1) {
            $user1 = new User;
            $user1->id = Str::uuid()->toString();
            $user1->login = 'user1';
            $user1->name = 'Test User';
            $user1->email = 'user1@biznespilot.uz';
            $user1->phone = '+998901234568';
            $user1->password = Hash::make('user123');
            $user1->save();
        }

        // Assign owner role
        if (! $user1->hasRole('owner')) {
            $user1->assignRole('owner');
        }

        $this->command->info('Test users created: admin@biznespilot.uz / admin123, user1@biznespilot.uz / user123');
    }
}
