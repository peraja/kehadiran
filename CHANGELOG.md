# Changelog

Semua perubahan penting pada proyek ini dicatat dalam berkas ini.

Format berkas ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/), dan proyek ini mematuhi [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.8] - 2026-08-29

### Diperbaiki
- **Keamanan — Hapus Token Hardcoded di `routes/console.php`**:
  - Menghapus nilai *fallback* token API PPPK PW yang ter-*hardcode* secara literal pada perintah Artisan diagnostik `erapat:test-pppk`. Perintah kini membaca konfigurasi dari `config('services.pppk_pw.token')` saja dan memberikan pesan error yang jelas apabila token belum dikonfigurasi melalui variabel lingkungan `PPPK_PW_TOKEN`.

- **Keamanan — Mass Assignment Protection pada Model Meeting**:
  - Mengganti `protected $guarded = []` dengan `protected $fillable` yang eksplisit pada [`app/Models/Meeting.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Models/Meeting.php). Kolom sensitif TTE (`minutes_signed_at/by/path`, `attendance_signed_at/by/path`, `photos_signed_at/by/path`) kini tidak dapat diisi melalui mass assignment.

### Diubah
- **Kinerja — Cache Jabatan Multi-Posisi pada Halaman Profil**:
  - Membungkus seluruh kueri DB dalam method `getAllPositions()` ([`app/Models/User.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Models/User.php)) dengan `Cache::remember()` selama 5 menit, menghilangkan query berulang ke tabel `opds` dan `opd_signers` setiap kali halaman profil dimuat.
  - Menambahkan method `forgetPositionsCache()` pada model `User` agar cache dapat di-invalidasi secara tepat setelah pembaruan data jabatan atau sinkronisasi OPD.

## [1.5.7] - 2026-08-29


