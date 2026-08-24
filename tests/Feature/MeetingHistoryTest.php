<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeetingHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('admin_opd');
        Role::findOrCreate('pimpinan');
        Role::findOrCreate('pegawai');
    }

    public function test_pegawai_cannot_access_meeting_history(): void
    {
        $pegawai = User::factory()->create();
        $pegawai->assignRole('pegawai');

        $this->actingAs($pegawai);

        $response = $this->get(route('meetings.history'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_view_all_completed_and_signed_meetings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $opd1 = Opd::create(['name' => 'Dinas Kominfo', 'unit_id' => '101', 'is_active' => true]);
        $opd2 = Opd::create(['name' => 'Bappeda', 'unit_id' => '102', 'is_active' => true]);

        // Completed & fully signed meeting
        $validMeeting1 = Meeting::create([
            'title' => 'Rapat Evaluasi SPBE',
            'agenda' => 'Evaluasi SPBE',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Command Center',
            'status' => 'completed',
            'opd_id' => $opd1->id,
            'created_by' => $admin->id,
            'minutes_signed_at' => now(),
            'attendance_signed_at' => now(),
            'photos_signed_at' => now(),
        ]);

        // Completed but NOT signed meeting (should NOT appear)
        $unsignedMeeting = Meeting::create([
            'title' => 'Rapat Belum TTE',
            'agenda' => 'Rapat Belum TTE',
            'date' => now()->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'location' => 'Ruang Pola',
            'status' => 'completed',
            'opd_id' => $opd2->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('meetings.history'));
        $response->assertStatus(200);

        Volt::test('meetings.history')
            ->assertSee('Rapat Evaluasi SPBE')
            ->assertDontSee('Rapat Belum TTE');
    }

    public function test_admin_opd_only_sees_meetings_of_own_opd(): void
    {
        $opd1 = Opd::create(['name' => 'Dinas Kominfo', 'unit_id' => '101', 'is_active' => true]);
        $opd2 = Opd::create(['name' => 'Dinas Pendidikan', 'unit_id' => '102', 'is_active' => true]);

        $adminOpd = User::factory()->create([
            'unit_name' => 'Dinas Kominfo',
        ]);
        $adminOpd->assignRole('admin_opd');

        $meeting1 = Meeting::create([
            'title' => 'Rapat Internal Kominfo',
            'agenda' => 'Rapat Internal Kominfo',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Kadis',
            'status' => 'completed',
            'opd_id' => $opd1->id,
            'created_by' => $adminOpd->id,
            'minutes_signed_at' => now(),
            'attendance_signed_at' => now(),
            'photos_signed_at' => now(),
        ]);

        $disdikUser = User::factory()->create([
            'unit_name' => 'Dinas Pendidikan',
        ]);

        $meeting2 = Meeting::create([
            'title' => 'Rapat Internal Disdik',
            'agenda' => 'Rapat Internal Disdik',
            'date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'location' => 'Aula Disdik',
            'status' => 'completed',
            'opd_id' => $opd2->id,
            'created_by' => $disdikUser->id,
            'minutes_signed_at' => now(),
            'attendance_signed_at' => now(),
            'photos_signed_at' => now(),
        ]);

        $this->actingAs($adminOpd);

        Volt::test('meetings.history')
            ->assertSee('Rapat Internal Kominfo')
            ->assertDontSee('Rapat Internal Disdik');
    }

    public function test_pimpinan_cannot_access_meeting_history(): void
    {
        $pimpinan = User::factory()->create([
            'name' => 'Dr. H. Andi Baso, M.Si',
            'nip' => '197501012000031001',
        ]);
        $pimpinan->assignRole('pimpinan');

        $this->actingAs($pimpinan);

        $response = $this->get(route('meetings.history'));
        $response->assertStatus(403);
    }
}
