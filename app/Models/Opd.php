<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opd extends Model
{
    use HasFactory;

    protected $table = 'opds';

    protected $fillable = [
        'unit_id',
        'name',
        'address',
        'phone',
        'email',
        'leader_name',
        'leader_rank',
        'leader_nip',
        'leader_title',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'unit_name', 'name');
    }

    public function signers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OpdSigner::class);
    }

    /**
     * Standardized official address mapping for Sinjai OPDs.
     */
    public static function getStandardAddresses(): array
    {
        return [
            '730701' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
            '730702' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
            '730705' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
            '730706' => 'Jl. Bulo-Bulo Barat No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730707' => 'Jl. Stadion Mini No. 7, Sinjai Utara, Kab. Sinjai 92611',
            '730708' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
            '730709' => 'Jl. Persatuan Raya No. 101, Sinjai Utara, Kab. Sinjai 92611',
            '730710' => 'Jl. Bulo-Bulo Timur No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730711' => 'Jl. Jend. Ahmad Yani No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730712' => 'Jl. Persatuan Raya No. 116, Sinjai Utara, Kab. Sinjai 92611',
            '730713' => 'Jl. H. Abdul Latief No. 8, Sinjai Utara, Kab. Sinjai 92611',
            '730714' => 'Jl. Persatuan Raya No. 101, Sinjai Utara, Kab. Sinjai 92611',
            '730715' => 'Jl. Bulo-Bulo Barat No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730716' => 'Jl. Bulu Pattuku, Kel. Bongki, Kec. Sinjai Utara, Kab. Sinjai 92613',
            '730717' => 'Jl. Lamatti No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730718' => 'Jl. Persatuan Raya No. 121, Sinjai Utara, Kab. Sinjai 92611',
            '730720' => 'Jl. Persatuan Raya No. 98, Sinjai Utara, Kab. Sinjai 92611',
            '730721' => 'Jl. Jend. Ahmad Yani No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730722' => 'Jl. Jenderal Sudirman No. 4, Sinjai Utara, Kab. Sinjai 92611',
            '730723' => 'Jl. Jenderal Sudirman No. 2, Sinjai Utara, Kab. Sinjai 92611',
            '730724' => 'Jl. Lamatti No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730725' => 'Jl. Persatuan Raya No. 116, Sinjai Utara, Kab. Sinjai 92611',
            '730726' => 'Jl. Persatuan Raya No. 116, Sinjai Utara, Kab. Sinjai 92611',
            '730727' => 'Jl. Jenderal Sudirman No. 3, Sinjai Utara, Kab. Sinjai 92611',
            '730728' => 'Jl. Jenderal Sudirman No. 47, Sinjai Utara, Kab. Sinjai 92611',
            '730729' => 'Jl. Persatuan Raya No. 134, Sinjai Utara, Kab. Sinjai 92611',
            '730730' => 'Jl. Kartini No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730731' => 'Jl. Persatuan Raya No. 141, Sinjai Utara, Kab. Sinjai 92611',
            '730732' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
            '730733' => 'Jl. Bulu Kunyi No. 1, Sinjai Utara, Kab. Sinjai 92612',
            '730734' => 'Jl. Abd. Latif No. 1, Sinjai Timur, Kab. Sinjai 92611',
            '730735' => 'Jl. Damai No. 1, Lappadata, Kec. Sinjai Tengah, Kab. Sinjai 92652',
            '730736' => 'Jl. Persatuan Raya Bikeru No. 1B, Kec. Sinjai Selatan, Kab. Sinjai 92661',
            '730737' => 'Jl. Persatuan Raya No. A.69, Manipi, Kec. Sinjai Barat, Kab. Sinjai 92653',
            '730738' => 'Jl. Pendidikan No. 64, Pasir Putih, Kec. Sinjai Borong, Kab. Sinjai 92622',
            '730739' => 'Mannanti, Kec. Tellu Limpoe, Kab. Sinjai 92662',
            '730740' => 'Lamatti Riawang, Kec. Bulupoddo, Kab. Sinjai 92651',
            '730741' => 'Pulau Harapan, Kec. Pulau Sembilan, Kab. Sinjai 92655',
            '730743' => 'Jl. Jenderal Sudirman No. 19, Sinjai Utara, Kab. Sinjai 92611',
            '730745' => 'Jl. H. A. Abdul Latief No. 1, Sinjai Utara, Kab. Sinjai 92612',
            '730746' => 'Jl. Jenderal Sudirman No. 21, Sinjai Utara, Kab. Sinjai 92615',
            '730747' => 'Jl. Persatuan Raya No. 101, Sinjai Utara, Kab. Sinjai 92611',
            '7307'   => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
        ];
    }

    /**
     * Clean and normalize OPD name, address, phone, and email from API.
     */
    public static function cleanAndFormatData(array $unit): array
    {
        $rawName = trim($unit['unit_nama'] ?? '');
        $unitId = $unit['unit_id'] ?? null;

        // Normalize all-caps names
        if ($rawName === mb_strtoupper($rawName) && mb_strlen($rawName) > 4) {
            $name = mb_convert_case($rawName, MB_CASE_TITLE, "UTF-8");
        } else {
            $name = $rawName;
        }

        $rawAddress = $unit['unit_alamat'] ?? '';
        $address = str_ireplace(['<br>', '<br/>', '<br />', '&nbsp;'], ', ', $rawAddress);
        $address = strip_tags($address);
        $address = html_entity_decode($address);
        $address = ltrim($address, ": \t\n\r\0\x0B");

        // Extract email if available in raw address or unit array
        $email = $unit['email'] ?? null;
        if (preg_match('/(?:Email\s*:\s*|E-mail\s*:\s*)([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $rawAddress, $m)) {
            $email = strtolower(trim($m[1]));
        }

        // Extract phone if available in raw address or unit array
        $phone = $unit['phone'] ?? null;
        if (preg_match('/(?:Tlp\.?|Telp\.?|Telepon|Phone|Fax\.?|Telp\/Fax\.?)\s*[:.]?\s*([0-9\(\)\s\-\/\.,]+)/i', $rawAddress, $m)) {
            $rawPhone = trim($m[1], ",. \t\n\r/");
            $rawPhone = preg_replace('/\s*(?:Kode|Fax|Email|Provinsi|Kabupaten).*/i', '', $rawPhone);
            $rawPhone = trim($rawPhone, ",. \t\n\r/");
            if (strlen($rawPhone) >= 4) {
                $phone = $rawPhone;
            }
        }

        // Use official standard address
        $standards = self::getStandardAddresses();
        if ($unitId && isset($standards[$unitId])) {
            $address = $standards[$unitId];
        } else {
            // Clean up address
            $address = preg_replace('/(?:Email\s*:\s*|E-mail\s*:\s*)[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/i', '', $address);
            $address = preg_replace('/(?:Tlp\.?|Telp\.?|Telepon|Phone|Fax\.?|Telp\/Fax\.?)\s*[:.]?\s*[\(\)\d\s\-\/\.]+/i', '', $address);
            $address = preg_replace('/^(?:Jalan|Jln|Jl|JL)\.?\s*/i', 'Jl. ', $address);
            $address = preg_replace('/,\s*(?:Jalan|Jln|Jl|JL)\.?\s*/i', ', Jl. ', $address);
            $address = preg_replace('/^J\.\s+/i', 'Jl. ', $address);
            $address = str_ireplace(['Sunjai', 'SInjai', 'Bulo – Bulo', 'Bulo — Bulo'], ['Sinjai', 'Sinjai', 'Bulo-Bulo', 'Bulo-Bulo'], $address);

            if ($address === strtoupper($address) && strlen($address) > 5) {
                $address = ucwords(strtolower($address));
                $address = preg_replace('/\bJl\b/i', 'Jl.', $address);
                $address = preg_replace('/\bNo\b/i', 'No.', $address);
            }

            $address = preg_replace('/,\s*,+/', ',', $address);
            $address = preg_replace('/\s{2,}/', ' ', trim($address, ",. \t\n\r\0\x0B"));
            if ($address === '0' || empty($address)) {
                $address = null;
            }
        }

        return [
            'unit_id' => $unitId,
            'name' => trim($name),
            'address' => $address,
            'phone' => $phone ? trim($phone) : null,
            'email' => $email ? trim($email) : null,
            'is_active' => true,
        ];
    }

    /**
     * Clean and format bidang/unit name based on job title.
     */
    public static function cleanBidangName(string $title): string
    {
        $title = trim($title);
        if (preg_match('/^(?:Plt\.\s+)?Sekretaris\b/i', $title)) {
            return str_starts_with($title, 'Plt.') ? 'Sekretariat (Plt. Sekretaris)' : 'Sekretariat';
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(Bidang\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . $m[1];
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(Bagian\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . $m[1];
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(Sub\s*bagian\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . preg_replace('/^Sub\s*bagian/i', 'Sub Bagian', $m[1]);
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(Seksi\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . $m[1];
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(UPT\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . $m[1];
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(Tata\s+Usaha\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . $m[1];
        }
        if (preg_match('/^(?:Plt\.\s+)?Lurah\s+(.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . 'Kelurahan ' . $m[1];
        }
        return $title;
    }

    /**
     * Synchronize officials (pejabat bereselon) from SIMPEG API.
     */
    public function syncSignersFromApi(): int
    {
        if (!$this->unit_id) {
            return 0;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(8)->get('http://apps.sinjaikab.go.id/api/pegawai/get_pegawai/', [
                'unit_id' => $this->unit_id,
            ]);

            if (!$response->successful()) {
                return 0;
            }

            $pegawaiList = $response->json();
            if (!is_array($pegawaiList)) {
                return 0;
            }

            // Sync Kepala OPD (check peran_khusus === 'KEPALA' or detect head title patterns)
            foreach ($pegawaiList as $p) {
                $isHeadRole = ($p['peran_khusus'] ?? '') === 'KEPALA';
                $jobTitle = trim($p['jabatan_nama'] ?? '');
                $eselon = strtoupper(trim((string)($p['jabatan_jenis_eselon'] ?? '')));

                $isHeadTitle = preg_match('/^(?:Plt\.\s+)?(?:Kepala\s+(?:Dinas|Badan|Satuan|Kantor|Pelaksana)|Camat|Inspektur|Sekretaris\s+Daerah|Direktur)\b/i', $jobTitle)
                    || (in_array($eselon, ['II.A', 'II.B'], true) && preg_match('/^(?:Plt\.\s+)?(?:Kepala|Pj\.|Pjs\.)\b/i', $jobTitle));

                if ($isHeadRole || $isHeadTitle) {
                    $pangkat = trim((string)($p['pangkat_nama'] ?? ''));
                    $this->update([
                        'leader_name' => $p['nama'],
                        'leader_nip' => $p['nip'],
                        'leader_title' => $jobTitle ?: $this->leader_title,
                        'leader_rank' => $pangkat ?: null,
                    ]);
                    break;
                }
            }

            $allowedEselons = ['II.A', 'II.B', 'III.A', 'III.B', 'IV.A'];

            // Delete any existing signers below Eselon IV.a (e.g. IV.b) for this OPD
            $this->signers()->whereNotIn('eselon', ['II.a', 'II.b', 'III.a', 'III.b', 'IV.a'])->delete();

            $syncedCount = 0;
            foreach ($pegawaiList as $p) {
                $eselon = trim((string)($p['jabatan_jenis_eselon'] ?? ($p['eselon'] ?? null)));
                if (empty($eselon) || $eselon === '-' || $eselon === 'null') {
                    continue;
                }

                // Filter up to Eselon IV.a only (exclude IV.b, V, etc.)
                if (!in_array(strtoupper($eselon), $allowedEselons, true)) {
                    continue;
                }

                // If this is the main Kepala OPD, skip adding to sub-bidang
                if (!empty($this->leader_nip) && ($p['nip'] ?? '') === $this->leader_nip) {
                    continue;
                }

                $title = trim($p['jabatan_nama'] ?? '');
                if (empty($title)) {
                    continue;
                }

                $bidangName = self::cleanBidangName($title);
                $pangkat = trim((string)($p['pangkat_nama'] ?? ''));

                OpdSigner::updateOrCreate(
                    [
                        'opd_id' => $this->id,
                        'title' => $title,
                    ],
                    [
                        'bidang_name' => $bidangName,
                        'name' => trim($p['nama'] ?? ''),
                        'nip' => !empty($p['nip']) ? trim($p['nip']) : null,
                        'rank' => $pangkat ?: null,
                        'eselon' => $eselon,
                        'is_active' => true,
                    ]
                );
                $syncedCount++;
            }

            return $syncedCount;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error syncing signers: ' . $e->getMessage());
            return 0;
        }
    }
}
