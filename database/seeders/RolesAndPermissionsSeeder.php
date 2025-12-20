<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create roles and assign permissions

        // Super Admin - Platform administrator (full system access)
        $superAdmin = Role::findOrCreate('super_admin');
        $superAdmin->givePermissionTo(Permission::all());

        // Owner - Full access
        $owner = Role::findOrCreate('owner');
        $owner->givePermissionTo(Permission::all());

        // Admin - Almost full access (except delete business and manage subscription)
        $admin = Role::findOrCreate('admin');
        $admin->givePermissionTo([
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

        // Manager - Manage day-to-day operations
        $manager = Role::findOrCreate('manager');
        $manager->givePermissionTo([
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

        // Member - Basic access
        $member = Role::findOrCreate('member');
        $member->givePermissionTo([
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

        // Viewer - Read-only access
        $viewer = Role::findOrCreate('viewer');
        $viewer->givePermissionTo([
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
}
