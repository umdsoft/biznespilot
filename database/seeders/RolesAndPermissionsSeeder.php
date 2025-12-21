<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Business management
            'view-business',
            'update-business',
            'delete-business',

            // Team management
            'view-team',
            'invite-team',
            'remove-team',
            'update-team-role',

            // Dream Buyer
            'view-dream-buyers',
            'create-dream-buyers',
            'update-dream-buyers',
            'delete-dream-buyers',

            // Marketing
            'view-marketing',
            'create-marketing',
            'update-marketing',
            'delete-marketing',

            // Sales
            'view-sales',
            'create-sales',
            'update-sales',
            'delete-sales',

            // Competitors
            'view-competitors',
            'create-competitors',
            'update-competitors',
            'delete-competitors',

            // Offers
            'view-offers',
            'create-offers',
            'update-offers',
            'delete-offers',

            // AI Insights
            'view-ai',
            'use-ai',

            // Chatbot
            'view-chatbot',
            'manage-chatbot',

            // Reports
            'view-reports',
            'export-reports',

            // Integrations
            'view-integrations',
            'manage-integrations',

            // Settings
            'manage-subscription',
            'view-billing',
        ];

        foreach ($permissions as $permissionName) {
            if (!Permission::where('name', $permissionName)->where('guard_name', 'web')->exists()) {
                $permission = new Permission();
                $permission->id = Str::uuid()->toString();
                $permission->name = $permissionName;
                $permission->guard_name = 'web';
                $permission->save();
            }
        }

        // Create roles and assign permissions
        $this->createRole('super_admin', Permission::all()->pluck('name')->toArray());
        $this->createRole('owner', Permission::all()->pluck('name')->toArray());

        $this->createRole('admin', [
            'view-business',
            'update-business',
            'view-team',
            'invite-team',
            'remove-team',
            'update-team-role',
            'view-dream-buyers',
            'create-dream-buyers',
            'update-dream-buyers',
            'delete-dream-buyers',
            'view-marketing',
            'create-marketing',
            'update-marketing',
            'delete-marketing',
            'view-sales',
            'create-sales',
            'update-sales',
            'delete-sales',
            'view-competitors',
            'create-competitors',
            'update-competitors',
            'delete-competitors',
            'view-offers',
            'create-offers',
            'update-offers',
            'delete-offers',
            'view-ai',
            'use-ai',
            'view-chatbot',
            'manage-chatbot',
            'view-reports',
            'export-reports',
            'view-integrations',
            'manage-integrations',
            'view-billing',
        ]);

        $this->createRole('manager', [
            'view-business',
            'view-team',
            'view-dream-buyers',
            'create-dream-buyers',
            'update-dream-buyers',
            'view-marketing',
            'create-marketing',
            'update-marketing',
            'view-sales',
            'create-sales',
            'update-sales',
            'view-competitors',
            'create-competitors',
            'update-competitors',
            'view-offers',
            'create-offers',
            'update-offers',
            'view-ai',
            'use-ai',
            'view-chatbot',
            'view-reports',
            'export-reports',
            'view-integrations',
        ]);

        $this->createRole('member', [
            'view-business',
            'view-team',
            'view-dream-buyers',
            'view-marketing',
            'create-marketing',
            'view-sales',
            'create-sales',
            'update-sales',
            'view-competitors',
            'view-offers',
            'view-ai',
            'use-ai',
            'view-chatbot',
            'view-reports',
        ]);

        $this->createRole('viewer', [
            'view-business',
            'view-team',
            'view-dream-buyers',
            'view-marketing',
            'view-sales',
            'view-competitors',
            'view-offers',
            'view-ai',
            'view-chatbot',
            'view-reports',
        ]);
    }

    private function createRole(string $roleName, array $permissions): void
    {
        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

        if (!$role) {
            $role = new Role();
            $role->id = Str::uuid()->toString();
            $role->name = $roleName;
            $role->guard_name = 'web';
            $role->save();
        }

        $role->syncPermissions($permissions);
    }
}
