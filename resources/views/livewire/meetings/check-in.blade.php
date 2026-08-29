<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;

new #[Layout('layouts.guest')] class extends Component {
    public Meeting $meeting;
    public $message = '';
    public $status = 'ready'; // ready, success, not_available

    public $participant_type = 'internal'; // 'internal' or 'eksternal'

    // Pemkab Sinjai
    public $nip = '';
    public $nip_checked = false;
    public bool $is_pppk_pw = false;
    public $pppk_parent_unit = '';
    public $employee_name = '';
    public $employee_jabatan = '';
    public $employee_unit = '';
    public $employee_id = null;

    // Eksternal
    public $guest_name = '';
    public $guest_agency = '';
    public $guest_position = '';

    public $signature = '';
    public $recorded_time = '';
    public array $available_roles = [];
    public int $selected_role_index = 0;

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;

        if ($this->meeting->status !== 'ongoing') {
            $this->status = 'not_available';
            if ($this->meeting->status === 'scheduled') {
                $this->message = 'Presensi belum dibuka, silakan tunggu hingga rapat dimulai.';
            } elseif ($this->meeting->status === 'completed') {
                $this->message = 'Presensi telah ditutup karena rapat telah selesai.';
            } else {
                $this->message = 'Presensi untuk rapat ini tidak tersedia.';
            }
        }
    }

    public function updatedParticipantType()
    {
        $this->resetValidation();
        $this->resetNip();
        $this->reset(['guest_name', 'guest_agency', 'guest_position', 'signature']);
    }

    public function resetNip()
    {
        $this->nip_checked = false;
        $this->is_pppk_pw = false;
        $this->pppk_parent_unit = '';
        $this->available_roles = [];
        $this->selected_role_index = 0;
        $this->employee_name = '';
        $this->employee_jabatan = '';
        $this->employee_unit = '';
        $this->employee_id = null;
        $this->signature = '';
    }

    public function selectRole(int $index): void
    {
        if (isset($this->available_roles[$index])) {
            $this->selected_role_index = $index;
            $this->employee_jabatan = $this->available_roles[$index]['jabatan'];
            $this->employee_unit = $this->available_roles[$index]['unit'];
        }
    }

    protected function normalizePosition(?string $parentUnit, ?string $childUnit, ?string $rawJabatan): array
    {
        $displayUnit = $parentUnit ?: 'Pemerintah Kabupaten Sinjai';
        $jabatan = $rawJabatan ?: '-';

        // 1. Multiple spaces cleanup
        $jabatan = preg_replace('/\s+/', ' ', trim($jabatan));

        if ($parentUnit && !empty($childUnit)) {
            $parentLower = strtolower($parentUnit);
            $childLower = strtolower($childUnit);

            // 1. Kecamatan (tampilkan nama Kelurahan secara bersih jika ada, atau Kantor Kecamatan)
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
                        $jabatan = $prefix . 'Sekretaris Camat';
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
                        $displayUnit = $this->formatSchoolUnitName($childUnit);
                    }
                }
            }
            // 3. Dinas Kesehatan (tampilkan nama Puskesmas / RSUD / Faskes saja)
            elseif (str_contains($parentLower, 'kesehatan')) {
                if (preg_match('/(puskesmas|rsud|rs\s+pratama|bulupac|bulupan|pustu|faskes|uptd|klinik|lab|psc)/i', $childUnit)) {
                    $displayUnit = $this->formatHealthUnitName($childUnit);
                }
            }
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
        } elseif (preg_match('/^inspektur daerah\b/i', $clean) || (preg_match('/^inspektur\b/i', $clean) && !preg_match('/inspektur pembantu/i', $clean))) {
            $jabatan = $prefix . 'Inspektur Daerah';
        } elseif (preg_match('/^sekretaris dprd\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris DPRD';
        } elseif (preg_match('/^sekretaris daerah\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris Daerah';
        } elseif (preg_match('/^ktu\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Sub Bagian Tata Usaha';
        } elseif (preg_match('/^kepala\s+(uptd\s+)?puskesmas\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Puskesmas';
        } elseif (preg_match('/^kepala\s+(uptd\s+)?(sd|sdn|smp|smpn|tk|paud|sekolah)\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Sekolah';
        } elseif (preg_match('/^direktur\s+(uptd\s+)?(rsud|rs)\b.*(bulupancing|bulupaccing|bulu\s+paccing)/i', $clean)) {
            $jabatan = $prefix . 'Direktur RS Pratama Bulupaccing';
        } elseif (preg_match('/^direktur\s+(rsud|rs)\s*(sinjai)?/i', $clean)) {
            $jabatan = $prefix . 'Direktur RSUD Sinjai';
        } else {
            // B. Bersihkan akhiran wilayah/induk pada jabatan dinas/badan/rsud/puskesmas/sekolah
            $clean = preg_replace('/\s+dinas\s+pemuda\s+dan\s+olahraga.*$/i', '', $clean);
            $clean = preg_replace('/\s+satpol\s+pp.*$/i', '', $clean);
            $clean = preg_replace('/\s+(pada|di)\s+.+$/i', '', $clean);
            $clean = preg_replace('/\s+(uptd\s+)?(rsud|rs)\b.*$/i', '', $clean);
            $clean = preg_replace('/\s+(uptd\s+)?puskesmas\b.*$/i', '', $clean);
            $clean = preg_replace('/\s+(uptd\s+)?(sd|sdn|smp|smpn|tk|paud|sekolah)\b.*$/i', '', $clean);
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
                $clean = $this->formatJabatanTitleCase($clean);
            } elseif (preg_match('/^(ahli\s+(pertama|muda|madya|utama))\s*-\s*(.+)$/i', $clean, $m)) {
                $jenjang = ucwords(strtolower($m[1]));
                $namaJbt = $this->formatJabatanTitleCase($m[3]);
                $clean = $namaJbt . ' ' . $jenjang;
            } elseif (preg_match('/^(terampil|mahir|penyelia|pemula)\s*-\s*(.+)$/i', $clean, $m)) {
                $jenjang = ucfirst(strtolower($m[1]));
                $namaJbt = $this->formatJabatanTitleCase($m[2]);
                $clean = $namaJbt . ' ' . $jenjang;
            } elseif (preg_match('/^(kepala\s+bidang|kabid|kepala\s+bagian|kabag|kepala\s+seksi|kasi|kepala\s+sub\s+bagian|kasubag|kepala\s+sub\s+bidang|kasubid)\b/i', $clean)) {
                $clean = $this->formatJabatanTitleCase($clean);
            } else {
                $clean = $this->formatJabatanTitleCase($clean);
            }

            $jabatan = $prefix . trim($clean);
        }

        return [
            'jabatan' => $jabatan,
            'unit' => $displayUnit,
            'is_plt' => $isPlt,
        ];
    }

    protected function formatJabatanTitleCase(string $str): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($str));
        $words = explode(' ', $clean);
        $lowerWords = ['dan', 'atau', 'di', 'ke', 'dari', 'pada', 'untuk', 'tentang', 'yang', 'serta', 'per'];
        $upperWords = ['sd', 'smp', 'sma', 'smk', 'tk', 'paud', 'pnf', 'sda', 'sdm', 'asn', 'pns', 'b3', 'tik', 'dprd', 'rsud', 'uptd', 'ppkn', 'ipa', 'ips', 'ktu', 'tu', 'bappeda', 'bkpsdma', 'bkad', 'dpmptsp', 'dinsos', 'dishub', 'disdik', 'dinkes', 'i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x'];

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
            if (in_array($wLower, $upperWords)) {
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

    protected function formatSchoolUnitName(string $str): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($str));
        // 1. Hapus embel-embel kecamatan / kabupaten di belakang
        $clean = preg_replace('/\s+kec\.?\s*.+$/i', '', $clean);
        $clean = preg_replace('/\s+kab\.?\s*sinjai.*$/i', '', $clean);

        // 2. Normalisasi Jenjang SMP -> 'UPTD SMP Negeri [Nomor] Sinjai'
        if (preg_match('/^(uptd\s+)?smp(n|\s+negeri|\s+neg\.?)?\s*(\d+)(\s+sinjai)?(\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))?/i', $clean, $m)) {
            $nomor = $m[3];
            return 'UPTD SMP Negeri ' . $nomor . ' Sinjai';
        }

        // 3. Normalisasi Jenjang SD Sesuai Perbup Sinjai No. 5/2019
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

        // 4. Normalisasi TK Negeri / TK Pembina
        if (preg_match('/^tk\s+(negeri|neg\.?)\s*(pembina)?\s*(.*)$/i', $clean, $m)) {
            $isPembina = !empty($m[2]);
            $lokasi = trim($m[3]);
            $lokasi = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $lokasi);
            $words = explode(' ', $lokasi);
            $words = array_map(fn($w) => ucfirst(strtolower($w)), $words);
            $cleanLokasi = implode(' ', $words);
            return ($isPembina ? 'TK Negeri Pembina' : 'TK Negeri') . ($cleanLokasi ? ' ' . $cleanLokasi : '');
        }

        // 5. Default General Title Case
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

    protected function formatHealthUnitName(string $str): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($str));
        // 1. Hapus embel-embel kecamatan / kabupaten di belakang
        $clean = preg_replace('/\s+kec\.?\s*.+$/i', '', $clean);
        $clean = preg_replace('/\s+kab\.?\s*sinjai.*$/i', '', $clean);

        // 2. RS Pratama Bulupaccing
        if (preg_match('/(rsud|rs)\b.*(bulupancing|bulupaccing|bulu\s+paccing)/i', $clean)) {
            return 'RS Pratama Bulupaccing';
        }

        // 3. RSUD Sinjai
        if (preg_match('/^(rumah\s+sakit\s+umum\s+daerah|rsud)\s*(sinjai)?/i', $clean)) {
            return 'RSUD Sinjai';
        }

        // 4. Labkesda
        if (preg_match('/(laboratorium|lab\.?\s*kes)/i', $clean)) {
            return 'UPTD Laboratorium Kesehatan Daerah';
        }

        // 5. PSC 119
        if (preg_match('/(psc|public\s+safety\s+center)\s*119/i', $clean)) {
            return 'UPTD PSC 119';
        }

        // 6. Deteksi Pustu yang memiliki Puskesmas Induk
        if (preg_match('/(puskesmas\s+pembantu|pustu)\b.*(uptd\s+)?puskesmas\s+([a-z\s]+)/i', $clean, $m)) {
            $induk = trim($m[3]);
            $induk = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $induk);
            return $this->formatHealthUnitName('UPTD Puskesmas ' . $induk);
        }
        if (preg_match('/(puskesmas\s+pembantu|pustu)\s+([a-z\s]+)\/.*puskesmas\s+([a-z\s]+)/i', $clean, $m)) {
            $induk = trim($m[3]);
            $induk = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $induk);
            return $this->formatHealthUnitName('UPTD Puskesmas ' . $induk);
        }

        // 7. Normalisasi 16 UPTD Puskesmas Resmi
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

        // 8. Default Puskesmas Fallback
        $clean = preg_replace('/^(tu|tata usaha|sub bagian tata usaha)\s+/i', '', $clean);
        if (preg_match('/^puskesmas\b/i', $clean)) {
            $clean = 'UPTD ' . $clean;
        }

        // Title Case
        $words = explode(' ', $clean);
        $words = array_map(fn($w) => in_array(strtolower($w), ['uptd', 'rsud', 'pustu', 'psc']) ? strtoupper($w) : ucfirst(strtolower($w)), $words);
        return implode(' ', $words);
    }

    public function checkNip(?string $nipInput = null)
    {
        $this->resetValidation();

        if (!empty($nipInput)) {
            $this->nip = trim((string) $nipInput);
        }

        $ip = request()->ip() ?: '127.0.0.1';
        $throttleKey = 'check-nip:' . $ip;
        if (RateLimiter::tooManyAttempts($throttleKey, 20)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('nip', "Terlalu banyak percobaan cek NIP. Silakan tunggu {$seconds} detik.");
            return;
        }
        RateLimiter::hit($throttleKey, 60);

        $this->nip = trim((string) $this->nip);
        $this->validate([
            'nip' => 'required|digits:18',
        ], [
            'nip.required' => 'NIP wajib diisi.',
            'nip.digits' => 'NIP harus 18 digit.',
        ]);

        $nip = trim($this->nip);
        \Illuminate\Support\Facades\Log::info("checkNip started for NIP: {$nip}");
        $user = User::where('nip', $nip)->first();
        $displayUnit = null;

        // 1. Check official SIMPEG API (for fresh profile, child unit & registration)
        $baseUrl = config('services.simpeg.url', 'http://apps.sinjaikab.go.id/api/pegawai');
        $timeout = (int) config('services.simpeg.timeout', 5);

        try {
            $pegawaiResponse = \Illuminate\Support\Facades\Http::timeout($timeout)->get("{$baseUrl}/data_pegawai/", [
                'nip' => $nip
            ]);

            $pegawaiData = $pegawaiResponse->json();
            $pData = isset($pegawaiData['data']) ? $pegawaiData['data'] : (isset($pegawaiData[0]) ? $pegawaiData[0] : $pegawaiData);

            if ($pegawaiResponse->successful() && is_array($pData) && !empty($pData['nama'] ?? $pData['nama_pegawai'] ?? null)) {
                $name = $pData['nama_pegawai'] ?? $pData['nama'] ?? $nip;
                $unit_id = $pData['unit_id'] ?? $pData['id_unit'] ?? null;
                $rawJabatan = $pData['jabatan_nama'] ?? $pData['jabatan'] ?? null;
                $pangkat = $pData['pangkat_nama'] ?? $pData['pangkat'] ?? null;
                $childUnit = trim((string)($pData['jabatan_grup'] ?? ''));
                $parentUnit = null;

                if ($unit_id) {
                    $unitResponse = \Illuminate\Support\Facades\Http::timeout(5)->get("{$baseUrl}/get_unit/", [
                        'unit_id' => $unit_id
                    ]);
                    $unitData = $unitResponse->json();
                    $uData = isset($unitData['data']) ? $unitData['data'] : (isset($unitData[0]) ? $unitData[0] : $unitData);
                    $parentUnit = $uData['unit_nama'] ?? $uData['nama_unit'] ?? $uData['unit_kerja'] ?? null;
                }

                // Discover all positions for this employee (cross-OPD aware via cached index)
                $allPnsMap = \Illuminate\Support\Facades\Cache::get('simpeg_all_pns_by_nip', []);
                $roles = [];

                if (!empty($allPnsMap[$nip]) && is_array($allPnsMap[$nip])) {
                    foreach ($allPnsMap[$nip] as $item) {
                        $pUnit = $item['parent_unit'] ?? $parentUnit;
                        $cUnit = trim((string)($item['jabatan_grup'] ?? ''));
                        $rJabatan = $item['jabatan_nama'] ?? $rawJabatan;
                        $norm = $this->normalizePosition($pUnit, $cUnit, $rJabatan);

                        $exists = false;
                        foreach ($roles as $existingRole) {
                            if ($existingRole['jabatan'] === $norm['jabatan'] && $existingRole['unit'] === $norm['unit']) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $roles[] = $norm;
                        }
                    }
                }

                // If not in cross-OPD cache, discover positions in the employee's unit
                if (empty($roles) && $unit_id) {
                    $listArray = \Illuminate\Support\Facades\Cache::remember("simpeg_unit_pegawai_{$unit_id}", 600, function () use ($baseUrl, $unit_id) {
                        try {
                            $listResponse = \Illuminate\Support\Facades\Http::timeout(5)->get("{$baseUrl}/get_pegawai/", [
                                'unit_id' => $unit_id
                            ]);
                            $listData = $listResponse->json();
                            return isset($listData['data']) ? $listData['data'] : (isset($listData[0]) ? $listData : $listData);
                        } catch (\Throwable $e) {
                            return [];
                        }
                    });

                    if (is_array($listArray)) {
                        foreach ($listArray as $item) {
                            if (($item['nip'] ?? '') === $nip) {
                                $cUnit = trim((string)($item['jabatan_grup'] ?? ''));
                                $rJabatan = $item['jabatan_nama'] ?? $rawJabatan;
                                $norm = $this->normalizePosition($parentUnit, $cUnit, $rJabatan);

                                $exists = false;
                                foreach ($roles as $existingRole) {
                                    if ($existingRole['jabatan'] === $norm['jabatan'] && $existingRole['unit'] === $norm['unit']) {
                                        $exists = true;
                                        break;
                                    }
                                }
                                if (!$exists) {
                                    $roles[] = $norm;
                                }
                            }
                        }
                    }
                }

                if (empty($roles)) {
                    $roles[] = $this->normalizePosition($parentUnit, $childUnit, $rawJabatan);
                }

                // Selalu tampilkan jabatan definitif di atas, diikuti jabatan Plt
                usort($roles, function ($a, $b) {
                    if ($a['is_plt'] === $b['is_plt']) {
                        return 0;
                    }
                    return $a['is_plt'] ? 1 : -1;
                });

                $this->available_roles = $roles;
                $this->selected_role_index = 0;
                $jabatan = $roles[0]['jabatan'];
                $displayUnit = $roles[0]['unit'];

                $userData = [
                    'name' => $name,
                    'jabatan' => $jabatan,
                    'unit_name' => $parentUnit ?: $user?->unit_name,
                ];

                if (!empty($pangkat)) {
                    $userData['pangkat'] = trim((string)$pangkat);
                }

                if (!$user) {
                    $userData['password'] = \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24));
                    $user = User::create(array_merge(['nip' => $nip], $userData));
                    if ($user->roles->count() == 0) {
                        $user->assignRole('pegawai');
                    }
                } else {
                    $user->update($userData);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("SIMPEG API error for NIP {$nip}: " . $e->getMessage());
        }

        // 2. If not found in SIMPEG, check PPPK Paruh Waktu API (Presensi only, do NOT persist to users table)
        if (!$user) {
            $pwUrl = config('services.pppk_pw.url') ?: 'https://tte.sinjaikab.go.id/api/v1/pppk-pw';
            $pwToken = config('services.pppk_pw.token') ?: 'sJ9k2Lp5mN8qR1t4vW7xZ0y3bC6fH9hS';
            $pwTimeout = (int) (config('services.pppk_pw.timeout') ?: 8);

            $host = parse_url($pwUrl, PHP_URL_HOST) ?: 'tte.sinjaikab.go.id';
            $targets = [
                // 1. Direct HTTPS via internal private IP (Instant ~15ms on Production cPanel)
                [
                    'url' => $pwUrl,
                    'options' => [
                        'force_ip_resolve' => 'v4',
                        'curl' => [
                            CURLOPT_RESOLVE => [
                                "{$host}:443:10.91.162.2",
                            ],
                        ],
                    ],
                ],
                // 2. Standard DNS fallback (Instant on Local / External)
                [
                    'url' => $pwUrl,
                    'options' => [
                        'force_ip_resolve' => 'v4',
                    ],
                ],
            ];

            $pwResponse = null;

            foreach ($targets as $target) {
                try {
                    $req = \Illuminate\Support\Facades\Http::timeout(4)
                        ->connectTimeout(1)
                        ->withoutVerifying()
                        ->withHeaders([
                            'Host' => $host,
                            'User-Agent' => 'Mozilla/5.0 (compatible; eRapat/1.5; +https://rapat.sinjaikab.go.id)',
                            'Accept' => 'application/json',
                        ])
                        ->withToken($pwToken);

                    if (!empty($target['options'])) {
                        $req->withOptions($target['options']);
                    }

                    $pwResponse = $req->get($target['url'], ['nip' => $nip]);

                    if ($pwResponse && $pwResponse->successful()) {
                        break;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("PPPK-PW check failed on {$target['url']}: " . $e->getMessage());
                }
            }

            if ($pwResponse) {
                \Illuminate\Support\Facades\Log::info("PPPK-PW HTTP status for {$nip}: " . $pwResponse->status() . " - body: " . substr($pwResponse->body(), 0, 100));
            } else {
                \Illuminate\Support\Facades\Log::warning("PPPK-PW response was null for {$nip}");
            }

            if ($pwResponse && $pwResponse->successful()) {
                $pwJson = $pwResponse->json();
                $pwList = $pwJson['data'] ?? [];
                if (!empty($pwList) && isset($pwList[0])) {
                    $pw = $pwList[0];
                    $name = $pw['name'] ?? $nip;
                    $rawJabatan = trim((string)($pw['jabatan'] ?? ''));
                    $jabatan = $rawJabatan ?: 'PPPK Paruh Waktu';
                    $unit_id = $pw['api_unit_id'] ?? null;
                    $rawUnit = trim((string)($pw['unit_kerja'] ?? ''));

                    // Resolve Parent OPD strictly from master Opd table
                    $opdName = 'Pemerintah Kabupaten Sinjai';
                    if ($unit_id) {
                        $opd = \App\Models\Opd::where('unit_id', $unit_id)->first();
                        if ($opd) {
                            $opdName = $opd->name;
                        }
                    }

                    $childUnit = null;
                    $parentUnit = $opdName;

                    if (!empty($rawUnit)) {
                        $normOpd = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $opdName));
                        $normRaw = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawUnit));
                        if ($normOpd !== $normRaw) {
                            $childUnit = $rawUnit;
                        }
                    }

                    $displayUnit = $childUnit ?: $parentUnit;

                    // Check if PPPK-PW already checked in to this meeting
                    $existingAttendance = $this->meeting->attendances()
                        ->whereNull('user_id')
                        ->where('guest_nip', $nip)
                        ->first();

                    if ($existingAttendance) {
                        $this->status = 'success';
                        $this->employee_name = $existingAttendance->guest_name;
                        $this->employee_unit = $existingAttendance->guest_agency ?: $displayUnit;
                        $this->employee_jabatan = $existingAttendance->guest_position ?: $jabatan;
                        $this->recorded_time = $existingAttendance->check_in ? $existingAttendance->check_in->format('H:i') . ' WITA' : now()->format('H:i') . ' WITA';
                        $this->message = 'Presensi sudah tercatat sebelumnya.';
                        return;
                    }

                    // Set state without creating User record in database (no login access)
                    $this->is_pppk_pw = true;
                    $this->employee_id = null;
                    $this->employee_name = $name;
                    $this->employee_jabatan = $jabatan;
                    $this->employee_unit = $displayUnit;
                    $this->pppk_parent_unit = $childUnit ? $parentUnit : '';
                    $this->nip_checked = true;
                    return;
                }
            }
        }

        if (!$user) {
            $this->nip_checked = false;
            $this->addError('nip', 'NIP tidak ditemukan.');
            return;
        }

        // Check if already checked in
        $existingAttendance = $this->meeting->attendances()->where('user_id', $user->id)->first();
        if ($existingAttendance) {
            $this->status = 'success';
            $this->employee_name = $user->name;
            $this->employee_unit = $displayUnit ?: ($user->unit_name ?: 'Pemkab Sinjai');
            $this->employee_jabatan = $user->jabatan ?: 'Pegawai';
            $this->recorded_time = $existingAttendance->check_in ? $existingAttendance->check_in->format('H:i') . ' WITA' : now()->format('H:i') . ' WITA';
            $this->message = 'Presensi sudah tercatat sebelumnya.';
            return;
        }

        $this->is_pppk_pw = false;
        $this->employee_id = $user->id;
        $this->employee_name = $user->name;
        $this->employee_jabatan = $user->jabatan ?: 'Pegawai';
        $this->employee_unit = $displayUnit ?: ($user->unit_name ?: 'Pemkab Sinjai');
        $this->nip_checked = true;
    }

    public function confirmCheckIn(?string $signatureData = null, ?string $participantType = null)
    {
        $ip = request()->ip() ?: '127.0.0.1';
        $throttleKey = 'checkin-submit:' . $ip . ':' . $this->meeting->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 15)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('signature', "Terlalu banyak pengiriman presensi. Silakan tunggu {$seconds} detik.");
            return;
        }
        RateLimiter::hit($throttleKey, 60);

        if ($participantType && in_array($participantType, ['internal', 'eksternal'])) {
            $this->participant_type = $participantType;
        }

        try {
            if (!empty($signatureData)) {
                // If data contains '|', it's the raw coordinates format: width|height|pathData
                if (str_contains($signatureData, '|')) {
                    $parts = explode('|', $signatureData, 3);
                    if (count($parts) === 3) {
                        $w = (int)$parts[0];
                        $h = (int)$parts[1];
                        $path = $parts[2];
                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'"><path d="'.$path.'" fill="none" stroke="#0f172a" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                        $this->signature = 'data:image/svg+xml;base64,' . base64_encode($svg);
                    } else {
                        $this->signature = $signatureData;
                    }
                } else {
                    $this->signature = $signatureData;
                }
            }

            if ($this->meeting->status !== 'ongoing') {
                $this->status = 'not_available';
                $this->message = 'Presensi hanya dibuka saat rapat berlangsung.';
                return;
            }

            $now = now();
            $this->recorded_time = $now->format('H:i') . ' WITA';

            if ($this->participant_type == 'internal') {
                $this->validate([
                    'signature' => 'required|string',
                ], [
                    'signature.required' => 'Tanda Tangan wajib diisi.'
                ]);

                if (!$this->nip_checked) {
                    $this->addError('nip', 'Silakan cek NIP terlebih dahulu.');
                    return;
                }

                if ($this->employee_id) {
                    // Regular PNS / ASN with User record
                    $existingAttendance = $this->meeting->attendances()->where('user_id', $this->employee_id)->first();
                    if ($existingAttendance) {
                        $this->status = 'success';
                        $this->employee_name = $this->employee_name ?: ($existingAttendance->user?->name ?? 'Pegawai');
                        $this->employee_unit = $existingAttendance->user?->unit_name ?: 'Pemkab Sinjai';
                        $this->employee_jabatan = $existingAttendance->user?->jabatan ?: 'Pegawai';
                        $this->recorded_time = $existingAttendance->check_in ? $existingAttendance->check_in->format('H:i') . ' WITA' : $this->recorded_time;
                        $this->message = 'Presensi sudah tercatat sebelumnya.';
                        return;
                    }

                    $this->meeting->attendances()->create([
                        'user_id' => $this->employee_id,
                        'guest_agency' => $this->employee_unit,
                        'guest_position' => $this->employee_jabatan,
                        'signature' => $this->signature,
                        'check_in' => $now,
                        'method' => 'qr',
                        'device_info' => request()->userAgent()
                    ]);
                } else {
                    // PPPK Paruh Waktu (Presensi only, without User account)
                    $existingAttendance = $this->meeting->attendances()
                        ->whereNull('user_id')
                        ->where('guest_name', $this->employee_name)
                        ->where('guest_agency', $this->employee_unit)
                        ->first();

                    if ($existingAttendance) {
                        $this->status = 'success';
                        $this->employee_name = $existingAttendance->guest_name;
                        $this->employee_unit = $existingAttendance->guest_agency;
                        $this->employee_jabatan = $existingAttendance->guest_position ?: '';
                        $this->recorded_time = $existingAttendance->check_in ? $existingAttendance->check_in->format('H:i') . ' WITA' : $this->recorded_time;
                        $this->message = 'Presensi sudah tercatat sebelumnya.';
                        return;
                    }

                    $this->meeting->attendances()->create([
                        'user_id' => null,
                        'guest_nip' => $this->nip,
                        'guest_name' => $this->employee_name,
                        'guest_agency' => $this->employee_unit,
                        'guest_position' => $this->employee_jabatan,
                        'signature' => $this->signature,
                        'check_in' => $now,
                        'method' => 'qr',
                        'device_info' => request()->userAgent()
                    ]);
                }
            } else {
                // Guest check-in: Validate name, agency, position, and signature simultaneously
                $this->validate([
                    'guest_name' => 'required|string|max:255',
                    'guest_agency' => 'required|string|max:255',
                    'guest_position' => 'nullable|string|max:255',
                    'signature' => 'required|string',
                ], [
                    'guest_name.required' => 'Nama Lengkap wajib diisi.',
                    'guest_agency.required' => 'Instansi / Lembaga wajib diisi.',
                    'signature.required' => 'Tanda Tangan wajib diisi.',
                ]);

                $existingGuest = $this->meeting->attendances()
                    ->whereNull('user_id')
                    ->where('guest_name', trim($this->guest_name))
                    ->where('guest_agency', trim($this->guest_agency))
                    ->first();

                if ($existingGuest) {
                    $this->status = 'success';
                    $this->employee_name = $existingGuest->guest_name;
                    $this->employee_unit = $existingGuest->guest_agency;
                    $this->employee_jabatan = $existingGuest->guest_position ?: '';
                    $this->recorded_time = $existingGuest->check_in ? $existingGuest->check_in->format('H:i') . ' WITA' : $this->recorded_time;
                    $this->message = 'Presensi sudah tercatat sebelumnya.';
                    return;
                }

                $this->meeting->attendances()->create([
                    'guest_name' => $this->guest_name,
                    'guest_agency' => $this->guest_agency,
                    'guest_position' => $this->guest_position,
                    'signature' => $this->signature,
                    'check_in' => $now,
                    'method' => 'qr',
                    'device_info' => request()->userAgent()
                ]);

                $this->employee_name = $this->guest_name;
                $this->employee_unit = $this->guest_agency;
                $this->employee_jabatan = $this->guest_position ?: '';
            }

            $this->status = 'success';
            $this->message = 'Presensi berhasil dicatat.';
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CheckIn Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->addError('signature', 'Gagal menyimpan presensi: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <x-slot:seo>
        <x-seo-meta
            :title="'Presensi: ' . $meeting->title . ' | eRapat'"
            :description="'Presensi ' . $meeting->title . ' Pemerintah Kabupaten Sinjai'"
            :url="route('meetings.check-in', $meeting->id)"
            :image="asset('img/meta.png')"
            robots="noindex, nofollow" />
    </x-slot:seo>

    <!-- Meeting Info Header -->
    <div class="mb-6 sm:mb-8 pb-5 sm:pb-6 border-b border-slate-100 text-center">
        <h2 class="text-lg sm:text-2xl font-extrabold text-slate-900 leading-snug break-words">
            {{ $meeting->title }}
        </h2>
        <div class="mt-2.5 flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 text-xs sm:text-sm font-medium text-slate-500">
            <span>{{ $meeting->date ? $meeting->date->translatedFormat('l, d F Y') : '-' }}</span>
            <span class="hidden sm:inline text-slate-300">&bull;</span>
            <span class="break-words">{{ $meeting->location }}</span>
        </div>
    </div>

    @if($status === 'ready')
    <div class="space-y-6" x-data="{
        tab: @entangle('participant_type'),
        nipChecked: @entangle('nip_checked'),
        clientError: '',
        validateAndCheckNip() {
            const el = document.getElementById('nip');
            const val = el ? el.value.trim() : '';
            if (!val) {
                this.clientError = 'NIP wajib diisi.';
                return;
            }
            if (val.length !== 18 || !/^\d+$/.test(val)) {
                this.clientError = 'NIP harus 18 digit.';
                return;
            }
            this.clientError = '';
            $wire.checkNip(val);
        }
    }">

        <!-- Category Segmented Control -->
        <div class="flex p-1.5 bg-slate-100 rounded-2xl shadow-inner border border-slate-200/50">
            <button type="button" @click="tab = 'internal'" :class="tab === 'internal' ? 'bg-white shadow-sm text-primary-700 ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'" class="flex-1 py-2.5 text-sm font-extrabold rounded-xl transition-all active:scale-95 focus:outline-none cursor-pointer">
                Pemkab Sinjai
            </button>
            <button type="button" @click="tab = 'eksternal'" :class="tab === 'eksternal' ? 'bg-white shadow-sm text-primary-700 ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'" class="flex-1 py-2.5 text-sm font-extrabold rounded-xl transition-all active:scale-95 focus:outline-none cursor-pointer">
                Eksternal
            </button>
        </div>

        <!-- Pegawai Internal NIP Section -->
        <div x-show="tab === 'internal'" class="space-y-3">
            <label for="nip" class="block text-sm font-bold text-slate-700">Masukkan NIP</label>

            <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                <div class="flex-1 w-full">
                    <x-text-input wire:model="nip"
                        @input="$event.target.value = $event.target.value.replace(/\D/g, ''); clientError = '';"
                        @beforeinput="if ($event.data && !/^\d+$/.test($event.data)) { $event.preventDefault(); }"
                        id="nip"
                        type="text"
                        pattern="[0-9]*"
                        maxlength="18"
                        inputmode="numeric"
                        class="block w-full py-2.5 px-3 rounded-xl border border-slate-300 text-base sm:text-sm font-mono focus:ring-primary-500 focus:border-primary-500 transition-colors {{ $nip_checked ? 'bg-slate-50 text-slate-500 opacity-70' : 'bg-white' }}"
                        placeholder="Contoh: 199610072022031013"
                        @readonly($nip_checked)
                        @keydown.enter.prevent="validateAndCheckNip()"
                        required />
                    
                    @error('nip')
                        <div class="mt-2 text-xs space-y-1.5" x-show="!clientError">
                            <span class="text-rose-600 font-bold block">{{ $message }}</span>
                            @if(str_contains($message, 'tidak ditemukan'))
                            <button type="button" @click="tab = 'eksternal'" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer">
                                <span>Gunakan Tab Eksternal</span>
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                            @endif
                        </div>
                    @enderror

                    <div class="mt-2 text-xs" x-show="clientError" x-cloak>
                        <span class="text-rose-600 font-bold block" x-text="clientError"></span>
                    </div>
                </div>

                @if(!$nip_checked)
                <button type="button" @click="validateAndCheckNip()" wire:loading.attr="disabled" class="w-full sm:w-auto shrink-0 inline-flex justify-center items-center px-5 py-3 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2 cursor-pointer disabled:opacity-50">
                    <svg wire:loading.remove wire:target="checkNip" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <svg wire:loading wire:target="checkNip" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Cek NIP</span>
                </button>
                @else
                <button type="button" wire:click="resetNip" class="w-full sm:w-auto shrink-0 inline-flex justify-center items-center px-5 py-3 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Ganti NIP
                </button>
                @endif
            </div>

            @if($nip_checked)
            <!-- Verified Employee Identity Card -->
            <div class="mt-3 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl space-y-3 animate-in fade-in slide-in-from-top-2">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0 space-y-0.5">
                        <p class="text-sm font-bold text-emerald-900 truncate">{{ $employee_name }}</p>
                        @if(empty($available_roles) || count($available_roles) <= 1)
                            @if($employee_jabatan)
                            <p class="text-xs font-semibold text-emerald-800 truncate">{{ $employee_jabatan }}</p>
                            @endif
                            @if($employee_unit)
                            <p class="text-xs font-medium text-emerald-600 truncate">{{ $employee_unit }}</p>
                            @endif
                        @endif
                    </div>
                    <span class="shrink-0 flex items-center justify-center w-7 h-7 bg-emerald-500 text-white rounded-xl shadow-xs" title="Terverifikasi SIMPEG">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </div>

                @if(!empty($available_roles) && count($available_roles) > 1)
                <!-- Multi-Role / Jabatan Selector -->
                <div class="pt-2.5 border-t border-emerald-200/80">
                    <label class="block text-[11px] font-extrabold text-emerald-950 uppercase tracking-wider mb-2">
                        Pilih Jabatan:
                    </label>
                    <div class="space-y-2">
                        @foreach($available_roles as $idx => $role)
                        <label class="flex items-start gap-2.5 p-2.5 rounded-xl border transition-all cursor-pointer {{ $selected_role_index === $idx ? 'bg-white border-emerald-500 ring-2 ring-emerald-500/20 shadow-xs' : 'bg-emerald-50/50 border-emerald-200/80 hover:bg-white' }}">
                            <input type="radio" name="selected_role" wire:click="selectRole({{ $idx }})" value="{{ $idx }}" {{ $selected_role_index === $idx ? 'checked' : '' }} class="mt-0.5 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            <div class="flex-1 text-xs">
                                <div class="font-extrabold text-slate-900 leading-tight">{{ $role['jabatan'] }}</div>
                                <div class="text-slate-500 font-medium mt-0.5">{{ $role['unit'] }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>

        <!-- Eksternal Form Section -->
        <div x-show="tab === 'eksternal'" class="space-y-4" x-cloak>
            <div>
                <label for="guest_name" class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap</label>
                <input wire:model.blur="guest_name" id="guest_name" type="text" class="block w-full py-2.5 px-3 rounded-xl border border-slate-300 text-base sm:text-sm focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: Anthony" required />
                @error('guest_name') <span class="text-xs text-rose-600 mt-1 block font-bold">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="guest_agency" class="block text-sm font-bold text-slate-700 mb-1">Instansi / Lembaga</label>
                <input wire:model.blur="guest_agency" id="guest_agency" type="text" class="block w-full py-2.5 px-3 rounded-xl border border-slate-300 text-base sm:text-sm focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: Pengadilan Negeri Sinjai" required />
                @error('guest_agency') <span class="text-xs text-rose-600 mt-1 block font-bold">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="guest_position" class="block text-sm font-bold text-slate-700 mb-1">Jabatan (Opsional)</label>
                <input wire:model.blur="guest_position" id="guest_position" type="text" class="block w-full py-2.5 px-3 rounded-xl border border-slate-300 text-base sm:text-sm focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: Ketua" />
                @error('guest_position') <span class="text-xs text-rose-600 mt-1 block font-bold">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Signature Pad Component & Submit Button -->
        <div x-show="tab === 'eksternal' || nipChecked" class="space-y-6 pt-4 border-t border-slate-100" x-data="signaturePad()" x-cloak>
            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-extrabold text-slate-900">Tanda Tangan</label>
                    <button type="button" @click="clearSignature" class="text-xs font-bold text-rose-600 hover:text-rose-700 active:scale-95 transition flex items-center gap-1 bg-rose-50 px-2 py-1 rounded-xl cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Hapus
                    </button>
                </div>

                <div class="relative border-2 border-dashed border-slate-300 rounded-2xl overflow-hidden bg-slate-50" wire:ignore>
                    <canvas x-ref="canvas" class="w-full h-48 touch-none cursor-crosshair select-none block"
                        @mousedown="startDrawing" @mousemove="draw" @mouseup="stopDrawing" @mouseleave="stopDrawing"
                        @touchstart.prevent="startDrawing" @touchmove.prevent="draw" @touchend.prevent="stopDrawing">
                    </canvas>
                    <div x-show="!hasDrawn" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center text-slate-400 gap-2">
                        <div class="p-3 bg-white rounded-full shadow-sm">
                            <svg class="w-6 h-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <span class="text-sm font-bold text-slate-400">Tanda Tangan</span>
                    </div>
                </div>

                @error('signature')
                    <span class="text-xs text-rose-600 mt-2 block font-bold text-center" x-show="!signatureError">{{ $message }}</span>
                @enderror
                <span class="text-xs text-rose-600 mt-2 block font-bold text-center" x-show="signatureError" x-text="signatureError" x-cloak></span>
            </div>

            <div>
                <button type="button" @click.prevent="submitCheckIn()" wire:loading.attr="disabled" wire:target="confirmCheckIn" class="w-full flex justify-center items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2 cursor-pointer">
                    <svg wire:loading.remove wire:target="confirmCheckIn" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg wire:loading wire:target="confirmCheckIn" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Kirim Presensi</span>
                </button>
            </div>
        </div>
    </div>

    @elseif($status === 'success')
    <div class="text-center py-4 space-y-6">
        <div>
            @if(str_contains($message ?? '', 'sudah tercatat'))
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-xl sm:text-2xl font-extrabold text-slate-900">Presensi Sudah Tercatat</h3>
            @else
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl sm:text-2xl font-extrabold text-slate-900">Presensi Berhasil!</h3>
            @endif
        </div>

        @if($employee_name)
        <div class="bg-white border border-slate-200 rounded-2xl p-4 text-left divide-y divide-slate-100 shadow-2xs">
            <div class="py-2.5 flex justify-between items-center gap-4">
                <span class="text-slate-500 font-medium text-sm">Nama Lengkap</span>
                <span class="font-bold text-slate-900 text-right">{{ $employee_name }}</span>
            </div>
            @if($recorded_time)
            <div class="py-2.5 flex justify-between items-center gap-4">
                <span class="text-slate-500 font-medium text-sm">Waktu Presensi</span>
                <span class="font-bold text-slate-900 font-mono text-right">{{ $recorded_time }}</span>
            </div>
            @endif
        </div>
        @endif

        @php
        $skmUrl = \App\Models\Setting::get('skm_url', 'https://skm.go.id/share/instansi/22748fb4-56a9-4101-9e6d-4145a727e0f5/1');
        @endphp
        @if($skmUrl)
        <!-- Banner Survei Kepuasan Masyarakat (SKM) -->
        <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl text-left space-y-3">
            <div>
                <h4 class="text-sm font-bold text-slate-900">Survei Kepuasan Masyarakat (SKM)</h4>
                <p class="text-xs text-slate-500 mt-0.5">Bantu kami meningkatkan kualitas layanan melalui survei singkat.</p>
            </div>

            <a href="{{ $skmUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex justify-center items-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm w-full gap-2">
                <span>Isi Survei SKM</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
        @endif

        <div>
            <a href="{{ route('meetings.check-in', $meeting->id) }}" wire:navigate class="inline-flex justify-center items-center px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-xs w-full gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Isi Presensi Peserta Lain
            </a>
        </div>
    </div>

    @elseif($status === 'not_available')
    <div class="text-center py-10 px-4">
        <div class="w-20 h-20 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto border-2 border-amber-200 mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h3 class="text-xl font-extrabold text-slate-900 mb-3">Presensi Tidak Tersedia</h3>
        <p class="text-sm font-medium text-slate-600 max-w-sm mx-auto leading-relaxed mb-8">{{ $message }}</p>

        <a href="{{ url('/') }}" class="inline-flex justify-center items-center px-6 py-3 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-xl font-bold text-xs uppercase tracking-wider transition-all shadow-sm">
            &larr; Kembali ke Beranda
        </a>
    </div>
    @endif

    <!-- Script for High-Precision Signature Canvas -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('signaturePad', () => ({
                isDrawing: false,
                hasDrawn: false,
                ctx: null,
                pathData: '',
                signatureError: '',

                init() {
                    this.$nextTick(() => {
                        this.setupCanvas();
                    });
                    this.$watch('tab', () => {
                        this.$nextTick(() => {
                            this.setupCanvas();
                        });
                    });
                    this.$watch('nipChecked', () => {
                        this.$nextTick(() => {
                            this.setupCanvas();
                        });
                    });
                },

                setupCanvas() {
                    const canvas = this.$refs.canvas;
                    if (!canvas) return;

                    this.ctx = canvas.getContext('2d');

                    // Fixed high-resolution internal buffer
                    const rect = canvas.getBoundingClientRect();
                    const width = rect.width > 0 ? rect.width : 400;
                    const height = rect.height > 0 ? rect.height : 180;

                    // Set internal bitmap resolution
                    canvas.width = Math.round(width * 2);
                    canvas.height = Math.round(height * 2);

                    this.ctx.lineWidth = 4;
                    this.ctx.lineCap = 'round';
                    this.ctx.lineJoin = 'round';
                    this.ctx.strokeStyle = '#0f172a';
                },

                getPos(e) {
                    const canvas = this.$refs.canvas;
                    const rect = canvas.getBoundingClientRect();
                    let clientX, clientY;

                    if (e.touches && e.touches.length > 0) {
                        clientX = e.touches[0].clientX;
                        clientY = e.touches[0].clientY;
                    } else if (e.changedTouches && e.changedTouches.length > 0) {
                        clientX = e.changedTouches[0].clientX;
                        clientY = e.changedTouches[0].clientY;
                    } else {
                        clientX = e.clientX;
                        clientY = e.clientY;
                    }

                    const scaleX = canvas.width / rect.width;
                    const scaleY = canvas.height / rect.height;

                    return {
                        x: (clientX - rect.left) * scaleX,
                        y: (clientY - rect.top) * scaleY
                    };
                },

                startDrawing(e) {
                    this.isDrawing = true;
                    this.hasDrawn = true;
                    this.signatureError = '';
                    const pos = this.getPos(e);
                    this.ctx.beginPath();
                    this.ctx.moveTo(pos.x, pos.y);
                    this.pathData += `M ${Math.round(pos.x)} ${Math.round(pos.y)} `;
                },

                draw(e) {
                    if (!this.isDrawing) return;
                    const pos = this.getPos(e);
                    this.ctx.lineTo(pos.x, pos.y);
                    this.ctx.stroke();
                    this.pathData += `L ${Math.round(pos.x)} ${Math.round(pos.y)} `;
                },

                stopDrawing() {
                    if (!this.isDrawing) return;
                    this.isDrawing = false;
                },

                clearSignature() {
                    const canvas = this.$refs.canvas;
                    if (!canvas || !this.ctx) return;
                    this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                    this.hasDrawn = false;
                    this.pathData = '';
                    this.signatureError = '';
                },

                updateSignatureData() {
                    const canvas = this.$refs.canvas;
                    if (!canvas || !this.hasDrawn || !this.pathData) {
                        return '';
                    }

                    // Format: width|height|pathData
                    return `${canvas.width}|${canvas.height}|${this.pathData.trim()}`;
                },

                submitCheckIn() {
                    const sig = this.updateSignatureData();
                    if (!sig || !this.hasDrawn) {
                        this.signatureError = 'Tanda Tangan wajib diisi.';
                        return;
                    }
                    this.signatureError = '';
                    this.$wire.confirmCheckIn(sig, this.tab);
                }
            }));
        });
    </script>
</div>