<?php

namespace App\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string')]
    public string $nip = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // NIP login via API
        try {
            $response = \Illuminate\Support\Facades\Http::get('http://apps.sinjaikab.go.id/api/pegawai/user_auth/', [
                'username' => $this->nip,
                'password' => $this->password
            ]);
            
            $body = trim($response->body());
            
            if ($response->successful() && !empty($body)) {
                
                // Auth success! Fetch employee data
                $pegawaiResponse = \Illuminate\Support\Facades\Http::get('http://apps.sinjaikab.go.id/api/pegawai/data_pegawai/', [
                    'nip' => $this->nip
                ]);
                
                $pegawaiData = $pegawaiResponse->json();
                
                $pData = isset($pegawaiData['data']) ? $pegawaiData['data'] : (isset($pegawaiData[0]) ? $pegawaiData[0] : $pegawaiData);
                
                $name = $pData['nama_pegawai'] ?? $pData['nama'] ?? $this->nip;
                $unit_id = $pData['unit_id'] ?? $pData['id_unit'] ?? null;
                $jabatan = $pData['jabatan_nama'] ?? $pData['jabatan'] ?? null;
                $unit_name = null;
                
                if ($unit_id) {
                    $unitResponse = \Illuminate\Support\Facades\Http::get('http://apps.sinjaikab.go.id/api/pegawai/get_unit/', [
                        'unit_id' => $unit_id
                    ]);
                    $unitData = $unitResponse->json();
                    $uData = isset($unitData['data']) ? $unitData['data'] : (isset($unitData[0]) ? $unitData[0] : $unitData);
                    $unit_name = $uData['unit_nama'] ?? $uData['nama_unit'] ?? $uData['unit_kerja'] ?? null;
                }
                
                $user = \App\Models\User::updateOrCreate(
                    ['nip' => $this->nip],
                    [
                        'name' => $name,
                        'email' => $this->nip . '@pegawai.sinjaikab.go.id',
                        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24)),
                        'jabatan' => $jabatan,
                        'unit_name' => $unit_name
                    ]
                );
                
                if ($user->roles->count() == 0) {
                    $user->assignRole('pegawai');
                }
                
                Auth::login($user, $this->remember);
                
            } else {
                // If API fails, try local DB fallback (useful for seeded dummy data)
                if (Auth::attempt(['nip' => $this->nip, 'password' => $this->password], $this->remember)) {
                    RateLimiter::clear($this->throttleKey());
                    return;
                }

                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'form.nip' => 'NIP atau Password salah.',
                ]);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            // Fallback to local auth if API is totally down
            if (Auth::attempt(['nip' => $this->nip, 'password' => $this->password], $this->remember)) {
                RateLimiter::clear($this->throttleKey());
                return;
            }

            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'form.nip' => 'Koneksi ke server kepegawaian gagal.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.nip' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->nip).'|'.request()->ip());
    }
}
