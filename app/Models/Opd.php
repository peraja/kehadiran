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
        'leader_nik',
        'leader_title',
        'leader_eselon',
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
     * Centralized Position and Unit Normalization
     */
    public static function normalizePosition(?string $parentUnit, ?string $childUnit, ?string $rawJabatan): array
    {
        $displayUnit = $parentUnit ?: 'Pemerintah Kabupaten Sinjai';
        $jabatan = $rawJabatan ?: '-';

        // 1. Bersihkan spasi berlebih
        $jabatan = preg_replace('/\s+/', ' ', trim($jabatan));

        // 2. Tentukan display unit untuk satuan khusus (Kelurahan, Sekolah, Puskesmas/Faskes)
        if ($parentUnit && !empty($childUnit)) {
            $parentLower = strtolower($parentUnit);
            $childLower = strtolower($childUnit);

            if (str_contains($parentLower, 'kecamatan')) {
                $kelurahanMap = [
                    'bongki' => 'Kantor Kelurahan Bongki',
                    'biringere' => 'Kantor Kelurahan Biringere',
                    'lappa' => 'Kantor Kelurahan Lappa',
                    'balangnipa' => 'Kantor Kelurahan Balangnipa',
                    'alehanuae' => 'Kantor Kelurahan Alehanuae',
                    'lamatti' => 'Kantor Kelurahan Lamatti Rilau',
                    'samataring' => 'Kantor Kelurahan Samataring',
                    'samaenre' => 'Kantor Kelurahan Samaenre',
                    'sangiasseri' => 'Kantor Kelurahan Sangiasseri',
                    'sangiaserri' => 'Kantor Kelurahan Sangiasseri',
                    'balakia' => 'Kantor Kelurahan Balakia',
                    'tassililu' => 'Kantor Kelurahan Tassililu',
                    'pasir putih' => 'Kantor Kelurahan Pasir Putih',
                    'pair putih' => 'Kantor Kelurahan Pasir Putih',
                    'mannanti' => 'Kantor Kelurahan Mannanti',
                ];

                $matchedKelurahan = null;
                foreach ($kelurahanMap as $key => $officialKelurahan) {
                    if (str_contains($childLower, $key)) {
                        $matchedKelurahan = $officialKelurahan;
                        break;
                    }
                }

                $jLower = strtolower(trim((string)$jabatan));
                $isPlt = str_starts_with($jLower, 'plt.') || str_starts_with($jLower, 'plt ') || str_starts_with($jLower, 'plt.kepala') || str_starts_with($jLower, 'plt.sekretaris');
                $prefix = $isPlt ? 'Plt. ' : '';
                $cleanPos = preg_replace('/^plt\.?\s*/i', '', (string)$jabatan);

                if ($matchedKelurahan) {
                    $displayUnit = $matchedKelurahan;

                    if (preg_match('/^sekretaris\s+(kelurahan|lurah)\b/i', $cleanPos) || str_contains($jLower, 'sekretaris kelurahan') || str_contains($jLower, 'sekretaris lurah')) {
                        $jabatan = $prefix . 'Sekretaris Lurah';
                    } elseif (preg_match('/^lurah\b/i', $cleanPos) || (str_contains($jLower, 'lurah') && !str_contains($jLower, 'seksi') && !str_contains($jLower, 'kasi'))) {
                        $jabatan = $prefix . 'Lurah';
                    } elseif (str_contains($jLower, 'penelaah')) {
                        $jabatan = 'Penelaah Teknis Kebijakan';
                    } elseif (str_contains($jLower, 'pengolah data') || str_contains($jLower, 'pengolah  data')) {
                        $jabatan = 'Pengolah Data dan Informasi';
                    } elseif (str_contains($jLower, 'pengadministrasi')) {
                        $jabatan = 'Pengadministrasi Perkantoran';
                    } elseif (str_contains($jLower, 'seksi') || str_contains($jLower, 'kasi')) {
                        if (str_contains($jLower, 'pelayanan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pelayanan Umum';
                        } elseif (str_contains($jLower, 'pemberdayaan') || str_contains($jLower, 'pembangunan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pembangunan dan Pemberdayaan Masyarakat';
                        } elseif (str_contains($jLower, 'pemerintahan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pemerintahan';
                        }
                    }
                } else {
                    $displayUnit = $parentUnit;

                    if (preg_match('/^sekretaris\s+(camat|kecamatan)\b/i', $cleanPos) || str_contains($jLower, 'sekretaris camat') || str_contains($jLower, 'sekretaris kecamatan')) {
                        $jabatan = $prefix . 'Sekretaris Kecamatan';
                    } elseif (preg_match('/^camat\b/i', $cleanPos) || (str_contains($jLower, 'camat') && !str_contains($jLower, 'seksi') && !str_contains($jLower, 'kasi') && !str_contains($jLower, 'sub bagian') && !str_contains($jLower, 'kasubag'))) {
                        $jabatan = $prefix . 'Camat';
                    } elseif (str_contains($jLower, 'penelaah')) {
                        $jabatan = 'Penelaah Teknis Kebijakan';
                    } elseif (str_contains($jLower, 'pengolah data') || str_contains($jLower, 'pengolah  data')) {
                        $jabatan = 'Pengolah Data dan Informasi';
                    } elseif (str_contains($jLower, 'pengadministrasi')) {
                        $jabatan = 'Pengadministrasi Perkantoran';
                    } elseif (str_contains($jLower, 'umum dan kepegawaian')) {
                        $jabatan = $prefix . 'Kepala Sub Bagian Umum dan Kepegawaian';
                    } elseif (str_contains($jLower, 'program dan keuangan') || str_contains($jLower, 'perencanaan dan keuangan')) {
                        $jabatan = $prefix . 'Kepala Sub Bagian Program dan Keuangan';
                    } elseif (str_contains($jLower, 'seksi') || str_contains($jLower, 'kasi')) {
                        if (str_contains($jLower, 'pelayanan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pelayanan Umum';
                        } elseif (str_contains($jLower, 'ketentraman') || str_contains($jLower, 'trantib') || str_contains($jLower, 'keamanan')) {
                            $jabatan = $prefix . 'Kepala Seksi Ketentraman dan Ketertiban Umum';
                        } elseif (str_contains($jLower, 'ekonomi') || str_contains($jLower, 'kesejahteraan')) {
                            $jabatan = $prefix . 'Kepala Seksi Ekonomi dan Kesejahteraan Rakyat';
                        } elseif (str_contains($jLower, 'pembangunan') || str_contains($jLower, 'pemberdayaan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pembangunan dan Pemberdayaan Masyarakat';
                        } elseif (str_contains($jLower, 'pemerintahan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pemerintahan';
                        }
                    }
                }
            }
            // 2. Dinas Pendidikan (tampilkan nama Sekolah / Satuan Pendidikan saja)
            elseif (str_contains($parentLower, 'pendidikan')) {
                if (!str_contains($childLower, 'bidang') && !str_contains($childLower, 'sekretariat') && !str_contains($childLower, 'sub bagian') && !str_contains($childLower, 'seksi')) {
                    if (preg_match('/(sd|smp|tk|paud|negeri|sekolah|spf|uptd)/i', $childUnit)) {
                        $displayUnit = self::formatSchoolUnitName($childUnit);
                    }
                }
            }
            // 3. Dinas Kesehatan & RSUD (tampilkan nama Puskesmas / RSUD / RS Pratama / Faskes saja)
            elseif (str_contains($parentLower, 'kesehatan')) {
                if (preg_match('/(puskesmas|rsud|rs\s+pratama|bulupac|bulupan|pustu|faskes|uptd|klinik|lab|psc)/i', $childUnit)) {
                    $displayUnit = self::formatHealthUnitName($childUnit);
                }
            }
        }

        if (str_contains(strtolower($displayUnit), 'rumah sakit umum daerah')) {
            $displayUnit = 'RSUD Sinjai';
        } elseif (str_contains(strtolower($displayUnit), 'kesehatan') && preg_match('/(rsud|rs\s+pratama|bulupac|bulupan)/i', (string)$rawJabatan)) {
            $displayUnit = 'RS Pratama Bulupaccing';
        }

        // 4. Normalisasi Global Jabatan Dinas, Badan, Inspektorat, Setda, Setwan
        $jLower = strtolower(trim((string)$jabatan));
        $isPlt = str_starts_with($jLower, 'plt.') || str_starts_with($jLower, 'plt ') || str_starts_with($jLower, 'plt.kepala') || str_starts_with($jLower, 'plt.sekretaris');
        $prefix = $isPlt ? 'Plt. ' : '';

        $clean = preg_replace('/^plt\.?\s*/i', '', $jabatan);
        $clean = preg_replace('/^plt\.kepala\s*/i', 'Kepala ', $clean);
        $clean = preg_replace('/^plt\.sekretaris\s*/i', 'Sekretaris ', $clean);

        // A. Pimpinan Utama OPD, Puskesmas & Sekolah
        if (preg_match('/^kepala dinas\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Dinas';
        } elseif (preg_match('/^sekretaris dinas\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris Dinas';
        } elseif (preg_match('/^kepala badan\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Badan';
        } elseif (preg_match('/^sekretaris badan\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris Badan';
        } elseif (preg_match('/^(kepala\s+satuan|kasat)\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Satuan';
        } elseif (preg_match('/^sekretaris\s+satuan\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris Satuan';
        } elseif (preg_match('/^inspektur daerah\b/i', $clean) || (preg_match('/^inspektur\b/i', $clean) && !preg_match('/inspektur pembantu/i', $clean))) {
            $jabatan = $prefix . 'Inspektur Daerah';
        } elseif (preg_match('/^sekretaris dprd\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris DPRD';
        } elseif (preg_match('/^sekretaris daerah\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris Daerah';
        } elseif (preg_match('/^sekretaris\s+(camat|kecamatan)\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris Kecamatan';
        } elseif (preg_match('/^camat\b/i', $clean)) {
            $jabatan = $prefix . 'Camat';
        } elseif (preg_match('/^sekretaris\s+(lurah|kelurahan)\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris Lurah';
        } elseif (preg_match('/^lurah\b/i', $clean) && !preg_match('/^(seksi|kasi)/i', $clean)) {
            $jabatan = $prefix . 'Lurah';
        } elseif (preg_match('/^ktu\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Sub Bagian Tata Usaha';
        } elseif (preg_match('/^kepala\s+(uptd\s+)?puskesmas\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Puskesmas';
        } elseif (preg_match('/^kepala\s+(uptd\s+)?(sd|sdn|smp|smpn|tk|paud|sekolah)\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Sekolah';
        } elseif (preg_match('/^direktur\b/i', $clean)) {
            $jabatan = $prefix . 'Direktur';
        } else {
            // B. Bersihkan akhiran wilayah/induk pada jabatan dinas/badan/rsud/puskesmas/sekolah/kecamatan/kelurahan
            $clean = preg_replace('/\s+(sekretariat\s+)?(dinas|badan|satpol|satuan\s+polisi|inspektorat)\s+.*$/i', '', $clean);
            $clean = preg_replace('/\s+(kec\.?|kecamatan|kel\.?|kelurahan)\s+.*$/i', '', $clean);
            $clean = preg_replace('/\s+(pada|di)\s+.+$/i', '', $clean);
            $clean = preg_replace('/\s+(uptd\s+)?(rsud|rs|puskesmas)\b.*$/i', '', $clean);
            $clean = preg_replace('/\s+(uptd\s+)?(sdn|smpn)\s+\d+.*$/i', '', $clean);
            $clean = preg_replace('/\s+kab\.?\s+sinjai.*$/i', '', $clean);
            $clean = preg_replace('/\s+kabupaten\s+sinjai.*$/i', '', $clean);
            $clean = preg_replace('/\s+sekretariat\s+daerah.*$/i', '', $clean);
            $clean = preg_replace('/\s+sekretariat\s+dprd.*$/i', '', $clean);

            // C. Staf pelaksana & fungsional
            if (preg_match('/^penelaah\b/i', $clean)) {
                $clean = 'Penelaah Teknis Kebijakan';
            } elseif (preg_match('/^pengolah\s+data\b/i', $clean)) {
                $clean = 'Pengolah Data dan Informasi';
            } elseif (preg_match('/^pengadministrasi\b/i', $clean)) {
                $clean = 'Pengadministrasi Perkantoran';
            } elseif (preg_match('/^pelak?a?sa?na\s+(bidan|perawat|dokter|nutrisionis|sanitarian|sanitasi|penyuluh|apoteker|asisten\s+apoteker|pranata)/i', $clean)) {
                $clean = preg_replace('/^pelak?a?sa?na\s+/i', '', $clean);
                if (preg_match('/\bpenyuluh\s+kesmas\b/i', $clean)) {
                    $clean = 'Penyuluh Kesehatan Masyarakat';
                }
                $clean = self::formatJabatanTitleCase($clean);
            } elseif (preg_match('/^(ahli\s+(pertama|muda|madya|utama))\s*-\s*(.+)$/i', $clean, $m)) {
                $jenjang = ucwords(strtolower($m[1]));
                $namaJbt = self::formatJabatanTitleCase($m[3]);
                $clean = $namaJbt . ' ' . $jenjang;
            } elseif (preg_match('/^(terampil|mahir|penyelia|pemula)\s*-\s*(.+)$/i', $clean, $m)) {
                $jenjang = ucfirst(strtolower($m[1]));
                $namaJbt = self::formatJabatanTitleCase($m[2]);
                $clean = $namaJbt . ' ' . $jenjang;
            } elseif (preg_match('/^(kepala\s+bidang|kabid|kepala\s+bagian|kabag|kepala\s+seksi|kasi|kepala\s+sub\s+bagian|kasubag|kepala\s+sub\s+bidang|kasubid)\b/i', $clean)) {
                $clean = self::formatJabatanTitleCase($clean);
            } else {
                $clean = self::formatJabatanTitleCase($clean);
            }

            $jabatan = $prefix . trim($clean);
        }

        return [
            'jabatan' => $jabatan,
            'unit' => $displayUnit,
            'is_plt' => $isPlt,
        ];
    }

    public static function formatJabatanTitleCase(string $str): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($str));
        $clean = preg_replace('/,\s*/', ', ', $clean);
        $words = explode(' ', $clean);
        $lowerWords = ['dan', 'atau', 'di', 'ke', 'dari', 'pada', 'untuk', 'tentang', 'yang', 'serta', 'per'];
        $upperWords = ['sd', 'smp', 'sma', 'smk', 'tk', 'paud', 'pnf', 'sda', 'sdm', 'asn', 'pns', 'b3', 'tik', 'dprd', 'rsud', 'uptd', 'ppkn', 'ipa', 'ips', 'ktu', 'tu', 'bappeda', 'bkpsdma', 'bkad', 'dpmptsp', 'dinsos', 'dishub', 'disdik', 'dinkes', 'bpbd', 'satpol', 'pp', 'damkar', 'dlhk', 'kesbangpol', 'i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x'];

        $formatted = [];
        foreach ($words as $i => $w) {
            $prefixPunct = '';
            $suffixPunct = '';
            if (preg_match('/^([\(])(.*)$/', $w, $m)) {
                $prefixPunct = $m[1];
                $w = $m[2];
            }
            if (preg_match('/^(.*)([\),.:])$/', $w, $m)) {
                $suffixPunct = $m[2];
                $w = $m[1];
            }

            $wLower = strtolower($w);
            if (str_contains($w, '/')) {
                $subParts = explode('/', $w);
                $formattedSubParts = [];
                foreach ($subParts as $subPart) {
                    $subLower = strtolower($subPart);
                    if (in_array($subLower, $upperWords)) {
                        $formattedSubParts[] = strtoupper($subLower);
                    } else {
                        $formattedSubParts[] = ucfirst($subLower);
                    }
                }
                $resWord = implode('/', $formattedSubParts);
            } elseif (in_array($wLower, $upperWords)) {
                $resWord = strtoupper($wLower);
            } elseif ($i > 0 && in_array($wLower, $lowerWords)) {
                $resWord = $wLower;
            } else {
                $resWord = ucfirst($wLower);
            }

            $formatted[] = $prefixPunct . $resWord . $suffixPunct;
        }

        return implode(' ', $formatted);
    }

    public static function formatSchoolUnitName(string $str): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($str));
        $clean = preg_replace('/\s+kec\.?\s*.+$/i', '', $clean);
        $clean = preg_replace('/\s+kab\.?\s*sinjai.*$/i', '', $clean);

        if (preg_match('/^(uptd\s+)?smp(n|\s+negeri|\s+neg\.?)?\s*(\d+)(\s+sinjai)?(\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))?/i', $clean, $m)) {
            $nomor = $m[3];
            return 'UPTD SMP Negeri ' . $nomor . ' Sinjai';
        }

        if (preg_match('/^sdn\.?\s*(no\.?)?\s*(\d+)\s*(.*)$/i', $clean, $m)) {
            $nomor = $m[2];
            $lokasi = trim($m[3]);
            $lokasi = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $lokasi);
            $words = explode(' ', $lokasi);
            $words = array_map(fn($w) => ucfirst(strtolower($w)), $words);
            return 'SDN No. ' . $nomor . ($lokasi ? ' ' . implode(' ', $words) : '');
        }

        if (preg_match('/^sd\s+negeri\s*(\d+)\s*(.*)$/i', $clean, $m)) {
            $nomor = $m[1];
            $lokasi = trim($m[2]);
            $lokasi = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $lokasi);
            $words = explode(' ', $lokasi);
            $words = array_map(fn($w) => ucfirst(strtolower($w)), $words);
            return 'SD Negeri ' . $nomor . ($lokasi ? ' ' . implode(' ', $words) : '');
        }

        if (preg_match('/^tk\s+(negeri|neg\.?)\s*(pembina)?\s*(.*)$/i', $clean, $m)) {
            $isPembina = !empty($m[2]);
            $lokasi = trim($m[3]);
            $lokasi = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $lokasi);
            $words = explode(' ', $lokasi);
            $words = array_map(fn($w) => ucfirst(strtolower($w)), $words);
            $cleanLokasi = implode(' ', $words);
            return ($isPembina ? 'TK Negeri Pembina' : 'TK Negeri') . ($cleanLokasi ? ' ' . $cleanLokasi : '');
        }

        $words = explode(' ', $clean);
        $upperKeywords = ['sd', 'sdn', 'smp', 'smpn', 'sma', 'sman', 'smk', 'smkn', 'tk', 'paud', 'uptd', 'spf', 'no.', 'ii', 'iii', 'iv', 'vi', 'vii', 'viii', 'ix', 'x'];

        $formatted = [];
        foreach ($words as $w) {
            $wLower = strtolower($w);
            if (in_array($wLower, $upperKeywords)) {
                $formatted[] = strtoupper($wLower);
            } else {
                $formatted[] = ucfirst($wLower);
            }
        }

        return preg_replace('/\s+/', ' ', trim(implode(' ', $formatted)));
    }

    public static function formatHealthUnitName(string $str): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($str));
        $clean = preg_replace('/\s+kec\.?\s*.+$/i', '', $clean);
        $clean = preg_replace('/\s+kab\.?\s*sinjai.*$/i', '', $clean);

        if (preg_match('/(rsud|rs)\b.*(bulupancing|bulupaccing|bulu\s+paccing)/i', $clean)) {
            return 'RS Pratama Bulupaccing';
        }

        if (preg_match('/^(rumah\s+sakit\s+umum\s+daerah|rsud)\s*(sinjai)?/i', $clean)) {
            return 'RSUD Sinjai';
        }

        if (preg_match('/(laboratorium|lab\.?\s*kes)/i', $clean)) {
            return 'UPTD Laboratorium Kesehatan Daerah';
        }

        if (preg_match('/(psc|public\s+safety\s+center)\s*119/i', $clean)) {
            return 'UPTD PSC 119';
        }

        if (preg_match('/(puskesmas\s+pembantu|pustu)\b.*(uptd\s+)?puskesmas\s+([a-z\s]+)/i', $clean, $m)) {
            $induk = trim($m[3]);
            $induk = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $induk);
            return self::formatHealthUnitName('UPTD Puskesmas ' . $induk);
        }
        if (preg_match('/(puskesmas\s+pembantu|pustu)\s+([a-z\s]+)\/.*puskesmas\s+([a-z\s]+)/i', $clean, $m)) {
            $induk = trim($m[3]);
            $induk = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $induk);
            return self::formatHealthUnitName('UPTD Puskesmas ' . $induk);
        }

        $puskesmasMap = [
            'balangnipa' => 'Balangnipa',
            'samataring' => 'Samataring',
            'kampala' => 'Kampala',
            'panaikang' => 'Panaikang',
            'aska' => 'Aska',
            'samaenre' => 'Samaenre',
            'biji nangka' => 'Biji Nangka',
            'bijinangka' => 'Biji Nangka',
            'borong kompleks' => 'Borong Kompleks',
            'manimpahoi' => 'Manimpahoi',
            'lappae' => 'Lappae',
            'lappadata' => 'Lappadata',
            'manipi' => 'Manipi',
            'tengnga lembang' => 'Tengnga Lembang',
            'tengngalembang' => 'Tengnga Lembang',
            'tengalembang' => 'Tengnga Lembang',
            'mannanti' => 'Mannanti',
            'bulupoddo' => 'Bulupoddo',
            'pulau sembilan' => 'Pulau Sembilan',
            'pulau ix' => 'Pulau Sembilan',
        ];

        $cleanLower = strtolower($clean);
        foreach ($puskesmasMap as $key => $officialName) {
            if (str_contains($cleanLower, $key)) {
                return 'UPTD Puskesmas ' . $officialName;
            }
        }

        $clean = preg_replace('/^(tu|tata usaha|sub bagian tata usaha)\s+/i', '', $clean);
        if (preg_match('/^puskesmas\b/i', $clean)) {
            $clean = 'UPTD ' . $clean;
        }

        $words = explode(' ', $clean);
        $words = array_map(fn($w) => in_array(strtolower($w), ['uptd', 'rsud', 'pustu', 'psc']) ? strtoupper($w) : ucfirst(strtolower($w)), $words);
        return implode(' ', $words);
    }

    /**
     * Resolve unit_id and clean info from SIMPEG API by OPD name if missing.
     */
    public function resolveUnitIdFromApi(): bool
    {
        if (!empty($this->unit_id)) {
            return true;
        }

        $baseUrl = config('services.simpeg.url', 'http://apps.sinjaikab.go.id/api/pegawai');
        $timeout = config('services.simpeg.timeout', 10);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout($timeout)->get("{$baseUrl}/get_unit");
            if ($response->successful()) {
                $units = $response->json();
                $unitList = isset($units['data']) && is_array($units['data']) ? $units['data'] : (is_array($units) ? $units : []);

                $cleanCurrent = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $this->name));
                foreach ($unitList as $u) {
                    $rawName = $u['unit_nama'] ?? '';
                    $cleanApi = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawName));
                    if ($cleanApi === $cleanCurrent || str_contains($cleanApi, $cleanCurrent) || str_contains($cleanCurrent, $cleanApi)) {
                        $cleaned = self::cleanAndFormatData($u);
                        $this->update([
                            'unit_id' => $cleaned['unit_id'] ?? $u['unit_id'] ?? null,
                            'address' => $this->address ?: ($cleaned['address'] ?? null),
                            'phone' => $this->phone ?: ($cleaned['phone'] ?? null),
                            'email' => $this->email ?: ($cleaned['email'] ?? null),
                        ]);
                        $this->refresh();
                        return !empty($this->unit_id);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore connection error
        }

        return false;
    }

    /**
     * Synchronize officials (pejabat bereselon) from SIMPEG API.
     */
    public function syncSignersFromApi(): int
    {
        if (empty($this->unit_id)) {
            $this->resolveUnitIdFromApi();
        }

        if (empty($this->unit_id)) {
            return 0;
        }

        $baseUrl = config('services.simpeg.url', 'http://apps.sinjaikab.go.id/api/pegawai');
        $timeout = config('services.simpeg.timeout', 10);

        try {
            $pegawaiList = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $response = \Illuminate\Support\Facades\Http::timeout($timeout)->get("{$baseUrl}/get_pegawai/", [
                    'unit_id' => $this->unit_id,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (is_array($data) && !isset($data['error'])) {
                        $pegawaiList = isset($data['data']) && is_array($data['data']) ? $data['data'] : (is_array($data) ? $data : []);
                        break;
                    }
                    if (isset($data['error']) && str_contains(strtolower((string)$data['error']), 'too many requests')) {
                        sleep(2);
                        continue;
                    }
                }
                if ($attempt < 3) {
                    sleep(1);
                }
            }

            if (!$pegawaiList || !is_array($pegawaiList)) {
                return 0;
            }

            // Sync Kepala OPD (check peran_khusus === 'KEPALA' or detect head title patterns)
            $headFound = false;
            foreach ($pegawaiList as $p) {
                $isHeadRole = ($p['peran_khusus'] ?? '') === 'KEPALA';
                $jobTitle = trim($p['jabatan_nama'] ?? '');
                $eselon = strtoupper(trim((string)($p['jabatan_jenis_eselon'] ?? '')));

                $isHeadTitle = preg_match('/^(?:Plt\.\s+|Pj\.\s+|Pjs\.\s+)?(?:Kepala\s+(?:Dinas|Badan|Satuan|Kantor|Pelaksana|UPTD)|Camat|Inspektur|Sekretaris\s+(?:Daerah|DPRD|Dewan)|Direktur)\b/i', $jobTitle)
                    || (in_array($eselon, ['II.A', 'II.B'], true) && !preg_match('/(?:Asisten|Staf\s+Ahli)/i', $jobTitle));

                if ($isHeadRole || $isHeadTitle) {
                    $pangkat = trim((string)($p['pangkat_nama'] ?? ''));
                    $eselonRaw = trim((string)($p['jabatan_jenis_eselon'] ?? ($p['eselon'] ?? '')));
                    $nik = trim((string)($p['nik'] ?? ($p['no_ktp'] ?? ($p['ktp'] ?? ($p['no_identitas'] ?? '')))));

                    // Normalisasi jabatan Kepala OPD
                    $normLeader = self::normalizePosition($this->name, '', $jobTitle);
                    $leaderJobTitle = $normLeader['jabatan'];

                    $this->update([
                        'leader_name' => $p['nama'],
                        'leader_nip' => $p['nip'],
                        'leader_nik' => $nik ?: $this->leader_nik,
                        'leader_title' => $leaderJobTitle ?: $this->leader_title,
                        'leader_rank' => $pangkat ?: null,
                        'leader_eselon' => $eselonRaw ?: ($eselon ?: 'II.a'),
                    ]);

                    // Sync Kepala OPD as User with role: pimpinan (prioritas jabatan definitif)
                    if (!empty($p['nip'])) {
                        $leaderUser = User::where('nip', $p['nip'])->first();
                        $isPltJob = str_starts_with(strtolower($jobTitle), 'plt');

                        if (!$leaderUser) {
                            $defJabatan = $jobTitle ?: $this->leader_title;
                            $defUnit = $this->name;

                            // Cek apakah pegawai memiliki jabatan definitif di OPD lain pada data SIMPEG
                            $allPnsMap = \Illuminate\Support\Facades\Cache::get('simpeg_all_pns_by_nip', []);
                            $pnsList = $allPnsMap[$p['nip']] ?? [];
                            foreach ($pnsList as $rec) {
                                if (($rec['jabatan_status_id'] ?? '1') == '1' && !str_starts_with(strtolower(trim($rec['jabatan_nama'] ?? '')), 'plt')) {
                                    $defJabatan = trim($rec['jabatan_nama']);
                                    $defUnit = trim($rec['parent_unit'] ?? $this->name);
                                    break;
                                }
                            }

                            $normUser = self::normalizePosition($defUnit, '', $defJabatan);

                            $leaderUser = User::create([
                                'nip' => $p['nip'],
                                'nik' => $nik ?: null,
                                'name' => trim($p['nama'] ?? $p['nip']),
                                'unit_name' => $normUser['unit'],
                                'jabatan' => $normUser['jabatan'],
                                'pangkat' => $pangkat ?: null,
                                'password' => null,
                            ]);
                        } else {
                            $updateData = [
                                'name' => trim($p['nama'] ?? $leaderUser->name),
                                'nik' => $nik ?: $leaderUser->nik,
                                'pangkat' => $pangkat ?: $leaderUser->pangkat,
                            ];

                            // Hanya timpa jabatan & unit_name jika bukan Plt atau jika user belum punya jabatan definitif
                            $existingIsPlt = str_starts_with(strtolower(trim((string)$leaderUser->jabatan)), 'plt');
                            if (!$isPltJob || $existingIsPlt || empty($leaderUser->jabatan)) {
                                $normUser = self::normalizePosition($this->name, '', $jobTitle);
                                $updateData['jabatan'] = $normUser['jabatan'] ?: $leaderUser->jabatan;
                                $updateData['unit_name'] = $normUser['unit'] ?: $this->name;
                            }

                            $leaderUser->update($updateData);
                        }
                        if (!$leaderUser->hasRole('admin')) {
                            $leaderUser->assignRole('pimpinan');
                        }
                    }
                    $headFound = true;
                    break;
                }
            }

            $allowedEselons = ['II.A', 'II.B', 'III.A', 'III.B'];

            // Delete any existing signers below Eselon III.b (e.g. IV.a, IV.b) for this OPD
            $this->signers()->whereNotIn('eselon', ['II.a', 'II.b', 'III.a', 'III.b'])->delete();

            $syncedCount = 0;
            foreach ($pegawaiList as $p) {
                $eselon = trim((string)($p['jabatan_jenis_eselon'] ?? ($p['eselon'] ?? null)));
                if (empty($eselon) || $eselon === '-' || $eselon === 'null') {
                    continue;
                }

                // Filter up to Eselon III.b only (exclude IV.a, IV.b, V, etc.)
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
                $signerNik = trim((string)($p['nik'] ?? ($p['no_ktp'] ?? ($p['ktp'] ?? ($p['no_identitas'] ?? '')))));

                // Normalisasi jabatan Penandatangan
                $normSigner = self::normalizePosition($this->name, $bidangName, $title);
                $signerTitle = $normSigner['jabatan'];

                // Cari signer yang sudah ada berdasarkan NIP (jika ada) atau berdasarkan title
                $signer = null;
                if (!empty($p['nip'])) {
                    $signer = OpdSigner::where('opd_id', $this->id)
                        ->where('nip', trim($p['nip']))
                        ->first();
                }
                if (!$signer) {
                    $signer = OpdSigner::where('opd_id', $this->id)
                        ->where('title', $signerTitle)
                        ->first();
                }
                if (!$signer) {
                    $signer = new OpdSigner();
                    $signer->opd_id = $this->id;
                }

                $signer->fill([
                    'title' => $signerTitle,
                    'bidang_name' => $bidangName,
                    'name' => trim($p['nama'] ?? ''),
                    'nip' => !empty($p['nip']) ? trim($p['nip']) : null,
                    'nik' => $signerNik ?: null,
                    'rank' => $pangkat ?: null,
                    'eselon' => $eselon,
                    'is_active' => true,
                ])->save();

                $syncedSignerIds[] = $signer->id;

                // Sync Signer as User with role: pimpinan (prioritas jabatan definitif)
                if (!empty($p['nip'])) {
                    $signerUser = User::where('nip', trim($p['nip']))->first();
                    $isPltJob = str_starts_with(strtolower($title), 'plt');

                    if (!$signerUser) {
                        $defJabatan = $title;
                        $defUnit = $this->name;

                        $allPnsMap = \Illuminate\Support\Facades\Cache::get('simpeg_all_pns_by_nip', []);
                        $pnsList = $allPnsMap[trim($p['nip'])] ?? [];
                        foreach ($pnsList as $rec) {
                            if (($rec['jabatan_status_id'] ?? '1') == '1' && !str_starts_with(strtolower(trim($rec['jabatan_nama'] ?? '')), 'plt')) {
                                $defJabatan = trim($rec['jabatan_nama']);
                                $defUnit = trim($rec['parent_unit'] ?? $this->name);
                                break;
                            }
                        }

                        $normUser = self::normalizePosition($defUnit, '', $defJabatan);

                        $signerUser = User::create([
                            'nip' => trim($p['nip']),
                            'nik' => $signerNik ?: null,
                            'name' => trim($p['nama'] ?? $p['nip']),
                            'unit_name' => $normUser['unit'],
                            'jabatan' => $normUser['jabatan'],
                            'pangkat' => $pangkat ?: null,
                            'password' => null,
                        ]);
                    } else {
                        $updateData = [
                            'name' => trim($p['nama'] ?? $signerUser->name),
                            'nik' => $signerNik ?: $signerUser->nik,
                            'pangkat' => $pangkat ?: $signerUser->pangkat,
                        ];

                        $existingIsPlt = str_starts_with(strtolower(trim((string)$signerUser->jabatan)), 'plt');
                        if (!$isPltJob || $existingIsPlt || empty($signerUser->jabatan)) {
                            $normUser = self::normalizePosition($this->name, $bidangName, $title);
                            $updateData['jabatan'] = $normUser['jabatan'];
                            $updateData['unit_name'] = $normUser['unit'];
                        }

                        $signerUser->update($updateData);
                    }
                    if (!$signerUser->hasRole('admin')) {
                        $signerUser->assignRole('pimpinan');
                    }
                }

                $syncedCount++;
            }

            // Bersihkan penandatangan usang / duplikat yang memiliki NIP dari OPD ini
            if (!empty($syncedSignerIds)) {
                $this->signers()->whereNotIn('id', $syncedSignerIds)->whereNotNull('nip')->delete();
            }

            // Sync Admin OPD (Kasubag Kepegawaian / Kasubag Umum dan Kepegawaian / Kasubag TU & Kepegawaian)
            $adminOpdCandidate = null;

            // Prioritas 1: Jabatan Kasubag yang mengandung kata 'Kepegawaian'
            foreach ($pegawaiList as $p) {
                $jab = trim($p['jabatan_nama'] ?? '');
                if (preg_match('/(?:Kasubag|Kepala\s+Sub\s*\.?\s*Bagian)\s+.*kepegawaian/i', $jab)
                    && !preg_match('/(?:RSUD|Puskesmas|SDN|SMPN|Kelurahan|Pustu)/i', $jab)) {
                    $adminOpdCandidate = $p;
                    break;
                }
            }

            // Prioritas 2: Jabatan Kasubag Umum / TU jika tidak ada kata kepegawaian eksplisit
            if (!$adminOpdCandidate) {
                foreach ($pegawaiList as $p) {
                    $jab = trim($p['jabatan_nama'] ?? '');
                    if (preg_match('/(?:Kasubag|Kepala\s+Sub\s*\.?\s*Bagian)\s+.*(?:umum|tata\s+usaha)/i', $jab)
                        && !preg_match('/(?:RSUD|Puskesmas|SDN|SMPN|Kelurahan|Pustu)/i', $jab)) {
                        $adminOpdCandidate = $p;
                        break;
                    }
                }
            }

            if ($adminOpdCandidate && !empty($adminOpdCandidate['nip'])) {
                $nip = trim($adminOpdCandidate['nip']);
                $adminPangkat = trim((string)($adminOpdCandidate['pangkat_nama'] ?? ''));
                $adminUser = User::where('nip', $nip)->first();
                $normAdmin = self::normalizePosition($this->name, '', $adminOpdCandidate['jabatan_nama'] ?? '');

                if (!$adminUser) {
                    $adminUser = User::create([
                        'nip' => $nip,
                        'name' => trim($adminOpdCandidate['nama'] ?? $nip),
                        'unit_name' => $normAdmin['unit'],
                        'jabatan' => $normAdmin['jabatan'],
                        'pangkat' => $adminPangkat ?: null,
                        'password' => bcrypt($nip),
                    ]);
                } else {
                    $adminUser->update([
                        'name' => trim($adminOpdCandidate['nama'] ?? $adminUser->name),
                        'unit_name' => $normAdmin['unit'],
                        'jabatan' => $normAdmin['jabatan'] ?: $adminUser->jabatan,
                        'pangkat' => $adminPangkat ?: $adminUser->pangkat,
                    ]);
                }

                if (!$adminUser->hasRole('admin')) {
                    $adminUser->assignRole('admin_opd');
                }
            }

            // Sync all existing users in this OPD from the API list (refresh pangkat, jabatan, nik, name)
            $nipsInApi = collect($pegawaiList)->filter(fn($item) => !empty($item['nip']))->keyBy('nip');
            $opdUsers = User::where('unit_name', $this->name)->whereNotNull('nip')->get();
            foreach ($opdUsers as $u) {
                if ($nipsInApi->has($u->nip)) {
                    $pData = $nipsInApi->get($u->nip);
                    $uPangkat = trim((string)($pData['pangkat_nama'] ?? ''));
                    $rawUJabatan = trim((string)($pData['jabatan_nama'] ?? ''));
                    $uName = trim((string)($pData['nama'] ?? $pData['nama_pegawai'] ?? ''));
                    $uNik = trim((string)($pData['nik'] ?? ($pData['no_ktp'] ?? ($pData['ktp'] ?? ($pData['no_identitas'] ?? '')))));

                    $normU = self::normalizePosition($this->name, '', $rawUJabatan);

                    $u->update([
                        'pangkat' => $uPangkat ?: $u->pangkat,
                        'jabatan' => $normU['jabatan'] ?: $u->jabatan,
                        'name'    => $uName ?: $u->name,
                        'nik'     => $uNik ?: $u->nik,
                    ]);
                }
            }

            return $syncedCount;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error syncing signers: ' . $e->getMessage());
            return 0;
        }
    }
}
