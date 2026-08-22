<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingMinute;
use App\Models\User;
use App\Services\BsreEsignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BsreEsignTest extends TestCase
{
    use RefreshDatabase;

    public function test_pimpinan_can_sign_documents_via_service(): void
    {
        $pimpinan = User::factory()->create([
            'nik' => '7307010101850001',
            'name' => 'Bupati Sinjai',
        ]);

        $user = User::factory()->create();

        $meeting = Meeting::create([
            'title' => 'Rapat Koordinasi Pembangunan',
            'agenda' => 'Rapat Koordinasi Pembangunan',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Pola',
            'status' => 'completed',
            'created_by' => $user->id,
        ]);

        MeetingMinute::create([
            'meeting_id' => $meeting->id,
            'content' => 'Hasil rapat penting disepakati.',
        ]);

        $service = app(BsreEsignService::class);
        $result = $service->signAllDocuments($meeting, $pimpinan, '123456');

        $this->assertTrue($result['success'], $result['message'] ?? 'Signing failed');
        $this->assertNotNull($meeting->fresh()->minutes_signed_at);
        $this->assertNotNull($meeting->fresh()->attendance_signed_at);
        $this->assertNotNull($meeting->fresh()->photos_signed_at);
    }

    public function test_signing_fails_gracefully_when_user_has_no_nik(): void
    {
        $pimpinanNoNik = User::factory()->create([
            'nik' => null,
            'name' => 'Pimpinan Tanpa NIK',
        ]);

        $meeting = Meeting::create([
            'title' => 'Rapat Evaluasi',
            'agenda' => 'Rapat Evaluasi',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Pola',
            'status' => 'completed',
            'created_by' => $pimpinanNoNik->id,
        ]);

        $service = app(BsreEsignService::class);
        $result = $service->signAllDocuments($meeting, $pimpinanNoNik, '123456');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('NIK', $result['message']);
    }

    public function test_public_can_view_tte_verification_page_for_signed_document(): void
    {
        $user = User::factory()->create(['nik' => '7307012345670001']);
        $meeting = Meeting::create([
            'title' => 'Rapat Koordinasi SPBE',
            'agenda' => 'Evaluasi SPBE',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Rapat Bappeda',
            'status' => 'completed',
            'created_by' => $user->id,
            'minutes_signed_at' => now(),
            'signer_name' => 'Dr. H. Contoh, M.Si',
            'signer_nip' => '197501012000031001',
            'signer_title' => 'Kepala Dinas Kominfo',
        ]);

        $response = $this->get(route('meetings.verify.tte', ['meeting' => $meeting->id, 'type' => 'notulen']));

        $response->assertStatus(200);
        $response->assertSee('Tanda Tangan Elektronik Sah');
        $response->assertSee('Rapat Koordinasi SPBE');
        $response->assertSee('Dr. H. Contoh, M.Si');
    }

    public function test_public_can_download_verified_signed_pdf(): void
    {
        $user = User::factory()->create(['nik' => '7307012345670001']);
        $meeting = Meeting::create([
            'title' => 'Rapat Koordinasi SPBE',
            'agenda' => 'Evaluasi SPBE',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Rapat Bappeda',
            'status' => 'completed',
            'created_by' => $user->id,
            'minutes_signed_at' => now(),
            'signer_name' => 'Dr. H. Contoh, M.Si',
            'signer_nip' => '197501012000031001',
            'signer_title' => 'Kepala Dinas Kominfo',
        ]);

        $response = $this->get(route('meetings.verify.download', ['type' => 'notulen', 'meeting' => $meeting->id]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
