<?php

namespace Tests\Feature;

use App\Filament\Resources\AdminResource\Pages\CreateAdmin;
use App\Models\Admin;
use App\Models\Order;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->seed(ShieldSeeder::class);
    }

    public function test_super_admin_can_open_staff_and_role_management(): void
    {
        $admin = Admin::factory()->create();

        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertTrue($admin->can('ViewAny:Admin'));
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Admin::class));
        $this->assertTrue($admin->canAccessPanel(Filament::getCurrentPanel()));

        $this->actingAs($admin, 'admin')
            ->get(route('filament.admin.resources.admins.index'))
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->get(route('filament.admin.resources.roles.index'))
            ->assertOk();
    }

    public function test_admin_can_create_staff_user_with_a_role(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create([
            'name' => '订单管理员',
            'guard_name' => 'admin',
        ]);

        Livewire::actingAs($admin, 'admin');

        Livewire::test(CreateAdmin::class)
            ->fillForm([
                'name' => '订单管理员账号',
                'email' => 'orders-admin@example.com',
                'password' => 'password123',
                'roles' => [$role->getKey()],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $createdAdmin = Admin::query()->where('email', 'orders-admin@example.com')->firstOrFail();

        $this->assertTrue($createdAdmin->hasRole($role));
        $this->assertTrue($createdAdmin->canAccessPanel(Filament::getCurrentPanel()));
    }

    public function test_role_permissions_limit_staff_resource_access(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create([
            'name' => '订单查看员',
            'guard_name' => 'admin',
        ]);
        $role->givePermissionTo(Permission::findByName('ViewAny:Order', 'admin'));
        $admin->syncRoles($role);

        $this->assertTrue($admin->can('ViewAny:Order'));
        $this->assertFalse($admin->can('ViewAny:Product'));
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Order::class));

        $this->actingAs($admin, 'admin')
            ->get(route('filament.admin.resources.orders.index'))
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->get(route('filament.admin.resources.products.index'))
            ->assertForbidden();
    }
}
