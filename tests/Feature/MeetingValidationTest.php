<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeetingValidationTest extends TestCase
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

    public function test_meeting_creation_accepts_and_normalizes_ampm_times(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $opd = Opd::create([
            'name' => 'Dinas Komunikasi dan Informatika',
            'unit_id' => '101',
            'leader_name' => 'Kadis Kominfo',
            'is_active' => true,
        ]);

        Volt::test('meetings.index')
            ->set('title', 'Rapat Koordinasi Siang')
            ->set('date', now()->toDateString())
            ->set('start_time', '09:30 AM')
            ->set('end_time', '02:00 PM')
            ->set('location', 'Ruang Pola')
            ->set('selected_opd_id', $opd->id)
            ->set('selected_signer_id', 'kepala_opd')
            ->call('saveMeeting')
            ->assertHasNoErrors();

        $meeting = Meeting::where('title', 'Rapat Koordinasi Siang')->first();
        $this->assertNotNull($meeting);
        $this->assertEquals('09:30', $meeting->start_time->format('H:i'));
        $this->assertEquals('14:00', $meeting->end_time->format('H:i'));
    }

    public function test_meeting_creation_rejects_end_time_before_start_time(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $opd = Opd::create([
            'name' => 'Dinas Komunikasi dan Informatika',
            'unit_id' => '101',
            'leader_name' => 'Kadis Kominfo',
            'is_active' => true,
        ]);

        Volt::test('meetings.index')
            ->set('title', 'Rapat Invalid Time')
            ->set('date', now()->toDateString())
            ->set('start_hour', '14')
            ->set('start_minute', '00')
            ->set('end_hour', '10')
            ->set('end_minute', '00')
            ->set('location', 'Ruang Pola')
            ->set('selected_opd_id', $opd->id)
            ->set('selected_signer_id', 'kepala_opd')
            ->call('saveMeeting')
            ->assertHasErrors(['end_time' => 'after']);
    }

    public function test_meeting_creation_with_hour_and_minute_dropdowns(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $opd = Opd::create([
            'name' => 'Dinas Komunikasi dan Informatika',
            'unit_id' => '101',
            'leader_name' => 'Kadis Kominfo',
            'is_active' => true,
        ]);

        Volt::test('meetings.index')
            ->set('title', 'Rapat Dropdown 24 Jam')
            ->set('date', now()->toDateString())
            ->set('start_hour', '13')
            ->set('start_minute', '30')
            ->set('end_hour', '15')
            ->set('end_minute', '45')
            ->set('location', 'Ruang Pola')
            ->set('selected_opd_id', $opd->id)
            ->set('selected_signer_id', 'kepala_opd')
            ->call('saveMeeting')
            ->assertHasNoErrors();

        $meeting = Meeting::where('title', 'Rapat Dropdown 24 Jam')->first();
        $this->assertNotNull($meeting);
        $this->assertEquals('13:30', $meeting->start_time->format('H:i'));
        $this->assertEquals('15:45', $meeting->end_time->format('H:i'));
    }

    public function test_completed_meeting_can_be_reopened_if_no_documents_signed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $meeting = Meeting::create([
            'title' => 'Rapat Selesai Belum TTE',
            'agenda' => 'Agenda',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Rapat',
            'status' => 'completed',
            'created_by' => $admin->id,
        ]);

        Volt::test('meetings.header', ['meeting' => $meeting])
            ->call('reopenMeeting')
            ->assertHasNoErrors();

        $meeting->refresh();
        $this->assertEquals('ongoing', $meeting->status);
    }

    public function test_completed_meeting_cannot_be_reopened_if_documents_signed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $meeting = Meeting::create([
            'title' => 'Rapat Selesai Sudah TTE',
            'agenda' => 'Agenda',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Rapat',
            'status' => 'completed',
            'minutes_signed_at' => now(),
            'created_by' => $admin->id,
        ]);

        Volt::test('meetings.header', ['meeting' => $meeting])
            ->call('reopenMeeting')
            ->assertStatus(403);

        $meeting->refresh();
        $this->assertEquals('completed', $meeting->status);
    }

    public function test_completed_meeting_cannot_be_reopened_by_pimpinan(): void
    {
        $pimpinan = User::factory()->create();
        $pimpinan->assignRole('pimpinan');
        $this->actingAs($pimpinan);

        $meeting = Meeting::create([
            'title' => 'Rapat Selesai Pimpinan Test',
            'agenda' => 'Agenda',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Rapat',
            'status' => 'completed',
            'created_by' => $pimpinan->id,
        ]);

        Volt::test('meetings.header', ['meeting' => $meeting])
            ->assertDontSee('Lanjutkan Rapat');
    }

    public function test_meeting_creation_and_editing_with_manual_pimpinan(): void
    {
        $adminOpd = User::factory()->create([
            'unit_name' => 'Dinas Komunikasi dan Informatika',
        ]);
        $adminOpd->assignRole('admin_opd');
        $this->actingAs($adminOpd);

        $opd = Opd::create([
            'name' => 'Dinas Komunikasi dan Informatika',
            'unit_id' => '101',
            'leader_name' => 'Kadis Kominfo',
            'leader_nip' => '197001011990011001',
            'is_active' => true,
        ]);

        $manualPimpinan = User::factory()->create([
            'name' => 'Drs. Manual Pimpinan',
            'nip' => '198001012000011002',
            'unit_name' => 'Dinas Komunikasi dan Informatika',
            'jabatan' => 'Plt. Kepala Bidang Informatika',
            'pangkat' => 'Pembina (IV/a)',
        ]);
        $manualPimpinan->assignRole('pimpinan');

        // Test creating meeting with manual pimpinan
        Volt::test('meetings.index')
            ->set('title', 'Rapat Koordinasi Manual Pimpinan')
            ->set('date', now()->toDateString())
            ->set('start_hour', '09')
            ->set('start_minute', '00')
            ->set('end_hour', '11')
            ->set('end_minute', '00')
            ->set('location', 'Ruang Rapat Kominfo')
            ->set('selected_signer_id', 'pimpinan_' . $manualPimpinan->id)
            ->call('saveMeeting')
            ->assertHasNoErrors();

        $meeting = Meeting::where('title', 'Rapat Koordinasi Manual Pimpinan')->first();
        $this->assertNotNull($meeting);
        $this->assertEquals('Plt. Kepala Bidang Informatika', $meeting->signer_title);
        $this->assertEquals('Drs. Manual Pimpinan', $meeting->signer_name);
        $this->assertEquals('198001012000011002', $meeting->signer_nip);
        $this->assertEquals('Pembina (IV/a)', $meeting->signer_rank);

        // Test editing meeting signer via header
        Volt::test('meetings.header', ['meeting' => $meeting])
            ->set('selected_signer_id', 'kepala_opd')
            ->call('updateMeeting')
            ->assertHasNoErrors();

        $meeting->refresh();
        $this->assertEquals('Kadis Kominfo', $meeting->signer_name);

        // Test editing manual pimpinan via opd.settings
        Volt::test('opd.settings')
            ->call('openEditManualPimpinanModal', $manualPimpinan->id)
            ->set('signer_name', 'Drs. Manual Pimpinan Updated')
            ->set('signer_rank', 'Pembina Tk. I')
            ->call('saveSigner')
            ->assertHasNoErrors();

        $manualPimpinan->refresh();
        $this->assertEquals('Drs. Manual Pimpinan Updated', $manualPimpinan->name);
        $this->assertEquals('Pembina Tk. I', $manualPimpinan->pangkat);
    }

    public function test_tte_modal_displays_user_status_by_nik(): void
    {
        $pimpinan = User::factory()->create([
            'nik' => '7307011234560001',
            'name' => 'Dr. H. Pimpinan TTE',
        ]);
        $pimpinan->assignRole('pimpinan');
        $this->actingAs($pimpinan);

        $meeting = Meeting::create([
            'title' => 'Rapat Uji TTE Modal Status',
            'agenda' => 'Agenda',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Rapat',
            'status' => 'completed',
            'signer_name' => $pimpinan->name,
            'signer_nip' => $pimpinan->nip,
            'created_by' => $pimpinan->id,
        ]);

        // Test status mapping on service
        $service = app(\App\Services\BsreEsignService::class);
        $statusIssue = $service->formatStatusResponse('ISSUE');
        $this->assertTrue($statusIssue['can_sign']);
        $this->assertEquals('Aktif', $statusIssue['label']);

        $statusExpired = $service->formatStatusResponse('EXPIRED');
        $this->assertFalse($statusExpired['can_sign']);
        $this->assertEquals('Expired', $statusExpired['label']);

        // Mock BSrE API response for reliable test execution
        \Illuminate\Support\Facades\Http::fake([
            '*/user/check/status' => \Illuminate\Support\Facades\Http::response([
                'status_code' => 1111,
                'message' => 'Status Sertifikat Anda ISSUE',
                'status' => 'ISSUE',
            ], 200),
        ]);

        // Test rendering modal with status info on overview for pimpinan
        Volt::test('meetings.overview', ['meeting' => $meeting])
            ->assertSee('Status TTE')
            ->assertSee('Aktif')
            ->assertSee('Notulen Rapat')
            ->assertSee('Presensi Rapat')
            ->assertSee('Dokumentasi Rapat');

        // Test pimpinan redirected to overview from other tabs
        Volt::test('meetings.presensi', ['meeting' => $meeting])
            ->assertRedirect(route('meetings.overview', $meeting->id));

        Volt::test('meetings.notulen', ['meeting' => $meeting])
            ->assertRedirect(route('meetings.overview', $meeting->id));

        Volt::test('meetings.dokumentasi', ['meeting' => $meeting])
            ->assertRedirect(route('meetings.overview', $meeting->id));

        // Test admin can view tabs with status
        $admin = User::factory()->create([
            'nik' => '7307011234560002',
        ]);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Volt::test('meetings.presensi', ['meeting' => $meeting])
            ->assertSee('Status TTE')
            ->assertSee('Aktif');
    }

    public function test_pimpinan_role_cannot_edit_notulen_and_dokumentasi(): void
    {
        $pimpinan = User::factory()->create();
        $pimpinan->assignRole('pimpinan');
        $this->actingAs($pimpinan);

        $meeting = Meeting::create([
            'title' => 'Rapat Pimpinan Cannot Edit Test',
            'agenda' => 'Agenda',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Rapat',
            'status' => 'completed',
            'signer_name' => $pimpinan->name,
            'signer_nip' => $pimpinan->nip,
            'created_by' => $pimpinan->id,
        ]);

        // Pimpinan on overview sees document actions without edit form
        Volt::test('meetings.overview', ['meeting' => $meeting])
            ->assertSee('Dokumen Rapat')
            ->assertDontSee('Simpan Notulen')
            ->assertDontSee('Upload Foto');
    }

    public function test_unlock_for_revision_dispatches_meeting_updated_and_resets_signed_fields(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $meeting = Meeting::create([
            'title' => 'Rapat Uji Buka Revisi',
            'agenda' => 'Agenda',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Rapat',
            'status' => 'completed',
            'created_by' => $admin->id,
            'attendance_signed_at' => now(),
            'minutes_signed_at' => now(),
            'photos_signed_at' => now(),
        ]);

        // Unlock attendance from presensi tab
        Volt::test('meetings.presensi', ['meeting' => $meeting])
            ->call('unlockForRevision')
            ->assertDispatched('meeting-updated');

        $meeting->refresh();
        $this->assertNull($meeting->attendance_signed_at);

        // Unlock minutes from notulen tab
        Volt::test('meetings.notulen', ['meeting' => $meeting])
            ->call('unlockForRevision')
            ->assertDispatched('meeting-updated');

        $meeting->refresh();
        $this->assertNull($meeting->minutes_signed_at);

        // Unlock photos from overview tab
        Volt::test('meetings.overview', ['meeting' => $meeting])
            ->call('unlockForRevision', 'photos')
            ->assertDispatched('meeting-updated');

        $meeting->refresh();
        $this->assertNull($meeting->photos_signed_at);
    }
}

