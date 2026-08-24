<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MultiroleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default roles
        Role::findOrCreate('admin');
        Role::findOrCreate('admin_opd');
        Role::findOrCreate('pimpinan');
        Role::findOrCreate('pegawai');
    }

    public function test_user_role_priority_and_default_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['pegawai', 'pimpinan', 'admin_opd']);

        // Default role should be highest priority (admin_opd > pimpinan > pegawai)
        $this->assertEquals('admin_opd', $user->defaultRole());
        $this->assertEquals('admin_opd', $user->currentRole());
        $this->assertTrue($user->hasActiveRole('admin_opd'));
        $this->assertFalse($user->hasActiveRole('pegawai'));
    }

    public function test_user_can_switch_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['admin_opd', 'pimpinan']);

        $this->actingAs($user);
        $this->assertEquals('admin_opd', $user->currentRole());

        // Switch to pimpinan
        $switched = $user->switchRole('pimpinan');
        $this->assertTrue($switched);
        $this->assertEquals('pimpinan', $user->currentRole());
        $this->assertTrue($user->hasActiveRole('pimpinan'));
        $this->assertFalse($user->hasActiveRole('admin_opd'));

        // Cannot switch to role not owned
        $invalidSwitch = $user->switchRole('admin');
        $this->assertFalse($invalidSwitch);
        $this->assertEquals('pimpinan', $user->currentRole());
    }

    public function test_topbar_profile_component_switches_role(): void
    {
        $user = User::factory()->create(['name' => 'Dr. H. Andi Sukri']);
        $user->assignRole(['admin_opd', 'pimpinan']);

        $this->actingAs($user);

        Volt::test('layout.topbar-profile')
            ->call('switchRole', 'pimpinan')
            ->assertRedirect(route('dashboard'));

        $this->assertEquals('pimpinan', session('active_role'));
        $this->assertTrue($user->fresh()->hasActiveRole('pimpinan'));
    }

    public function test_super_admin_can_assign_multiple_roles_to_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        Volt::test('users.index')
            ->call('openAddModal')
            ->set('name', 'Budi Santoso, S.Kom')
            ->set('nip', '199001012015011001')
            ->set('roles', ['admin_opd', 'pimpinan'])
            ->set('unit_name', 'Dinas Komunikasi dan Informatika')
            ->set('jabatan', 'Kepala Bidang')
            ->call('saveUser')
            ->assertHasNoErrors();

        $newUser = User::where('nip', '199001012015011001')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole('admin_opd'));
        $this->assertTrue($newUser->hasRole('pimpinan'));
        $this->assertFalse($newUser->hasRole('admin'));
    }

    public function test_super_admin_can_update_user_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create(['nip' => '199202022016021002']);
        $user->assignRole('pegawai');

        $this->actingAs($admin);

        Volt::test('users.index')
            ->call('openEditModal', $user->id)
            ->set('roles', ['admin', 'pimpinan'])
            ->call('saveUser')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('pimpinan'));
        $this->assertFalse($user->hasRole('pegawai'));
    }

    public function test_user_management_screen_requires_active_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['admin', 'pegawai']);

        $this->actingAs($user);

        // Active as admin -> can access
        $user->switchRole('admin');
        $this->get('/users')->assertOk();

        // Switch to pegawai -> forbidden
        $user->switchRole('pegawai');
        $this->get('/users')->assertForbidden();
    }
}
