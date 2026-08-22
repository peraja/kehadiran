# Changelog

Semua perubahan penting pada proyek ini dicatat dalam berkas ini.

Format berkas ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/), dan proyek ini mematuhi [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 2026-08-22

### Ditambahkan
- Modul Manajemen OPD & Penandatangan Dokumen Rapat (`Opd`, `OpdSigner`) lengkap dengan manajemen Eselon dan sinkronisasi pimpinan OPD.
- Komponen lencana status rapat global `<x-meeting-status-badge>`.
- Tombol filter pill interaktif pada Daftar Rapat dan Master Pengguna dengan badge penghitung dinamis.
- Nomor urut otomatis (`#`) pada tabel daftar hadir presensi rapat.

### Diubah
- Standardisasi seluruh *Page Header* dan *Modal Header* di semua modul (Daftar Rapat, Dashboard, Pengguna, OPD, dan Profil).
- Refaktor modal sistem (`components/modal.blade.php`) menggunakan `m-auto` untuk penyelarasan vertikal otomatis dan scroll mulus pada modal tinggi.
- Relabeling *field* judul menjadi "Agenda" pada form Buat & Edit Rapat, serta pembersihan *field* ganda.
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
