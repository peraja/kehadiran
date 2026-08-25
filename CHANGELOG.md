# Changelog

Semua perubahan penting pada proyek ini dicatat dalam berkas ini.

Format berkas ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/), dan proyek ini mematuhi [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
