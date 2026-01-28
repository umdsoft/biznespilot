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
     * Super admin foydalanuvchini yaratish
     */
    public function run(): void
    {
        // Create super admin user
        $admin = User::where('login', 'umdsoft')->first();

        if (! $admin) {
            $admin = new User;
            $admin->id = Str::uuid()->toString();
            $admin->login = 'umdsoft';
            $admin->name = 'Super Admin';
            $admin->email = 'admin@biznespilot.uz';
            $admin->phone = '+998901234567';
            $admin->password = Hash::make('Umidbek19952812@');
            $admin->save();
        }

        // Assign super_admin role for platform administration
        if (! $admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        $this->command->info('Admin yaratildi: umdsoft / Umidbek19952812@');
    }
}
