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
}
