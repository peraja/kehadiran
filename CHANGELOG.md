# Changelog

Semua perubahan penting pada proyek ini dicatat dalam berkas ini.

Format berkas ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/), dan proyek ini mematuhi [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 2026-08-23

### Ditambahkan
- Integrasi modul **Tanda Tangan Elektronik (TTE) BSrE - BSSN** (`app/Services/BsreEsignService.php`) untuk pengesahan digital Notulen, Daftar Hadir, dan Dokumentasi Foto Rapat.
- Modal eksekusi TTE terintegrasi pada workspace rapat dengan validasi Passphrase dan verifikasi NIK pejabat penandatangan.
- Halaman verifikasi publik TTE (`resources/views/livewire/meetings/verify-tte.blade.php`) dengan *design system* konsisten untuk memvalidasi keabsahan dokumen serta stempel waktu (*timestamp*) penandatanganan.
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
- Penyesuaian blok tanda tangan pada PDF: nama pejabat berformat tebal (`font-bold`) tanpa garis bawah (*underline*).
- Penambahan restriksi peran *Pimpinan*: penutupan akses dan penyembunyian tombol buat rapat pada antarmuka serta proteksi *authorization guard* backend.
- Pembaruan label status TTE pada antarmuka menjadi *"Sudah TTE"* dengan lencana hijau emerald.
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
