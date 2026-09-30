<?php

namespace Database\Seeders;

use App\Models\Admin;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();

        $guard = 'admin';
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => $guard,
        ]);
        Role::firstOrCreate([
            'name' => 'panel_user',
            'guard_name' => $guard,
        ]);

        /** @var list<string> $permissionNames */
        $permissionNames = [];

        foreach (FilamentShield::getResources() ?? [] as $resource) {
            foreach ($resource['permissions'] ?? [] as $permission) {
                if (is_string($permission['key'] ?? null)) {
                    $permissionNames[] = $permission['key'];
                }
            }
        }

        foreach (FilamentShield::getPages() ?? [] as $page) {
            foreach (array_keys($page['permissions'] ?? []) as $permission) {
                if (is_string($permission)) {
                    $permissionNames[] = $permission;
                }
            }
        }

        $permissionNames = array_values(array_unique($permissionNames));

        Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', [
                'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore',
                'forceDelete', 'forceDeleteAny', 'restoreAny', 'replicate', 'reorder',
            ])
            ->delete();

        $permissions = collect($permissionNames)->map(
            fn (string $name): Permission => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]),
        );

        $superAdmin->syncPermissions($permissions);

        Admin::query()
            ->where('email', 'admin@admin.com')
            ->get()
            ->each(fn (Admin $admin): mixed => $admin->assignRole($superAdmin));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
