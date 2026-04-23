<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Partner roli va unga tegishli permission'larni yaratadi.
 *
 * Partner roli boshqa kompaniya rollaridan (owner/admin/member) FARQ QILADI:
 *  - Partner hech qanday biznes'ga bog'lanmaydi
 *  - Faqat o'z hamkorlik dashboard'ini ko'radi (/partner/*)
 *  - Commission, referrals, payouts, profile sozlamalari
 *
 * Admin va super_admin rollari avtomatik partner panelga kirish huquqiga
 * ega (support uchun), lekin o'zlariga partner yozuvi kerak emas.
 */
class PartnerRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Cached roles/permissions'larni tozalash
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Partner-ga tegishli permission'lar
        $permissions = [
            'view-partner-dashboard',
            'view-partner-referrals',
            'view-partner-commissions',
            'view-partner-payouts',
            'request-partner-payout',
            'manage-partner-profile',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['id' => (string) Str::uuid()]
            );
        }

        // Partner roli
        $this->createRole('partner', $permissions);

        // Super admin va admin avtomatik partner permissionlarni ham olishadi
        $this->grantPermissionsToRole('super_admin', $permissions);
        $this->grantPermissionsToRole('admin', $permissions);

        $this->command->info('Partner role + ' . count($permissions) . ' permissions yaratildi/yangilandi.');
    }

    /**
     * Rolni yaratish yoki mavjud bo'lsa permission'larni yangilash.
     */
    protected function createRole(string $roleName, array $permissions): void
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['id' => (string) Str::uuid()]
        );

        $permissionModels = Permission::whereIn('name', $permissions)
            ->where('guard_name', 'web')
            ->get();

        $role->syncPermissions($permissionModels);
    }

    /**
     * Mavjud rolga qo'shimcha permission berish (mavjudlari o'zgarmaydi).
     */
    protected function grantPermissionsToRole(string $roleName, array $permissions): void
    {
        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

        if (! $role) {
            return; // Rol hali yo'q
        }

        $permissionModels = Permission::whereIn('name', $permissions)
            ->where('guard_name', 'web')
            ->get();

        foreach ($permissionModels as $perm) {
            if (! $role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }
    }
}