### Diubah
- **Penerapan Penuh Dialog Modal 100% Sisi Klien (*Pure Client-Side Form Hydration*)**:
  - Mengubah aksi tombol *Tambah & Edit Pengguna* pada [`resources/views/livewire/users/index.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/users/index.blade.php) menjadi 100% *client-side* di Alpine.js sehingga pengisian data dan pembukaan modal dialog berjalan instan (0 ms) serta bebas dari *glitch* / kilasan data lama.
  - Mengonversi tombol *Edit OPD* ([`resources/views/livewire/opd/index.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/opd/index.blade.php)) dan tombol *Edit Pejabat Penandatangan* ([`resources/views/livewire/opd/settings.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/opd/settings.blade.php)) menjadi *client-side* untuk pengalaman interaksi instan tanpa latensi jaringan.
  - Mengompilasi ulang bundel aset CSS produksi Vite (`public/build/`).

## [1.5.6] - 2026-08-29

### Diubah
- **Optimasi Akselerasi Dialog Modal pada Menu Pengguna & Master OPD**:
  - Menerapkan pembukaan instan sisi klien (*pure client-side dispatch*) pada tombol *Tambah Pengguna* ([`resources/views/livewire/users/index.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/users/index.blade.php)).
  - Menambahkan *loading state* (animasi *spinner*) pada tombol *Edit Pengguna* dan *Edit OPD* ([`resources/views/livewire/opd/index.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/opd/index.blade.php)) untuk umpan balik visual instan saat memuat data.

## [1.5.5] - 2026-08-29

### Diubah
- **Optimasi Performa dan Akselerasi Pembukaan Dialog Modal**:
  - Mengubah mekanisme pembukaan modal dialog (*Buat Rapat*) pada [`resources/views/livewire/meetings/index.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/index.blade.php) menjadi 100% *client-side* (Alpine.js) tanpa menunggu *network roundtrip* ke server.
  - Mengoptimalkan komponen dasar modal ([`resources/views/components/modal.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/components/modal.blade.php)) dengan mengganti filter berat `backdrop-blur` menjadi *overlay* GPU-friendly `bg-slate-900/50` dan mempercepat durasi transisi menjadi 150ms agar animasi modal berjalan instan dan mulus pada 60 FPS di seluruh perangkat.
  - Mengompilasi ulang seluruh bundel aset CSS produksi Vite (`public/build/`).

## [1.5.4] - 2026-08-29

### Ditambahkan
- **Tampilan Multi-Jabatan Terintegrasi pada Halaman Profil Pengguna**:
  - Menambahkan method `getAllPositions()` pada model [`User.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Models/User.php) untuk mengumpulkan seluruh peran jabatan dan OPD (Definitif & Plt lintas instansi) secara dinamis.
  - Memperbarui halaman profil ([`resources/views/profile.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/profile.blade.php)) untuk menampilkan kartu daftar penugasan multi-peran dengan badge visual status (*Definitif* / *Plt*).

### Diubah
- **Proteksi Prioritas Jabatan Definitif pada Sinkronisasi Master OPD**:
  - Memperbarui logika `syncSignersFromApi` pada [`app/Models/Opd.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Models/Opd.php) agar tombol **Sinkron OPD** selalu mempertahankan jabatan definitif akun pengguna di tabel `users` dan tidak tertimpa oleh jabatan Plt sementara, sembari tetap mencatat pejabat Plt sebagai pimpinan sah pada tabel `opds` dan `opd_signers`.

## [1.5.3] - 2026-08-29

### Ditambahkan
- **Deteksi Peran Ganda Lintas OPD (*Cross-OPD Multi-Role Discovery*)**:
  - Mengimplementasikan pengindeksan cerdas seluruh peran ganda (Definitif dan Plt) ASN se-Kabupaten Sinjai yang terdistribusi lintas OPD pada form presensi mandiri ([`check-in.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/check-in.blade.php)) dan formulir login ([`LoginForm.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Livewire/Forms/LoginForm.php)).
  - Mengintegrasikan mekanisme caching data pegawai unit SIMPEG (`simpeg_all_pns_by_nip`) untuk perlindungan penuh dari batas laju (*rate limit*) API SIMPEG Sinjai.
  - Memprioritaskan penempatan posisi Jabatan Definitif di urutan paling atas (*default*) pada pemilih kapasitas jabatan presensi dan profil akun pengguna saat login.

### Diubah
- **Standardisasi & Normalisasi Menyeluruh Nomenklatur Jabatan & Unit Kerja Pemkab Sinjai**:
  - **Fasilitas Kesehatan**: Menyeragamkan penulisan 16 UPTD Puskesmas se-Kabupaten Sinjai (`UPTD Puskesmas [Nama]`), UPTD Laboratorium Kesehatan Daerah, dan standardisasi penulisan resmi **`RS Pratama Bulupaccing`** (*Direktur RS Pratama Bulupaccing*) sesuai toponimi dan regulasi Perbup Sinjai.
  - **Satuan Pendidikan**: Membakukan nama sekolah sesuai Perbup Sinjai No. 5/2019 (`UPTD SMP Negeri [Nomor] Sinjai`, `SD Negeri [Nomor] [Nama]`, `SDN No. [Nomor] [Nama]`, dan `TK Negeri Pembina / TK Negeri`).
  - **Kewilayahan (Kecamatan & Kelurahan)**: Memperketat regex boundary normalisasi untuk memetakan jabatan Lurah, Sekretaris Lurah, Kasi Kelurahan (13 Kelurahan) serta Camat, Sekcam, Kasi, dan Kasubag (9 Kecamatan).
  - **Jabatan Fungsional (Keahlian & Keterampilan)**: Menata ulang struktur jabatan fungsional terbalik BKN/SIMPEG (Ahli Pertama/Muda/Madya/Utama dan Pemula/Terampil/Mahir/Penyelia) serta merapikan kapitalisasi *Title Case*, kata sambung, dan angka Romawi.
- **Pembersihan Antarmuka Pemilih Jabatan Presensi**:
  - Menghilangkan badge teks visual `[Definitif]` dan `[Plt]` pada daftar opsi radio di kartu check-in agar tampil lebih bersih dan elegan.
- **Penyelarasan Data Profil Pengguna saat Login**:
  - Memastikan profil `users.jabatan` dinormalisasi ke jabatan definitif dan `users.unit_name` diarahkan ke nama OPD Induk yang 100% konsisten dengan tabel master 43 Perangkat Daerah.

## [1.5.2] - 2026-08-29

### Ditambahkan
- **Fitur Pagination dan Pencarian Real-Time pada Presensi Admin**:
  - Menambahkan pagination interaktif (`WithPagination`) dan pemilih jumlah baris per halaman (10, 25, 50, 100) pada tabel presensi ruang kerja admin ([`resources/views/livewire/meetings/presensi.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/presensi.blade.php)).
  - Menambahkan kolom pencarian instan (*live search*) untuk memfilter peserta berdasarkan nama, NIP, unit kerja, atau jabatan.
  - Memastikan penomoran baris `#` berjalan berkesinambungan dan akurat di setiap pergantian halaman.

### Diubah
- **Pengurutan Kronologis Presensi Rapat**:
  - Menambahkan pengurutan `orderBy('check_in', 'asc')->orderBy('id', 'asc')` pada relasi `attendances()` di model [`app/Models/Meeting.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Models/Meeting.php) agar nomor urut dan posisi peserta selalu konsisten kronologis berdasarkan waktu kedatangan.
- **Standardisasi Tampilan Child Unit Tunggal**:
  - Menyelaraskan tampilan unit kerja di form presensi ([`check-in.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/check-in.blade.php)), tabel admin ([`presensi.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/presensi.blade.php)), dan ekspor PDF ([`meeting-attendance.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/exports/meeting-attendance.blade.php)) agar menampilkan nama unit penempatan/child unit secara langsung (contoh: *SD Neg. No. 124 Lura*, *UPTD Puskesmas Balangnipa*, *Kantor Kelurahan Bongki*).
- **Format Penulisan & Label Kolom Presensi**:
  - Mengubah label header kolom tabel presensi dari `OPD / Instansi` menjadi `Unit Kerja / Instansi`.
  - Menerapkan format *Title Case* baku untuk jabatan dan nama unit kerja pada data PPPK-PW dan dokumen PDF cetak.
- **Optimasi Latensi Lookup API PPPK-PW**:
  - Mengoptimalkan target request cURL internal ke IP privat `10.91.162.2` dan memangkas connection timeout menjadi 1 detik untuk respon instan di server production.



### Ditambahkan
- **Integrasi API Presensi PPPK Paruh Waktu (PPPK-PW)**:
  - Menghubungkan gateway API PPPK-PW Sinjai (`https://tte.sinjaikab.go.id/api/v1/pppk-pw`) pada form presensi mandiri ([`resources/views/livewire/meetings/check-in.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/check-in.blade.php)) untuk pengenalan otomatis nama, jabatan, dan OPD PPPK Paruh Waktu via NIP 18 digit.
  - Menerapkan isolasi akun: data PPPK-PW hanya diproses untuk kebutuhan presensi rapat tanpa membuat record akun di tabel pengguna (`users`), sehingga hak akses login tetap tidak tersedia.
  - Menambahkan kolom `guest_nip` pada tabel `meeting_attendances` melalui migrasi database ([`database/migrations/2026_08_28_175410_add_guest_nip_to_meeting_attendances_table.php`](file:///Users/abedzul/Desktop/htdocs/rapat/database/migrations/2026_08_28_175410_add_guest_nip_to_meeting_attendances_table.php)) untuk mencatat NIP PPPK-PW.
  - Menambahkan pengamanan fallback token default, pemaksaan protokol IPv4 (`force_ip_resolve: v4`), dan penanganan loopback DNS internal server agar request API PPPK-PW tidak terblokir firewall atau NAT server cPanel.
- **Penyelarasan Tampilan NIP Presensi Admin & Dokumen Ekspor**:
  - Menampilkan NIP peserta PPPK-PW secara resmi pada tabel presensi ruang kerja admin ([`resources/views/livewire/meetings/presensi.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/presensi.blade.php)) tanpa badge "Eksternal".
  - Menampilkan NIP peserta PPPK-PW pada dokumen cetak dan ekspor PDF resmi ([`resources/views/exports/meeting-attendance.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/exports/meeting-attendance.blade.php)).

## [1.5.0] - 2026-08-27

### Ditambahkan
- **Fitur Ekspor Arsip Berkas Rapat Lengkap (.ZIP)**:
  - Menambahkan endpoint dan *controller method* `exportBundle` pada [`routes/web.php`](file:///Users/abedzul/Desktop/htdocs/rapat/routes/web.php) dan [`app/Http/Controllers/MeetingExportController.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Http/Controllers/MeetingExportController.php) untuk mengemas seluruh berkas PDF rapat (*Notulen*, *Presensi*, dan *Dokumentasi*) ke dalam 1 file arsip `.ZIP`.
  - Menerapkan format penamaan arsip bersih `Dokumen - [Judul Agenda] - [Tanggal].zip` serta nama berkas PDF di dalamnya tanpa penomoran berlebih (`Notulen - ...pdf`, `Presensi - ...pdf`, `Dokumentasi - ...pdf`).
  - Menerapkan validasi backend dan frontend ketat: tombol dan akses berkas ZIP hanya aktif jika status rapat telah diselesaikan dan ketiga dokumen telah berstatus TTE Lengkap (`isFullySigned()`).
  - Menambahkan tombol **`Download ZIP`** pada Header Ruang Kerja Rapat ([`resources/views/livewire/meetings/header.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/header.blade.php)) dan tombol **`ZIP`** pada kolom Dokumen TTE di tabel Riwayat Rapat ([`resources/views/livewire/meetings/history.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/history.blade.php)).
- **Proteksi Rate-Limiting pada Presensi Mandiri Publik (Check-in)**:
  - Menerapkan pembatasan laju permintaan (*rate limiting*) berbasis IP pengguna pada [`resources/views/livewire/meetings/check-in.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/check-in.blade.php) untuk aksi cek NIP (maksimal 20 percobaan/menit) dan konfirmasi presensi/tanda tangan (maksimal 15 pengiriman/menit) guna mengamankan endpoint dari *brute-force* dan *spam bot*.

### Diubah
- **Unduh Langsung (*Direct Download*) untuk Berkas PDF TTE**:
  - Mengubah metode penyajian berkas PDF bertanda tangan elektronik (TTE) pada [`app/Http/Controllers/MeetingExportController.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Http/Controllers/MeetingExportController.php) agar otomatis mengunduh berkas fisik asli (*attachment / direct download*) ke perangkat pengguna saat tombol diklik.
  - Menyeragamkan label tombol aksi di seluruh halaman ([`overview.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/overview.blade.php), [`presensi.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/presensi.blade.php), [`dokumentasi.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/dokumentasi.blade.php), dan [`notulen.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/notulen.blade.php)): berkas TTE berlabel **`Download PDF`**, sedangkan berkas draf pratinjau berlabel **`Lihat PDF`**.

## [1.4.4] - 2026-08-26

### Ditambahkan
- **4 Kartu Layanan Unggulan di Landing Page**:
  - Menambahkan kartu ringkas fitur utama eRapat (*Presensi Digital*, *TTE BSrE - BSSN*, *Notulen AI*, *Ekspor Dokumen*) pada [`resources/views/welcome.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/welcome.blade.php) sehingga halaman depan selalu informatif, hidup, dan profesional saat tidak ada jadwal rapat aktif.

### Diubah
- **Redesain Hero Section & Header Landing Page**:
  - Mengubah susunan judul hero menjadi gaya *eyebrow tag* institusional resmi dengan teks instansi `PEMERINTAH KABUPATEN SINJAI` dan judul utama `Portal Rapat Elektronik`.
  - Menyederhanakan kartu *empty state* rapat hari ini dengan badge tanggal dinamis dan tombol aksi cepat *Buat Rapat*.
- **Grid 2x2 Kompak Kartu Statistik Dashboard di Mobile**:
  - Mengubah susunan 4 kartu statistik (*Hari Ini, Minggu Ini, Bulan Ini, Tahun Ini*) pada [`resources/views/livewire/dashboard-summary.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/dashboard-summary.blade.php) dari 1 kolom bertumpuk menjadi grid 2 kolom x 2 baris simetris (`grid-cols-2 lg:grid-cols-4`) guna menghemat ruang vertikal layar smartphone.
- **Auto-Fit Dropdown Profil Topbar Mobile**:
  - Mengubah lebar dropdown menu profil di mobile ([`resources/views/livewire/layout/topbar-profile.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/layout/topbar-profile.blade.php)) menjadi `w-max min-w-[200px] max-w-[calc(100vw-2rem)]` agar otomatis menyesuaikan panjang nama pengguna dan gelar tanpa terpotong.
- **Penyelarasan Badge Tanggal Topbar Mobile**:
  - Menampilkan badge tanggal hari ini (`l, d F Y`) pada topbar mobile ([`resources/views/layouts/app.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/layouts/app.blade.php)) dengan style dan format yang seragam dengan desktop.
- **Optimalisasi Perilaku Auto Focus Form**:
  - Menghapus auto focus pada form Presensi/Check-In ([`resources/views/livewire/meetings/check-in.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/check-in.blade.php)) guna mencegah keyboard virtual mobile menutupi informasi rapat saat halaman dibuka via scan QR.
  - Memperkuat auto focus pada input NIP form Login ([`resources/views/livewire/pages/auth/login.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/pages/auth/login.blade.php)) menggunakan Alpine.js agar selalu aktif saat transisi navigasi SPA.
- **Isolasi State Signature Pad & Sinkronisasi Input Eksternal**:
  - Menambahkan isolasi DOM dinamis `wire:key` dan `wire:ignore` pada kanvas tanda tangan presensi ([`resources/views/livewire/meetings/check-in.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/check-in.blade.php)) agar konteks kanvas tidak tereset saat berpindah tab kategori peserta (*Pemkab Sinjai* vs *Eksternal*).
  - Menggunakan `wire:model.blur` pada input tamu eksternal guna memastikan seluruh nilai field tersinkronisasi sebelum data tanda tangan dikirim.
- **Transisi Tab Presensi Instan (0ms Client-Side)**:
  - Mengalihkan sistem perpindahan tab (*Pemkab Sinjai* vs *Eksternal*) pada [`resources/views/livewire/meetings/check-in.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/check-in.blade.php) dari Livewire server roundtrip ke Alpine.js `x-show` murni di sisi client guna menghilangkan latensi jaringan internet dan membuat perpindahan tab instan 0ms tanpa beban server di smartphone.
- **Penyempurnaan Antarmuka & Validasi Form Presensi**:
  - Menghilangkan kontainer bersarang (*nested card*) pada tab eksternal agar menyatu rapi dengan kartu utama presensi.
  - Menghapus ikon tanggal dan lokasi pada header form presensi untuk estetika yang lebih minimalis.
  - Menyelaraskan teks pesan error validasi dengan label masing-masing kolom serta mengeksekusi validasi seluruh kolom tamu eksternal secara serentak.
  - Memperbarui label input NIP menjadi `Masukkan NIP`, menambahkan validasi ketat 18 digit angka (`required|digits:18`, `maxlength="18"`, `inputmode="numeric"`), pembatasan input keystroke hanya angka murni, validasi instan sisi klien (0ms) saat NIP kosong, sinkronisasi nilai eksplisit, dan memastikan pesan error `[ NIP tidak ditemukan ]` beserta tombol pintas eksternal selalu tampil andal.
  - Memperbarui contoh *placeholder* NIP (`Contoh: 199610072022031013`) dan instansi tamu eksternal (`Contoh: Pengadilan Negeri Sinjai`).
  - Mengintegrasikan state verifikasi NIP (`nipChecked`) secara reaktif dengan `@entangle` dan pemantau `$watch('nipChecked')` pada kanvas tanda tangan presensi.
  - Menambahkan penanganan khusus untuk peserta yang sudah pernah presensi (*Presensi Sudah Tercatat*) serta menyederhanakan kartu konfirmasi presensi dengan hanya menampilkan nama lengkap dan waktu presensi.
  - Menambahkan validasi instan sisi klien (0ms) saat kanvas tanda tangan kosong pada form presensi mandiri tanpa menunggu roundtrip server.
- **Optimasi Performa Core Web Vitals Gambar Publik**:
  - Menambahkan atribut dimensi eksplisit (`width`, `height`) dan `fetchpriority="high"` pada logo instansi di landing page ([`welcome.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/welcome.blade.php)) dan layout tamu ([`guest.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/layouts/guest.blade.php)) guna mencegah pergeseran tata letak (*zero CLS*) dan mempercepat proses rendering awal (*LCP*) di perangkat mobile.
- **Standarisasi SEO Meta & Pratinjau Tautan Halaman Publik**:
  - Menyeragamkan formulasi judul dan meta description dinamis (`Pemerintah Kabupaten Sinjai`) pada landing page ([`welcome.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/welcome.blade.php)), login ([`login.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/pages/auth/login.blade.php)), presensi mandiri ([`check-in.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/check-in.blade.php)), dan verifikasi dokumen TTE ([`verify-tte.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/verify-tte.blade.php)).
  - Menerapkan direktif `robots="noindex, nofollow"` dan aset gambar lokal `asset('img/meta.png')` pada halaman presensi dan verifikasi dokumen demi keamanan, privasi data, dan keandalan pratinjau medsos.
- **Penyelarasan Warna Ikon Tombol Login Landing Page**:
  - Mengubah warna ikon SVG pada tombol Login ([`resources/views/welcome.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/welcome.blade.php)) menjadi putih solid (`text-white`) agar seragam dengan teks dan tombol Dashboard.
- **Pemberantasan Jeda Aksi Status Rapat & Sinkronisasi OPD (0ms Reactive Update)**:
  - Menghapus pemanggilan redirect SPA berlebih pada aksi status rapat (*Mulai Rapat*, *Selesaikan Rapat*, *Lanjutkan Rapat*, *Simpan Edit*, dan *TTE Semua*) di header rapat ([`resources/views/livewire/meetings/header.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/header.blade.php)) serta pada fitur sinkronisasi data SIMPEG OPD ([`resources/views/livewire/opd/settings.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/opd/settings.blade.php) & [`resources/views/livewire/opd/index.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/opd/index.blade.php)) sehingga status dan tampilan diperbarui secara instan dalam 1 siklus AJAX murni tanpa jeda reload halaman.
- **Optimasi Prefetching & Transisi Tab Detail Rapat**:
  - Menambahkan direktif `wire:navigate.hover` dan transisi opasitas (`transition-opacity`) pada navigasi tab ruang kerja rapat ([`resources/views/components/meeting-layout.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/components/meeting-layout.blade.php)) untuk tab *Ringkasan*, *Presensi*, *Dokumentasi*, dan *Notulen* agar konten termuat di latar belakang saat disentuh/didekati kursor dan perpindahan tab terasa instan.
- **Transisi Visual Loading Tabel & Responsivitas Filter Pills (0ms Instant Feedback)**:
  - Menambahkan efek halus `wire:loading.class="opacity-50"` pada seluruh tabel data utama (*Daftar Rapat*, *Riwayat Rapat*, *Pengguna*, *OPD*, *Presensi*, dan *Audit Logs*) guna memberikan umpan balik visual yang responsif saat pencarian atau filter status sedang diproses.
  - Mengintegrasikan Alpine.js `@entangle(...).live` pada seluruh filter pills tabel (*Daftar Rapat*, *Manajemen Pengguna*, *Master OPD*, dan *Audit Logs*) sehingga status visual tombol aktif langsung berganti seketika (0ms) saat diklik tanpa jeda jaringan.
  - Menyeragamkan label teks `Reset` dan ikon putar (*rotate* `↻`) pada seluruh tombol reset filter tabel data (*Daftar Rapat*, *Riwayat Rapat*, *Pengguna*, *OPD*, dan *Audit Logs*).
- **Optimasi Performa Kueri Tabel & Indeks Database Komprehensif (< 100ms Response Time)**:
  - Menambahkan indeks database baru pada `users` (`unit_name`), `opds` (`name`, `is_active`, `leader_nip`), dan `meeting_attendances` (`[meeting_id, user_id]`, `check_in`) melalui migrasi [`2026_08_26_152308_add_comprehensive_performance_indexes.php`](file:///Users/abedzul/Desktop/htdocs/rapat/database/migrations/2026_08_26_152308_add_comprehensive_performance_indexes.php).
  - Mengeliminasi *full table scan string manipulation* (`whereRaw REPLACE`) dan menyederhanakan kueri pencarian OPD menggunakan indeks terstruktur.
  - Menggabungkan perhitungan 4 kueri badge jumlah (*counts*) menjadi **1 kueri agregasi SQL tunggal** (`SUM(CASE WHEN ...)`) pada tabel Daftar Rapat, Master OPD, dan Audit Logs serta menambahkan *caching* cerdas pada tabel Manajemen Pengguna.
  - Menambahkan *eager loading* relasi notulen (`minutes`) pada Daftar Rapat dan Riwayat Rapat untuk mencegah kueri berulang.

## [1.4.3] - 2026-08-26

### Ditambahkan
- **Indeks Database Performa Query**:
  - Menambahkan migrasi penambahan indeks pada kolom `status`, `date`, `signer_nip`, dan komposit `[status, date]` pada tabel `meetings` ([`2026_08_26_000001_add_performance_indexes_to_meetings_table.php`](file:///Users/abedzul/Desktop/htdocs/rapat/database/migrations/2026_08_26_000001_add_performance_indexes_to_meetings_table.php)) guna mempercepat kueri filtering data dan otorisasi role di production.
- **Kompresi Aset & Browser Caching Web Server**:
  - Menambahkan konfigurasi Gzip compression (`mod_deflate`), browser caching 1 tahun (`mod_expires`), dan security headers pada [`public/.htaccess`](file:///Users/abedzul/Desktop/htdocs/rapat/public/.htaccess) untuk mempercepat transfer berkas statis di cPanel / Apache / LiteSpeed.
- **Panduan Deploy & Optimasi Production**:
  - Menambahkan panduan maintenance, konfigurasi lingkungan `.env`, dan automasi *artisan caching* pada [`README.md`](file:///Users/abedzul/Desktop/htdocs/rapat/README.md).

### Diubah
- **Reposisi Navigasi 4 Tab Workspace Rapat**:
  - Memindahkan navigasi 4 tab (*Ringkasan, Presensi, Dokumentasi, Notulen*) dari kartu header ke bagian atas kartu konten ([`components/meeting-layout.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/components/meeting-layout.blade.php)) sebagai sub-header tab dengan garis pembatas halus.
- **Standardisasi Layout & Ukuran Tombol Aksi di Mobile**:
  - Menerapkan sistem **Auto Grid & Full-Width** pada toolbar tab Presensi, Dokumentasi, dan Notulen: tombol otomatis melebar penuh jika tunggal (`only:col-span-2 w-full`), dan membagi 2 kolom berdampingan secara simetris jika terdapat 2 tombol.
  - Memperbesar tombol **Lihat PDF** dan **TTE Dokumen** di tab Ringkasan (khusus pimpinan) ke standar `text-sm font-bold` dengan padding `px-4 py-2.5` dan ikon `w-4 h-4` yang proporsional.
  - Menyesuaikan tombol **Simpan Notulen**, **Upload Foto**, dan banner **Buka Revisi** agar melebar penuh (*full-width*) di mobile dengan touch target yang nyaman.
  - Menghilangkan *blank space* di bawah nama OPD pada kartu header rapat di mobile dengan conditional rendering `@if($hasActionButtons)` dan normalisasi padding kartu.

### Diperbaiki
- **Stabilitas Modal Notulen AI & Transisi Keluar Mulus**:
  - Memperbaiki glitch saat menerapkan notulen AI dengan mempertahankan variabel `$aiResult` selama animasi *fade-out* dan memancarkan event penutupan modal eksplisit.
  - Memperbaiki posisi loading spinner SVG di modal agar selalu terpusat sempurna di tengah layar.
  - Menambahkan validasi instan di sisi klien jika konten editor notulen masih kosong (< 5 karakter) sebelum membuka modal AI.
- **Reaktivitas Alert Notifikasi Sukses**:
  - Mengatasi alert sukses yang langsung hilang setelah aksi Simpan Notulen, Upload/Hapus Foto, TTE Dokumen, dan Buka Revisi dengan mengimplementasikan properti reaktif `$successMessage` dan dynamic `:wire:key` berbasis timestamp agar alert tampil stabil selama 4 detik penuh di setiap pembaruan.

## [1.4.2] - 2026-08-26

### Ditambahkan
- **Komponen SEO Meta Dinamis Terpusat**:
  - Menambahkan komponen Blade `<x-seo-meta>` di [`components/seo-meta.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/components/seo-meta.blade.php) yang mendukung konfigurasi judul, deskripsi kontekstual "Pemerintah Kabupaten Sinjai", tag robots, canonical URL, OpenGraph, dan Twitter Card.
  - Mengintegrasikan metadata SEO dinamis dan ringkas pada seluruh halaman publik: Landing Page (`eRapat | Pemkab Sinjai`, index follow), Halaman Login (`Login | eRapat`, noindex), Halaman Presensi Publik (`Presensi | [Judul Rapat]`, noindex), dan Verifikasi TTE (`Verifikasi [Dokumen] | [Judul Rapat]`, noindex).

### Diubah
- **Optimalisasi Tipografi dan Spasi Hero Landing Page**:
  - Menyesuaikan hirarki ukuran teks judul hero di mobile hingga desktop ("Manajemen Rapat" `text-4xl sm:text-6xl md:text-7xl` dan "Pemerintah Kabupaten Sinjai" `text-2xl sm:text-4xl md:text-5xl`).
  - Menyelaraskan *vertical spacing* landing page agar proporsional dan tidak terpotong.
- **Standardisasi Filter Grid, Search & Badge di Seluruh Modul**:
  - Menyeragamkan ukuran font teks tombol filter pill menjadi `text-sm font-bold` (14px) dan badge angka menjadi `text-xs font-bold` (12px) di semua viewport (termasuk mobile).
  - Menata kolom pencarian dan tombol Reset menjadi satu baris berdampingan (`grid-cols-[1fr_auto]`) saat filter aktif di mobile.
  - Menghapus pembagian `col-span` berlebih pada tombol "Semua" di Master Pengguna agar ukuran seluruh pill setara dalam sistem grid.
  - Memperbaiki kalkulasi 12-kolom grid filter pada Riwayat Rapat agar selalu membentang penuh (100% *full width*) di desktop.
- **Penyelarasan Padding Seluruh Kartu Header, Tabel, dan Dashboard di Mobile**:
  - Menyeragamkan *padding* seluruh kartu header, toolbar tabel, dan kartu statistik di halaman *Daftar Rapat*, *Riwayat Rapat*, *Master Pengguna*, *Master OPD*, *Pengaturan OPD*, *Audit Log*, *Profil Pengguna*, dan *Dashboard* menjadi `p-5 sm:p-6` (20px di mobile, 24px di desktop).
  - Menyesuaikan tombol aksi utama (*Buat Rapat*, *Sinkron SIMPEG*, *Lihat Presensi*, *TTE Dokumen*, *Isi Notulen*) menjadi *full-width* (`w-full sm:w-auto`) di mobile.
  - Mengubah label input pilihan instansi pada formulir modal Buat Rapat khusus Super Admin dari "OPD / Instansi" menjadi "OPD".

## [1.4.1] - 2026-08-25

### Diubah
- **Standardisasi Layout CSS Grid pada Seluruh Toolbar & Filter Tabel**:
  - Mengubah seluruh toolbar dan filter tabel (*Daftar Rapat*, *Riwayat Rapat*, *Manajemen Pengguna*, *Master Data OPD*, dan *Log Audit*) menggunakan layout responsif **CSS Grid** (`grid-cols-*`) sehingga proporsi tombol filter status/role, search input, date range picker, dan tombol reset tersusun simetris dan rapi di semua ukuran layar.
- **Pencegahan Mobile Auto-Zoom pada Seluruh Input Form**:
  - Menetapkan ukuran font seluruh input teks, password, date picker, search bar, select, dan textarea di seluruh komponen aplikasi menjadi `text-base sm:text-sm` (16px di layar HP <640px) guna mencegah browser iOS Safari dan Chrome mobile melakukan *auto-zoom* otomatis saat input difokuskan.
  - Menambahkan `maximum-scale=1` pada viewport meta tag di [`layouts/guest.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/layouts/guest.blade.php), [`layouts/app.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/layouts/app.blade.php), dan [`welcome.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/welcome.blade.php).
- **Penyempurnaan Navigasi Sidebar Mobile**:
  - Menambahkan penutupan sidebar mobile secara instan saat pengguna menyentuh/mengklik area luar drawer atau backdrop gelap, serta melalui tombol keyboard `Esc`.
  - Menghapus tombol *close* (ikon silang X di luar laci) untuk tampilan mobile yang lebih bersih dan minimalis.
- **Optimalisasi Tombol Aksi Header Rapat di Mobile**:
  - Menambahkan `whitespace-nowrap`, `shrink-0`, dan penyesuaian padding pada tombol-tombol aksi header rapat (*QR Code*, *Mulai*, *Selesaikan*, *TTE Semua*, *Edit*) agar tidak terpotong menjadi 2 baris di layar kecil.

### Diperbaiki
- **Perbaikan Glitch Scroll dan Scroll-Bleed pada Modal Global**:
  - Menghapus manipulasi `overflow: hidden` pada `document.documentElement` (hanya disematkan pada `document.body`) guna mengatasi bug loncatan posisi scroll ke paling atas saat membuka modal di halaman yang sedang di-scroll.
  - Menambahkan opsi `focus({ preventScroll: true })` saat memfokuskan input pertama di modal.
  - Menambahkan `overscroll-contain` dan `overflow-x-hidden` pada backdrop dan kartu modal guna mencegah *scroll bleed* ke halaman belakang serta mencegah goyangan scroll horizontal di mobile.

## [1.4.0] - 2026-08-25

### Ditambahkan
- **Pengetatan Akses & Tampilan Khusus Role Pimpinan**:
  - Penegasan kueri rapat untuk peran `pimpinan` pada Daftar Rapat ([`meetings/index.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/index.blade.php)) dan Dashboard ([`dashboard-summary.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/dashboard-summary.blade.php)) agar secara eksklusif hanya menampilkan rapat yang berstatus selesai (`status = 'completed'`).
  - Proteksi otorisasi `403 Forbidden` pada [`header.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/header.blade.php) dan [`overview.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/overview.blade.php) jika pimpinan mencoba mengakses rapat yang belum berstatus selesai.

### Diubah
- **Optimalisasi Desain Responsif Antarmuka Mobile (< 640px)**:
  - **Navigasi Shell & Topbar**: Pengurangan tinggi topbar menjadi `h-16 sm:h-20`, tombol profil diubah menjadi mode ikon avatar saja di HP (`hidden sm:block` untuk teks nama/role), serta penambahan penutupan otomatis drawer mobile saat tautan diklik (`@click="sidebarOpen = false"`).
  - **Grid Filter Pills & Toolbar Data**:
    - *Daftar Rapat & Log Audit*: Filter pills status/aksi menjadi layout Grid 2 Kolom (`grid grid-cols-2 sm:flex sm:flex-wrap`).
    - *Master OPD*: Filter pills status menjadi Grid 3 Kolom simetris (`grid grid-cols-3 sm:flex sm:flex-wrap`).
    - *Master Pengguna*: Pill "Semua" diatur baris penuh (`col-span-2 sm:col-span-1`) dan 4 pilihan role tersusun 2 kolom di bawahnya.
    - *Riwayat Rapat & Log Audit*: Rentang filter tanggal "Dari" dan "Sampai" dibuat *full-width* simetris (`grid grid-cols-2 sm:inline-flex w-full sm:w-auto`).
  - **Tab Navigasi Workspace Rapat Tanpa Scroll**: Mengubah 4 tab workspace rapat pada [`meeting-layout.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/components/meeting-layout.blade.php) menjadi Grid 2 Kolom tanpa scroll (`grid grid-cols-2 sm:flex sm:flex-wrap`) dengan teks dan badge rata tengah.
  - **Standardisasi Form Modal & Tombol Aksi Sentuh**:
    - *Form Buat & Edit Rapat*: Input tanggal dan waktu mulai/selesai diatur menjadi `grid-cols-2 gap-4 md:contents` dengan dropdown jam:menit yang *fluid*.
    - *Form Tambah/Edit Pengguna*: Pilihan checkbox role disusun dalam Grid 2 Kolom ramah sentuhan.
    - *Form Edit OPD*: Grid kode unit & nama OPD menggunakan `grid-cols-1 sm:grid-cols-3` agar tidak menyempit di layar HP kecil.
    - *Modal Notulen AI*: Ketinggian kotak pratinjau hasil AI menggunakan batas adaptif `max-h-[55vh] sm:max-h-[420px] overflow-y-auto`.
    - *Tombol Aksi Seluruh Modal*: Diseragamkan menggunakan pola `flex-col-reverse sm:flex-row justify-end gap-3` dengan tombol utama berposisi di atas dan melebar penuh (`w-full sm:w-auto`).
  - **Card Dokumen Rapat**: Tombol aksi berkas pada akun pimpinan diatur dengan pembungkus `flex flex-wrap items-center gap-2` dan padding yang nyaman.

## [1.3.9] - 2026-08-25

### Diubah
- **Penyempurnaan Halaman Detail Dokumen Rapat (Akun Pimpinan)**:
  - Menampilkan seluruh baris dokumen rapat (*Presensi*, *Dokumentasi*, *Notulen*) secara konsisten pada tabel Dokumen Rapat akun pimpinan ([`overview.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/overview.blade.php)) meskipun belum memiliki isi.
  - Peringkasan nama dokumen pada baris tabel menjadi *Presensi*, *Dokumentasi*, dan *Notulen*.
  - Penyederhanaan keterangan status dokumen kosong menjadi *Presensi kosong*, *Dokumentasi kosong*, dan *Notulen kosong*.
  - Penghapusan badge *Belum TTE* dan *Sudah TTE* pada tabel ringkasan untuk tampilan antarmuka yang lebih bersih dan minimalis.
  - Penyeragaman ikon tombol *Lihat PDF* yang telah ditandatangani menggunakan ikon perisai terverifikasi (*verified shield* hijau).
- **Optimalisasi Tombol dan Modal TTE Akun Pimpinan**:
  - Penggunaan label tombol statis **`TTE Semua`** pada *card header* rapat akun pimpinan ([`header.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/header.blade.php)).
  - Penanganan dinamis dokumen kosong pada proses TTE massal di modal (`sign-all-modal`) dan backend [`BsreEsignService.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Services/BsreEsignService.php), di mana hanya dokumen yang memiliki isi yang diproses secara otomatis tanpa memicu error.
  - Peningkatan pesan hasil TTE massal agar membedakan proses 1 dokumen (*"Dokumen [Nama Dokumen] berhasil ditandatangani"*) dan multi-dokumen (*"[Jumlah] dokumen berhasil ditandatangani"*).
  - Penyederhanaan form modal TTE: Label diubah menjadi *"Passphrase"*, notifikasi NIK kosong dipersingkat menjadi *"Hubungi Admin OPD untuk mendaftarkan NIK"*, serta penambahan tombol tutup (*dismiss*) pada banner error.
  - Penambahan proteksi *try-catch* dan reset error bag saat membuka/menutup modal TTE di seluruh 5 komponen TTE.
- **Penyempurnaan Badge Status Rapat**:
  - Pembaruan label badge status rapat yang belum memiliki berkas dokumen dari **Draft Dokumen** menjadi **Draft TTE** pada [`meeting-status-badge.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/components/meeting-status-badge.blade.php).
  - Penonaktifan tampilan badge status dokumen pada landing page ([`welcome.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/welcome.blade.php)) sehingga hanya menampilkan badge status murni rapat (*Dijadwalkan*, *Berlangsung*, *Selesai*).
- **Peningkatan Performa & Respon Sistem**:
  - Penghapusan pemanggilan HTTP sinkron pengecekan status sertifikat BSrE pada setiap siklus render Livewire `with()` guna menghilangkan latensi perpindahan halaman dan pembukaan modal.
  - Penambahan mekanisme cache status sertifikat dan *local simulation fallback* mandiri pada [`BsreEsignService.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Services/BsreEsignService.php).

### Diperbaiki
- **Perbaikan Form dan Arsitektur Tanda Tangan Vektor SVG pada Presensi Mandiri**: Mengganti atribut `:readonly="$nip_checked"` menjadi direktif native Blade `@readonly($nip_checked)`, menghapus elemen `<form>` DOM untuk mencegah submit bawaan browser, mengadopsi perekaman koordinat vektor SVG murni sehingga ukuran payload berkurang drastis dari ~300 KB menjadi **~300 bytes**, mengirimkan data tanda tangan secara langsung via parameter `$wire.confirmCheckIn(sig)`, menyertakan blok *try-catch* log detail exception, serta menambahkan `@livewireStyles` dan `@livewireScripts` secara eksplisit pada [`layouts/guest.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/layouts/guest.blade.php) guna mencegah pemblokiran payload oleh WAF/ModSecurity cPanel pada [`livewire/meetings/check-in.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/check-in.blade.php).
- **Penanganan Error 405 Method Not Allowed pada Request Livewire di Server cPanel**:
  - Penambahan berkas [`index.php`](file:///Users/abedzul/Desktop/htdocs/rapat/index.php) pada root repositori dan penyelarasan rewrite root [`.htaccess`](file:///Users/abedzul/Desktop/htdocs/rapat/.htaccess) agar menangani request HTTP POST dinamis secara langsung di root directory tanpa melalui *cross-directory subrequest* ke folder `public/` yang memicu respon statis 405 pada server cPanel.
  - Penambahan konfigurasi `$middleware->trustProxies(at: '*')` pada [`bootstrap/app.php`](file:///Users/abedzul/Desktop/htdocs/rapat/bootstrap/app.php) guna memastikan Laravel mendeteksi header SSL/HTTPS dari reverse proxy cPanel.
  - Penegasan skema HTTPS global (`URL::forceScheme('https')`) pada [`app/Providers/AppServiceProvider.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Providers/AppServiceProvider.php) untuk lingkungan produksi.
  - Pembatasan aturan redirect *trailing slash* pada [`public/.htaccess`](file:///Users/abedzul/Desktop/htdocs/rapat/public/.htaccess) hanya untuk metode `GET` (`RewriteCond %{REQUEST_METHOD} =GET`) guna mencegah konversi request `POST /livewire/update` menjadi `GET` akibat redirect 301.
  - Penambahan direktif `Options -MultiViews -Indexes` pada root [`.htaccess`](file:///Users/abedzul/Desktop/htdocs/rapat/.htaccess).
  - **Penyebab Utama Terpecahkan (ModSecurity WAF Block)**: Ditemukan bahwa payload string Base64 SVG memicu blokir XSS oleh Web Application Firewall (ModSecurity/Imunify360) di server cPanel. Saat WAF memblokir request POST dengan status 406/403, konfigurasi `ErrorDocument` Apache di cPanel secara internal mengalihkan (subrequest) ke `index.php` dengan metode **GET**, yang menyebabkan Laravel melempar `405 Method Not Allowed`. Solusi definitif: mengirim data tanda tangan hanya sebagai string koordinat mentah (`width|height|pathData`) dari sisi klien, lalu membangun ulang tag HTML SVG-nya murni di backend (PHP) agar sama sekali tidak terdeteksi oleh WAF.

## [1.3.8] - 2026-08-25

### Diperbaiki
- **Polyfill `iconv` untuk Kompatibilitas Lingkungan cPanel**: Penambahan fungsi polyfill `iconv()` otomatis berbasis `mb_convert_encoding()` pada [`bootstrap/app.php`](file:///Users/abedzul/Desktop/htdocs/rapat/bootstrap/app.php) guna mencegah kegagalan pembuatan QR Code (`BaconQrCode\Encoder\iconv`) pada server shared hosting cPanel yang belum mengaktifkan ekstensi PHP `iconv`.
- **Import Facade QR Code**: Penambahan import `SimpleSoftwareIO\QrCode\Facades\QrCode` pada komponen Livewire [`livewire/meetings/header.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/header.blade.php).

## [1.3.7] - 2026-08-25

### Ditambahkan
- **Modul Audit Log Aktivitas Sistem**:
  - Model [`AuditLog.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Models/AuditLog.php) dan layanan [`AuditLogger.php`](file:///Users/abedzul/Desktop/htdocs/rapat/app/Services/AuditLogger.php) untuk pencatatan otomatis aktivitas autentikasi (login SIMPEG/lokal, logout), pembuatan/penghapusan rapat, dan pengesahan TTE BSrE lengkap beserta NIP dan alamat IP.
  - Fitur pembersihan otomatis (*automatic pruning*) untuk audit log berusia lebih dari 90 hari via `model:prune`.
  - Halaman antarmuka Audit Log ([`livewire/admin/audit-logs.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/admin/audit-logs.blade.php)) khusus Super Admin dengan toolbar responsif, filter pill aksi, filter rentang tanggal, dan bilah pencarian *fluid*.

### Diubah
- **Penyelarasan & Pemisahan Tombol Reset Filter**:
  - Penambahan tombol *badge* **Reset Filter** pada toolbar Daftar Rapat ([`meetings/index.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/livewire/meetings/index.blade.php)) saat filter aktif.
  - Pemisahan fungsi tombol silang `(x)` di dalam kolom pencarian (khusus *quick clear* teks) dan tombol `Reset Filter` di luar (khusus parameter status/role/tanggal/OPD) untuk mencegah duplikasi tombol reset.
  - Standardisasi tombol reset di seluruh tampilan data kosong (*Empty State*) dengan efek interaksi `active:scale-95 transition-all cursor-pointer`.
- **Standardisasi Tampilan Empty State**:
  - Penyelarasan desain wadah ikon (`w-14 h-14 bg-slate-100 rounded-2xl`), ikon stroke 1.5, dan tipografi judul (`text-base font-extrabold text-slate-900`) di seluruh modul (Daftar Rapat, Riwayat Rapat, Audit Log, Master OPD, Master Pengguna, Presensi, dan Pengaturan OPD).
  - Penghapusan teks subjudul pada tampilan data kosong agar seragam, rapi, dan minimalis.
- **Penyempurnaan Tabel Audit Log**:
  - Penyesuaian proporsi lebar kolom: Tanggal & Waktu (`w-48`), Nama & NIP (`w-56`), Aksi (`w-32`), Keterangan (`flex-1 / line-clamp-2`), dan Alamat IP (`w-36`).
  - Pengubahan nama header kolom: `Pelaku` &rarr; `Nama & NIP` dan `Waktu` &rarr; `Tanggal & Waktu`.
  - Penghapusan avatar inisial pada kolom Nama & NIP untuk tampilan yang lebih bersih.
  - Peringkasan label aksi dan deskripsi log (contoh: `TTE BSrE` &rarr; `TTE`, `Login ASN` &rarr; `Login`).

## [1.3.6] - 2026-08-25

### Ditambahkan
- **Indikator Loading State Tombol Login**: Penambahan animasi spinner `animate-spin` pada ikon tombol login dan atribut `wire:loading.attr="disabled"` untuk mencegah klik ganda saat proses otentikasi SIMPEG berjalan.
- **Top Loading Progress Bar Guest Layout**: Penambahan bilah animasi progress bar Livewire (`wire:loading.delay.shortest`) di bagian atas pada [`layouts/guest.blade.php`](file:///Users/abedzul/Desktop/htdocs/rapat/resources/views/layouts/guest.blade.php).

## [1.3.5] - 2026-08-25

### Ditambahkan
- **Dependensi Vendor Teroptimasi untuk cPanel**: Penyesuaian `.gitignore` untuk menyertakan folder `vendor/` hasil optimasi `composer install --no-dev --optimize-autoloader` guna mendukung deployment instan di shared hosting cPanel tanpa memerlukan Composer CLI di server.
- **Konfigurasi Enkripsi SMTP**: Penambahan parameter `encryption` pada konfigurasi mailer SMTP [`config/mail.php`](file:///Users/abedzul/Desktop/htdocs/rapat/config/mail.php).

### Diubah
- **Standarisasi Lingkungan Produksi & `.env.example`**: Perapian kelompok variabel environment dan penambahan dokumentasi nilai konfigurasi cPanel, BSrE (`BSRE_SIGN_LOCATION`), SIMPEG, serta Google Gemini AI.
- **Penguatan Keamanan Root `.htaccess`**: Penambahan aturan proteksi untuk memblokir akses langsung ke berkas tersembunyi (`.env`, `.git`) dan direktori sensitif (`app`, `storage`, `vendor`, dll).
- **Pembaruan Panduan Deploy di `README.md`**: Penyederhanaan langkah instalasi cPanel langsung via `git clone`/`git pull`.

## [1.3.4] - 2026-08-25

### Ditambahkan
- **Sinkronisasi Pangkat SIMPEG**: Pemetaan dan penyimpanan otomatis atribut `pangkat` dari respon API SIMPEG (`pangkat_nama`) saat login ASN (`LoginForm.php`) dan saat scan presensi mandiri (`check-in.blade.php`).
- **Pelacakan Aset Build untuk cPanel**: Penyesuaian `.gitignore` untuk menyertakan folder `public/build` sehingga aset CSS/JS siap pakai tanpa memerlukan Node.js di server cPanel.
- **Standarisasi Struktur Folder Storage**: Penambahan berkas `.gitignore` standar di seluruh subdirektori `storage/` (`storage/app/public`, `storage/framework/*`, `storage/logs`) guna memastikan struktur direktori otomatis terbuat saat deployment Git.

### Diubah
- **Konsolidasi & Perapian Migrasi Database**:
  - Menggabungkan kolom `pangkat` langsung ke skema utama [`0001_01_01_000000_create_users_table.php`](file:///Users/abedzul/Desktop/htdocs/rapat/database/migrations/0001_01_01_000000_create_users_table.php).
  - Menggabungkan seluruh kolom TTE BSrE langsung ke skema utama [`2026_08_20_000004_create_meetings_table.php`](file:///Users/abedzul/Desktop/htdocs/rapat/database/migrations/2026_08_20_000004_create_meetings_table.php).
  - Menghapus 2 berkas migrasi alter tabel redundan.
- **Penyelarasan UI Header & Dokumen Rapat**:
  - Pemindahan badge status dokumen ke sisi kanan bilah aksi pada header rapat.
  - Penyembunyian badge status rapat khusus untuk pengguna dengan peran `pimpinan`.
  - Reaktivitas realtime Livewire antar-tab saat aksi "Buka Revisi" dijalankan melalui penyiaran event `meeting-updated`.
  - Desain tombol "Lihat PDF" yang telah di-TTE bergaya *emerald verified* dan pembersihan badge ganda.
- **Konfigurasi Server & Dokumentasi**:
  - Standardisasi berkas `.htaccess` di root proyek menjadi aturan *forwarder* publik yang ringkas dan aman.
  - Pembaruan berkas [`README.md`](file:///Users/abedzul/Desktop/htdocs/rapat/README.md) menjadi ringkas, akurat dengan kondisi sistem eksisting, dan bersih dari data kredensial.

### Dihapus
- **Pembersihan Berkas Legacy & Prototipe**:
  - Menghapus seluruh berkas statis prototipe lama di root dan `public/` (`admin.html/php`, `api.php`, `login.html/php`, `meeting.html/php`, `server.js`, `create_assets.js`, `build_zip.py`, `public/assets/`, `.htaccess-1-barebones`, `.htaccess-2-routing`).
  - Menghapus berkas scaffolding autentikasi Laravel Breeze yang tidak digunakan (`VerifyEmailController.php`, `register.blade.php`, `forgot-password.blade.php`, `reset-password.blade.php`, `confirm-password.blade.php`, `verify-email.blade.php`, `welcome/navigation.blade.php`).
  - Menghapus komponen blade yang tidak terpakai (`action-message.blade.php`, `application-logo.blade.php`, `danger-button.blade.php`, `secondary-button.blade.php`, `textarea-input.blade.php`).
  - Menghapus berkas dokumen perancangan dan PDF teknis internal (`CPANEL_DEPLOYMENT_GUIDE.md`, `CPANEL_TROUBLESHOOT.md`, `DEPLOYMENT_GUIDE.md`, `DESIGN.md`, `Rancangan_Modul_Manajemen_Rapat.md`, `pdf-petunjuk-teknis-api-esign-client-service-v221-sign-2_compress.pdf`).

## [1.3.3] - 2026-08-25

### Ditambahkan
- **Integrasi BSrE API Client Service v2**: Dukungan penuh penandatanganan digital kriptografi resmi BSrE - BSSN (`POST /api/v2/sign/pdf` & `/api/v2/user/check/status`) berstandar `PAdES-BASELINE-LT` dengan Time-Stamp Authority (TSA).
- **Pengujian Error Handling BSrE**: Unit & feature test untuk simulasi kesalahan passphrase (`HTTP 400 - Kode 2031`), pengguna tidak terdaftar, dan koneksi server pada `tests/Feature/BsreEsignTest.php`.

### Diubah
- **Penyelarasan Tampilan Dokumen Rapat (`overview.blade.php`)**:
  - Penataan urutan dokumen menjadi: 1. Presensi Rapat, 2. Dokumentasi Rapat, 3. Notulen Rapat.
  - Penyelarasan format tabel dokumen menggunakan tata letak 2-kolom (`sm:w-1/4` dan `sm:w-3/4`) identik dengan kartu Informasi Rapat.
  - Penyesuaian label tombol TTE spesifik dokumen: `TTE Presensi`, `TTE Dokumentasi`, `TTE Notulen`.
- **Standardisasi & Peringkasan Badge Status TTE**:
  - Label badge disederhanakan menjadi ringkas: `Aktif`, `Belum Terdaftar`, `Expired`, `Pembaruan`, `Verifikasi`, `Belum Aktif`, `Tanpa Sertifikat`, `Suspend`, `Dicabut`, `NIK Kosong`, `Offline`.
  - Kotak peringatan pada modal TTE dipadatkan menjadi 1 baris penjelasan langsung.
- **Penyempurnaan Penanganan Error TTE (Modal UX)**:
  - Modal TTE tetap terbuka saat terjadi kesalahan (*passphrase* salah / gangguan koneksi).
  - Kolom input *passphrase* otomatis dikosongkan kembali dengan kursor tetap aktif untuk kemudahan input ulang.
  - Pesan kesalahan dipersingkat dan langsung pada intinya (contoh: *"Passphrase salah."*, *"NIK belum terdaftar di BSrE."*).
- **Penyajian Berkas Fisik PDF Ber-TTE (`MeetingExportController`)**:
  - Aksi "Lihat PDF" dan "Download" pada dokumen yang telah di-TTE menyajikan berkas fisik asli yang sama persis (`storage/app/public/signed_documents/...`) berisi segel kriptografi resmi BSrE.
  - Perbaikan render QR Code dan *footer* resmi BSrE pada template PDF sebelum dokumen dikirim ke mesin penandatanganan BSrE.
- **Standardisasi Payload `reason` TTE**:
  - Format `reason` TTE distandardisasi menjadi: `TTE Notulen - {Judul Rapat}`, `TTE Presensi - {Judul Rapat}`, dan `TTE Dokumentasi - {Judul Rapat}`.
- **Penyelarasan & Minimalisasi Halaman Verifikasi Publik (`verify-tte.blade.php`)**:
  - Tampilan difokuskan pada keaslian dokumen dan identitas penandatangan dengan tata letak minimalis dan elegan.
  - Tombol **`Download PDF`** disesuaikan untuk langsung mengunduh berkas fisik PDF bertandatangan digital resmi tanpa melalui pratinjau (*direct attachment download*).

## [1.3.2] - 2026-08-25

### Ditambahkan
- **Rollback Status Rapat (`Lanjutkan Rapat`)**: Fitur pengembalian status rapat dari *Selesai* (`completed`) ke *Sedang Berlangsung* (`ongoing`) di header rapat dengan proteksi pembatalan otomatis jika salah satu dokumen telah di-TTE BSrE.
- **Buka Revisi Presensi (`unlockForRevision`)**: Kemampuan membuka kunci presensi ber-TTE untuk revisi/perbaikan presensi oleh penyelenggara/admin.
- **Card Antrean Menunggu TTE**: Widget daftar rapat menunggu TTE diaktifkan pada dasbor **Admin OPD** dan **Pegawai** pembuat rapat.
- Suite pengujian validasi jam/menit dan pembukaan kembali rapat (`tests/Feature/MeetingValidationTest.php`).

### Diubah
- **Pilihan Waktu Rapat 24 Jam**: Seleksi jam kerja (`08` s/d `16`) dan menit interval 15 menit (`00`, `15`, `30`, `45`) pada modal Buat dan Edit Rapat.
- **Integrasi Konteks & AI Notulen**: Notulen AI memadukan konteks pimpinan, agenda, dan peserta pada Pembukaan secara proporsional serta menyusun Pembahasan & Kesimpulan berbasis catatan asli editor.
- **Visibilitas Tombol Header Rapat**:
  - Tombol **`QR Code`** presensi hanya muncul saat rapat sedang berlangsung (*status `ongoing`*).
  - Tombol **`Lihat PDF`** (Presensi, Dokumentasi, Notulen) dan **`Notulen AI`** hanya tampil setelah rapat diselesaikan (*status `completed`*).
  - Standardisasi dimensi seluruh tombol dan badge toolbar header rapat menjadi `px-4 py-2.5 text-sm font-bold rounded-xl gap-2`.
- **Penyelarasan Halaman Verifikasi TTE (`verify-tte.blade.php`)**:
  - Penandatangan ditempatkan di baris teratas tanpa NIP.
  - Label instansi disesuaikan menjadi **OPD** dengan penanganan teks panjang (*wrapping* rapi).
  - Penghapusan tautan kembali ke beranda dan tombol unduh agar tampilan murni sebagai bukti verifikasi keabsahan dokumen.
- **Standardisasi Badge & Pesan TTE**:
  - Label badge status sebagian diubah menjadi **`1/3 Sudah TTE`** dan **`2/3 Sudah TTE`**.
  - Standardisasi salinan (*copy*) alert sukses dan pesan kesalahan TTE BSrE menjadi ringkas, jelas, dan profesional.

### Dihapus
- Data akun pengguna dan pejabat penandatangan pengujian (*Testing TTE / NIP 123456*) dari `RoleAndUserSeeder.php` dan `OpdSeeder.php`.

## [1.3.1] - 2026-08-24

### Ditambahkan
- Halaman **Riwayat Rapat** (`meetings/history.blade.php`) terpadu untuk peran Admin OPD dan Super Admin lengkap dengan filter pencarian instan, filter rentang tanggal, filter OPD (Super Admin), dan status TTE dokumen.
- Metode pembantu `signedTteCount()` pada model `Meeting` untuk menghitung dokumen yang telah sah ditandatangani.
- Suite pengujian otorisasi dan akses halaman riwayat rapat (`tests/Feature/MeetingHistoryTest.php`).

### Diubah
- **Penyelarasan Dashboard Pimpinan**:
  - Penanganan kartu antrean **Menunggu TTE** dengan *empty state* bersih *"Tidak Ada Dokumen yang Menunggu TTE"* jika seluruh dokumen telah ditandatangani.
  - Penghapusan kartu *Rapat Mendatang* pada dasbor Pimpinan untuk antarmuka yang lebih ringkas dan fokus.
- **Penyelarasan Tampilan Daftar Rapat (`meetings/index.blade.php`)**:
  - Penyembunyian filter status (*Semua Status, Dijadwalkan, Berlangsung, Selesai*) khusus bagi peran Pimpinan.
  - Kolom status tabel bagi peran Pimpinan diubah menjadi **Status Dokumen** (`TTE Lengkap`, `X/3 TTE`, `Menunggu TTE`, `Draft`) tanpa badge status siklus rapat.
- **Penyelarasan Berkas Ekspor PDF (Notulen, Presensi, Dokumentasi)**:
  - Penanganan baris informasi **Pimpinan** dengan subline nama jabatan proporsional agar nama jabatan yang panjang tidak merusak tata letak.
  - Penataan ulang tabel informasi rapat menjadi 3 kolom dengan kolom titik dua (`:`) presisi (`12px`) untuk penjajaran vertikal sempurna.
  - Perluasan *spacing* dan *padding* sebelum dan sesudah blok informasi rapat agar dokumen lebih lega dan estetis.
  - Penyelarasan indentasi bertingkat (*step-aligned*) pada isi notulen sehingga teks judul bab sejajar dengan anak penomoran (`22px`, `44px`, `66px`).
  - Standardisasi *line-height* dokumen PDF secara konsisten (`1.45` – `1.5`).
- **Penyelarasan Tabel Riwayat Rapat (`meetings/history.blade.php`)**:
  - Penataan *toolbar* filter menjadi 1 baris terpadu horizontal (*fluid search bar*, *compact date range container*, dropdown OPD, dan tombol reset).
  - Penyesuaian label header kolom aksi dari *Unduh Dokumen* menjadi *Download*.
  - Penataan nama Perangkat Daerah (OPD) di bawah lokasi rapat pada kolom Agenda & Lokasi dengan *clean text badge*, proteksi *truncate*, dan *tooltip*.
- **Penyelarasan Fitur Notulen**:
  - Perubahan nama dan tombol aksi dari **Bantuan AI** menjadi **Notulen AI** pada antarmuka editor dan modal hasil AI.
  - Penyempurnaan penyelarasan nama pada tombol dropdown profil di topbar dengan lebar otomatis (`whitespace-nowrap`).
  - Standardisasi label badge dokumen menjadi **`TTE Lengkap`** di seluruh aplikasi.

## [1.3.0] - 2026-08-24

### Ditambahkan
- **Asisten AI Notulen Rapat (Google Gemini AI)** (`app/Services/GeminiAiService.php`): Fitur perapian catatan mentah rapat menjadi notulen kedinasan terstruktur secara otomatis dalam 1 klik berbasis Tata Naskah Dinas Pemerintah Daerah.
- Konfigurasi Gemini API Key pada Pengaturan Sistem (`admin/settings.blade.php`) lengkap dengan tombol **Uji Koneksi API** dan mekanisme *fallback model* cerdas (`gemini-2.5-flash`, `gemini-2.0-flash`, `gemini-1.5-flash`, `gemini-1.5-pro`).
- Modal interaktif **Bantuan AI** (`ai-minutes-modal`) yang berdimensi luas (`maxWidth="3xl"`), dengan indikator *loading* animasi berbasis Alpine.js Event Bus (`ai-loading-start` & `ai-loading-stop`).
- Fitur **Auto-Hide** (menghilang otomatis setelah 4 detik) dan tombol tutup manual `(X)` pada komponen alert notifikasi ([`components/alert.blade.php`](resources/views/components/alert.blade.php)).
- Suite pengujian otomatis terintegrasi untuk modul AI Notulen ([`tests/Feature/GeminiAiNotulenTest.php`](tests/Feature/GeminiAiNotulenTest.php)).
- Arsitektur **Multirole & Role Switcher** pada model `User` (`ROLE_PRIORITIES`, `defaultRole()`, `currentRole()`, `hasActiveRole()`, `switchRole()`, `sortedRoles()`).
- Menu dropdown profil dan modal **Role** (`switch-role-modal`) di topbar untuk pergantian peran aktif secara instan tanpa perlu *re-login*.
- Fitur penugasan multirole pada Master Pengguna (`users/index.blade.php`) dengan seleksi tombol pil (*checkbox pills*) yang ringkas dan bebas visual *clutter*.
- Tampilan seluruh badge peran yang di-assign ke pengguna pada header halaman **Profil Pengguna** (`profile.blade.php`).
- Dukungan *teleportation* (`<template x-teleport="body">`) pada komponen `<x-modal>` agar seluruh modal tampil presisi di tengah layar (*viewport center*) dan terbebas dari batasan elemen induk (*containing block trap*).
- Suite pengujian fitur multirole lengkap ([`tests/Feature/MultiroleTest.php`](tests/Feature/MultiroleTest.php)).

### Diubah
- Standardisasi luaran notulen AI dan *placeholder* editor textarea mengikuti **Tata Naskah Dinas Permendagri No. 1 Tahun 2023** (3 bab utama: `1. Pembukaan`, `2. Pembahasan (a., b., c.)`, `3. Kesimpulan (a., b., c.)`).
- Penyelarasan format, spasi, dan tipografi seluruh dokumen cetak PDF (Notulen, Presensi, Dokumentasi):
  - Standardisasi margin halaman `@page` seragam (`8mm 10mm 12mm 10mm`).
  - Standardisasi tabel informasi metadata rapat (`Agenda`, `Hari / Tanggal`, `Waktu`, `Tempat`, `Pimpinan`).
  - Penerapan *single continuous table grid* dan *hanging indent* presisi pada PDF notulen.
  - Perbaikan urutan ekspresi reguler (*regex*) agar sub-poin huruf `c.`, `d.`, dst. tidak keliru terbaca sebagai angka romawi judul bab.
- Peningkatan reaktivitas dan sinkronisasi status rapat (`startMeeting()`, `finishMeeting()`, dan update penandatangan) menggunakan *livewire event listener* `#[On('meeting-updated')]` dan navigasi instan SPA (`$this->redirect(..., navigate: true)`).
- Penyesuaian hierarki pemilihan pejabat penandatangan multi-jabatan pada modal edit rapat berbasis pencocokan ganda (*Title & Name*).
- Penghapusan badge *"Disusun dengan AI"* pada antarmuka notulen demi menjaga estetika kedinasan.
- Penyesuaian seluruh otorisasi menu, dashboard, pembuatan rapat, workspace dokumen, dan ekspor PDF berbasis peran aktif (`hasActiveRole()`).
- Penyesuaian sinkronisasi API SIMPEG pada model `Opd.php` menggunakan `assignRole` agar penugasan multirole pengguna tidak terhapus/tertimpa saat sinkronisasi dijalankan.
- Penyederhanaan menu dropdown profil di topbar menjadi 3 menu utama yang bersih (*Profil, Role, Logout*).
- Pembaruan label penugasan peran pada formulir pengguna menjadi *"Role"*.

## [1.2.0] - 2026-08-23

### Ditambahkan
- Integrasi modul **Tanda Tangan Elektronik (TTE) BSrE - BSSN** (`app/Services/BsreEsignService.php`) untuk pengesahan digital Notulen, Daftar Hadir, dan Dokumentasi Foto Rapat.
- Modal eksekusi TTE terintegrasi pada workspace rapat dengan validasi Passphrase, toggle *show/hide* password interaktif, dan verifikasi NIK pejabat penandatangan.
- Mekanisme proteksi penguncian dokumen setelah TTE (*Locked State*) serta fitur **Buka Kunci untuk Revisi** pada tab Notulen, Presensi, dan Dokumentasi Foto.
- Halaman verifikasi publik TTE (`resources/views/livewire/meetings/verify-tte.blade.php`) berfokus langsung pada keabsahan dokumen dan identitas penandatangan, selaras dengan *design system* halaman *check-in*.
- Rute publik pengunduhan dokumen PDF bertanda tangan digital resmi (`meetings.verify.download`).
- Blok QR Code verifikasi dinamis pada PDF ekspor dokumen rapat yang mengarahkan pembaca ke halaman verifikasi publik.
- Catatan kaki (*footer legal disclaimer*) standar BSrE BSSN dan Permendagri No. 1 Tahun 2023 pada margin bawah berkas PDF yang telah ditandatangani.
- Seeder modular Master OPD ([`OpdSeeder.php`](database/seeders/OpdSeeder.php)) yang memuat 42 Perangkat Daerah se-Kabupaten Sinjai lengkap dengan `unit_id` resmi SIMPEG.
- Kolom `nik` (16 digit) pada tabel `users`, `opds` (`leader_nik`), dan `opd_signers` (`nik`) untuk kesiapan integrasi TTE BSrE (BSSN).
- Fitur **Cek NIP SIMPEG** otomatis pada modal form penandatangan di Pengaturan OPD dan Master Pengguna.
- Komponen paginasi kustom minimalis berikon (`<x-pagination>`) untuk penyajian data tabel yang rapi.
- Test suite pengujian TTE BSrE dan rute verifikasi publik ([`tests/Feature/BsreEsignTest.php`](tests/Feature/BsreEsignTest.php)).

### Diubah
- Standardisasi seluruh tipografi dan tata letak dokumen ekspor PDF (Notulen, Daftar Hadir, Dokumentasi) sesuai standar Permendagri No. 1 Tahun 2023 menggunakan font `Arial, sans-serif` murni.
- Penyelarasan konsisten desain seluruh tab workspace detail rapat (*Ringkasan, Presensi, Dokumentasi, Notulen*) dengan *toolbar header* terpadu dan kontainer data kartu putih halus.
- Penyelarasan visual kartu widget **Rapat Berlangsung** dan **Menunggu TTE** pada dasbor, serta perluasan akses monitoring antrean TTE bagi peran Super Admin.
- Penyempurnaan notifikasi sistem: penghapusan duplikasi alert sukses pada workspace rapat.
- Pembersihan spasi dan indentasi berlebih pada tampilan notulen mode *read-only*.
- Pembaruan label badge header rapat menjadi *"Sudah TTE Semua"*.
- Penyesuaian blok tanda tangan pada PDF: nama pejabat berformat tebal (`font-bold`) tanpa garis bawah (*underline*).
- Penambahan restriksi peran *Pimpinan*: penutupan akses dan penyembunyian tombol buat rapat pada antarmuka serta proteksi *authorization guard* backend.
- Konsolidasi seluruh migrasi database menjadi 11 berkas atomik bersih siap pasang di server cPanel / produksi.
- Penggabungan tampilan Kepala OPD ke dalam tabel Pejabat Penandatangan pada halaman Pengaturan OPD dengan dukungan modal edit dinamis (Eselon, Pangkat, Jabatan, NIK, dan Unit Kerja).
- Penghapusan input manual password pada form pembuatan pengguna karena otentikasi login terhubung langsung ke Gateway ENIKDA / SIMPEG Sinjai.
- Penyelarasan tata letak formulir penandatangan: Eselon dan Pangkat dalam 2 kolom, Jabatan lebar penuh, dan label "Unit Kerja".
- Pembersihan berkas-berkas pengujian dan migrasi yang tidak terpakai.

## [1.1.0] - 2026-08-22

### Ditambahkan
- Relasi `opd_id` pada model `Meeting` dan migrasi basis data untuk pemilihan OPD langsung saat Super Admin membuat rapat.
- Pembatasan dan proteksi otorisasi berbasis peran: pengguna non-pembuat rapat (pegawai biasa) hanya dapat membaca notulen (*read-only*) dan galeri foto tanpa hak mengubah/menghapus.
- Modul Pengaturan Sistem (`Setting` model & migration, `admin/settings.blade.php`) untuk pengelolaan konfigurasi tautan Survei Kepuasan Masyarakat (SKM).
- Integrasi ajakan pengisian Survei Kepuasan Masyarakat (SKM) secara instan pada layar sukses presensi (*check-in*).
- Komponen lencana peran pengguna global `<x-user-role-badge>` untuk standardisasi tampilan *role* (Super Admin, Admin OPD, Pegawai).
- Modul Manajemen OPD & Penandatangan Dokumen Rapat (`Opd`, `OpdSigner`) lengkap dengan manajemen Eselon dan sinkronisasi pimpinan OPD.
- Komponen lencana status rapat global `<x-meeting-status-badge>`.
- Tombol filter pill interaktif pada Daftar Rapat, Master Pengguna, dan Master OPD dengan badge penghitung dinamis.
- Nomor urut otomatis (`#`) pada tabel daftar hadir presensi rapat.

### Diubah
- Penyederhanaan tampilan akun di *topbar*: trigger menampilkan avatar, nama, dan role secara proporsional; menu dropdown fokus pada Profil dan Logout.
- Penyesuaian widget *Menunggu Notulen* pada dasbor: hanya rapat berstatus *ongoing* atau *completed* tanpa notulen yang dimuat, serta tombol "Isi Notulen" dikhususkan bagi pembuat rapat dan admin.
- Audit dan perapian seluruh *Empty State*: menghilangkan teks deskriptif berlebih pada notulen, dokumentasi, presensi, tabel rapat, pengguna, dan OPD.
- Penyelarasan kartu *Rapat Hari Ini* pada Landing Page dengan estetika *glassmorphism*, pemisahan tegas header dan body, serta format tipografi bersih tanpa ikon repetitif.
- Perapian urutan tab workspace rapat menjadi *Ringkasan, Presensi, Dokumentasi, Notulen* dan penghapusan badge angka status pada tab Notulen.
- Standardisasi token warna lencana rapat: status *Live / Berlangsung* menggunakan warna merah menyala (`rose-50 / rose-700`) dengan animasi denyut (*pulsing dot*).
- Pembaruan kartu statistik Dashboard menjadi 4 periode waktu kronologis (*Hari Ini, Minggu Ini, Bulan Ini, Tahun Ini*).
- Penyederhanaan widget *Rapat Berlangsung* pada dashboard dengan pembersihan elemen berlebih.
- Penghapusan kartu metrik statistik berlebih pada Master Pengguna dan Master OPD demi tata letak yang lebih ringkas dan fokus.
- Standardisasi seluruh teks *placeholder* di semua formulir dan *field* pencarian menggunakan format contoh yang seragam.
- Standardisasi seluruh *Page Header* dan *Modal Header* di semua modul (Daftar Rapat, Dashboard, Pengguna, OPD, Pengaturan, dan Profil).
- Refaktor modal sistem (`components/modal.blade.php`): penguncian backdrop luar dan penerapan scroll internal pada kartu modal untuk modal dengan konten panjang.
- Relabeling *field* form rapat menjadi "Agenda", "Waktu Mulai", "Waktu Selesai", dan "Penandatangan", serta penyelarasan pesan error validasi agar presisi sesuai nama label form.
- Audit dan standardisasi notifikasi alert (`<x-alert>`) dan pesan kesalahan di seluruh modul agar ringkas, to-the-point, dan bebas dari tag header redundan.
- Audit dan penyelarasan seluruh tombol di sistem: penambahan ikon relevan pada tombol aksi simpan modal, halaman publik (*welcome*, *login*, *check-in*), tombol aksi tabel daftar rapat ("Lihat" dengan ikon mata), dan perbaikan tombol dasbor ("Presensi" & "Isi Notulen") menjadi tombol solid.
- Penyesuaian kolom tabel Master OPD: penyederhanaan nama kolom menjadi "Nama & Kepala OPD" dan penghapusan tampilan pangkat pimpinan dari tabel.
- Penataan ulang kartu *Informasi Rapat* pada tab Ringkasan detail rapat agar menyatu langsung tanpa bingkai ganda (*card inside card*).
- Penempatan *badge* status rapat ke posisi atas (*eyebrow badge*) di *header* detail rapat untuk mendukung judul agenda yang sangat panjang.
- Penyempurnaan halaman profil pengguna: pemindahan *badge* peran ke sudut kanan *header*, NIP sebagai subjudul, dan pembersihan baris duplikat.
- Perbaikan *glitch* pada *focus/click textarea* notulen rapat dengan mengganti `transition-all` menjadi `transition-colors`.
- Standardisasi form unggah foto dokumentasi, tombol unduh, dan *Lightbox Modal* galeri rapat.

## [1.0.0] - 2026-08-20

### Ditambahkan
- Komponen Blade `<x-textarea-input>` untuk standardisasi kolom teks multi-baris di seluruh modul rapat.
- Komponen Blade `<x-alert>` dengan dukungan 4 varian semantik (`success`, `warning`, `danger`, `info`) dan ikon SVG terintegrasi.
- Tombol Call-to-Action (CTA) interaktif pada *Empty State* daftar rapat (`+ Tambah Rapat Baru` dan `Reset Pencarian & Filter`).
- Tampilan ilustrasi visual dan petunjuk ramah pada *Empty State* presensi dan notulen rapat.
- Label aksesibilitas tersembunyi (`sr-only`) untuk pembaca layar pada form pencarian dan filter status rapat.

### Diubah
- Standardisasi token warna lencana status rapat (*Status Badges*):
  - `scheduled`: `bg-gray-100 text-gray-800` (Netral).
  - `ongoing`: `bg-amber-100 text-amber-800 animate-pulse` (Peringatan/Aktif).
  - `completed`: `bg-primary-100 text-primary-800` (Tema Utama/Sukses).
- Penyelarasan radius kelengkungan sudut secara konsisten (`rounded-xl` pada tombol/input dan `rounded-2xl` pada kontainer modal).
- Penyempurnaan 6 kondisi interaksi tombol UI (*hover*, *focus ring*, *active:scale-95*, *disabled states*, dan *transitions*).
- Peningkatan fokus visual pada navigasi tab, tombol dropdown pengguna, dan navigasi menu seluler.
- Pembersihan sisa token warna hardcode `focus-visible:ring-[#FF2D20]` pada navigasi selamat datang.
