<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'nip' => '198501012010011001',
            'password' => bcrypt('password'),
        ]);

        $component = Volt::test('pages.auth.login')
            ->set('form.nip', $user->nip)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'nip' => '198501012010011001',
            'password' => bcrypt('password'),
        ]);

        $component = Volt::test('pages.auth.login')
            ->set('form.nip', $user->nip)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response
            ->assertOk()
            ->assertSeeVolt('layout.navigation');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.navigation');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_users_can_authenticate_via_simpeg_and_populate_pangkat(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*user_auth*' => \Illuminate\Support\Facades\Http::response('1', 200),
            '*data_pegawai*' => \Illuminate\Support\Facades\Http::response([
                'nama' => 'Pegawai SIMPEG Test',
                'nip' => '199001012015011001',
                'jabatan_nama' => 'Pranata Komputer Ahli Muda',
                'pangkat_nama' => 'Penata (III/c)',
                'unit_id' => '730714',
            ], 200),
            '*get_unit*' => \Illuminate\Support\Facades\Http::response([
                'unit_nama' => 'Dinas Komunikasi Informatika dan Persandian',
            ], 200),
        ]);

        $component = Volt::test('pages.auth.login')
            ->set('form.nip', '199001012015011001')
            ->set('form.password', 'secret123');

        $component->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $user = User::where('nip', '199001012015011001')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Pegawai SIMPEG Test', $user->name);
        $this->assertEquals('Pranata Komputer Ahli Muda', $user->jabatan);
        $this->assertEquals('Penata (III/c)', $user->pangkat);
        $this->assertEquals('Dinas Komunikasi Informatika dan Persandian', $user->unit_name);
        $this->assertTrue($user->hasRole('pegawai'));
    }
}
