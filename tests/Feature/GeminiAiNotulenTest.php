<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\User;
use App\Services\GeminiAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GeminiAiNotulenTest extends TestCase
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

    public function test_gemini_service_generates_minutes_from_text(): void
    {
        config([
            'services.gemini.api_key' => 'test-fake-key-12345',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "1. Pembukaan\nRapat dibuka oleh Kepala Dinas.\n\n2. Pembahasan\nPembahasan penerapan sistem eRapat.\n\n3. Kesimpulan\nSemua unit sepakat menerapkan eRapat."]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $user = User::factory()->create();
        $meeting = Meeting::create([
            'title' => 'Rapat Koordinasi eRapat',
            'agenda' => 'Penerapan Aplikasi eRapat Terpadu',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Rapat Diskominfo',
            'status' => 'ongoing',
            'created_by' => $user->id,
        ]);

        $service = new GeminiAiService();
        $result = $service->generateMinutesFromText($meeting, 'Kepala dinas membuka rapat. Bahas sistem eRapat. Semua sepakat.');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('1. Pembukaan', $result['content']);
        $this->assertStringContainsString('2. Pembahasan', $result['content']);
        $this->assertStringContainsString('3. Kesimpulan', $result['content']);
    }

    public function test_gemini_service_falls_back_when_first_model_fails(): void
    {
        config([
            'services.gemini.api_key' => 'test-fake-key-12345',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:*' => Http::response([
                'error' => [
                    'code' => 404,
                    'message' => 'models/gemini-3.6-flash is not found'
                ]
            ], 404),
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "Hasil notulen dari model fallback gemini-3.5-flash."]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $user = User::factory()->create();
        $meeting = Meeting::create([
            'title' => 'Rapat Koordinasi',
            'agenda' => 'Agenda Fallback',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Rapat',
            'status' => 'ongoing',
            'created_by' => $user->id,
        ]);

        $service = new GeminiAiService();
        $result = $service->generateMinutesFromText($meeting, 'Catatan rapat untuk pengujian fallback.');

        $this->assertTrue($result['success']);
        $this->assertEquals('gemini-3.5-flash', $result['model']);
        $this->assertStringContainsString('Hasil notulen dari model fallback', $result['content']);
    }

    public function test_gemini_service_test_connection(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'KONEKSI_BERHASIL']
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $service = new GeminiAiService();
        $result = $service->testConnection('test-key');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('berhasil', $result['message']);
    }

    public function test_notulen_component_can_generate_and_apply_ai_content(): void
    {
        config([
            'services.gemini.api_key' => 'test-fake-key',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "I. PEMBUKAAN\nRapat koordinasi eRapat.\n\nII. KESIMPULAN\nDisepakati bersama."]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $user = User::factory()->create();
        $user->assignRole('pegawai');

        $meeting = Meeting::create([
            'title' => 'Rapat Koordinasi eRapat',
            'agenda' => 'Penerapan Aplikasi eRapat',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Pola',
            'status' => 'completed',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Volt::test('meetings.notulen', ['meeting' => $meeting])
            ->set('content', 'Ini catatan rapat mentah yang ditulis di editor untuk dites AI.')
            ->call('processAi')
            ->assertSet('showAiModal', true)
            ->assertHasNoErrors()
            ->assertSet('aiResult', "I. PEMBUKAAN\nRapat koordinasi eRapat.\n\nII. KESIMPULAN\nDisepakati bersama.")
            ->call('applyAiMinutes')
            ->assertSet('content', "I. PEMBUKAAN\nRapat koordinasi eRapat.\n\nII. KESIMPULAN\nDisepakati bersama.");

        $meeting->refresh();
        $this->assertNotNull($meeting->minutes);
        $this->assertTrue($meeting->minutes->ai_generated);
        $this->assertStringContainsString('Rapat koordinasi eRapat', $meeting->minutes->content);
    }

    public function test_notulen_ai_works_for_ongoing_meetings(): void
    {
        config([
            'services.gemini.api_key' => 'test-fake-key',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "I. PEMBUKAAN\nRapat sedang berjalan."]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $user = User::factory()->create();
        $user->assignRole('pegawai');

        $meeting = Meeting::create([
            'title' => 'Rapat Ongoing',
            'agenda' => 'Pembahasan',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Pola',
            'status' => 'ongoing',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Volt::test('meetings.notulen', ['meeting' => $meeting])
            ->set('content', 'Catatan sementara yang sedang berjalan.')
            ->call('processAi')
            ->assertSet('showAiModal', true)
            ->assertHasNoErrors()
            ->assertSet('aiResult', "I. PEMBUKAAN\nRapat sedang berjalan.");
    }

    public function test_notulen_ai_is_forbidden_if_minutes_already_signed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('pegawai');

        $meeting = Meeting::create([
            'title' => 'Rapat Selesai TTE',
            'agenda' => 'Pembahasan',
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Ruang Pola',
            'status' => 'completed',
            'minutes_signed_at' => now(),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Volt::test('meetings.notulen', ['meeting' => $meeting])
            ->set('content', 'Catatan setelah TTE.')
            ->call('processAi')
            ->assertStatus(403);
    }
}
