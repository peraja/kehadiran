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

    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $nip = trim($this->nip);
        $password = $this->password;

        // 1. Try API Kepegawaian Authentication
        try {
            $authResponse = \Illuminate\Support\Facades\Http::timeout(6)->get('http://apps.sinjaikab.go.id/api/pegawai/user_auth/', [
                'username' => $nip,
                'password' => $password
            ]);

            $authBody = trim($authResponse->body());

            if ($authResponse->successful() && !empty($authBody)) {
                // API Auth Succeeded -> Fetch full employee details
                $pegawaiResponse = \Illuminate\Support\Facades\Http::timeout(5)->get('http://apps.sinjaikab.go.id/api/pegawai/data_pegawai/', [
                    'nip' => $nip
                ]);

                $pegawaiData = $pegawaiResponse->json();
                $pData = isset($pegawaiData['data']) ? $pegawaiData['data'] : (isset($pegawaiData[0]) ? $pegawaiData[0] : $pegawaiData);

                $name = $pData['nama_pegawai'] ?? $pData['nama'] ?? $nip;
                $unit_id = $pData['unit_id'] ?? $pData['id_unit'] ?? null;
                $jabatan = $pData['jabatan_nama'] ?? $pData['jabatan'] ?? null;
                $unit_name = null;

                if ($unit_id) {
                    $unitResponse = \Illuminate\Support\Facades\Http::timeout(4)->get('http://apps.sinjaikab.go.id/api/pegawai/get_unit/', [
                        'unit_id' => $unit_id
                    ]);
                    $unitData = $unitResponse->json();
                    $uData = isset($unitData['data']) ? $unitData['data'] : (isset($unitData[0]) ? $unitData[0] : $unitData);
                    $unit_name = $uData['unit_nama'] ?? $uData['nama_unit'] ?? $uData['unit_kerja'] ?? null;
                }

                $user = \App\Models\User::updateOrCreate(
                    ['nip' => $nip],
                    [
                        'name' => $name,
                        'jabatan' => $jabatan,
                        'unit_name' => $unit_name
                    ]
                );

                if ($user->roles->count() === 0) {
                    $user->assignRole('pegawai');
                }

                Auth::login($user, $this->remember);
                RateLimiter::clear($this->throttleKey());
                return;
            }
        } catch (\Exception $e) {
            // If API connection error, proceed to check local DB fallback
        }

        // 2. Check Local Database fallback (seeded accounts / local users)
        if (Auth::attempt(['nip' => $nip, 'password' => $password], $this->remember)) {
            RateLimiter::clear($this->throttleKey());
            return;
        }

        RateLimiter::hit($this->throttleKey());

        // Check if user exists locally or in API Kepegawaian
        $userExistsLocally = \App\Models\User::where('nip', $nip)->exists();
        $userExistsInApi = false;

        if (!$userExistsLocally) {
            try {
                $checkPegawai = \Illuminate\Support\Facades\Http::timeout(3)->get('http://apps.sinjaikab.go.id/api/pegawai/data_pegawai/', [
                    'nip' => $nip
                ]);
                if ($checkPegawai->successful()) {
                    $checkData = $checkPegawai->json();
                    $cData = isset($checkData['data']) ? $checkData['data'] : (isset($checkData[0]) ? $checkData[0] : $checkData);
                    if (!empty($cData['nama'] ?? $cData['nama_pegawai'] ?? null)) {
                        $userExistsInApi = true;
                    }
                }
            } catch (\Exception $e) {
                // Ignore API connection issue
            }
        }

        if (!$userExistsLocally && !$userExistsInApi) {
            throw ValidationException::withMessages([
                'form.nip' => 'NIP tidak terdaftar.',
            ]);
        }

        throw ValidationException::withMessages([
            'form.password' => 'Kata sandi salah.',
        ]);
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
