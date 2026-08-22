# eRapat - Sistem Manajemen Rapat Terpadu Pemkab Sinjai

Aplikasi digitalisasi tata kelola administrasi rapat resmi untuk lingkungan Pemkab Sinjai. Memfasilitasi penjadwalan rapat, presensi mandiri berbasis QR Code dan tanda tangan digital (untuk ASN maupun tamu eksternal), pencatatan notulen resmi, galeri dokumentasi terkompresi otomatis, hingga ekspor dokumen PDF dengan kop dinas resmi.

---

## Fitur Utama

- **Manajemen Agenda Rapat:** Penjadwalan, pengaturan status rapat (*Dijadwalkan*, *Berlangsung*, *Selesai*), lokasi, serta batas akses OPD.
- **Presensi Mandiri QR & Tanda Tangan Digital:** Integrasi data ASN via API Kepegawaian (NIP) dan dukungan peserta eksternal dilengkapi *signature pad* canvas HTML5.
- **Tanda Tangan Elektronik (TTE) BSrE - BSSN:** Pengesahan digital dokumen resmi (Notulen, Daftar Hadir, Dokumentasi) via API BSrE, dilengkapi QR Code verifikasi publik dan *footer legal disclaimer* standar Permendagri No. 1 Tahun 2023.
- **Notulen Rapat & Ekspor PDF:** Pencatatan ringkasan pembahasan serta cetak otomatis ke PDF resmi bertata letak kop dinas (DomPDF).
- **Dokumentasi Terintegrasi & ZIP Archive:** Unggah multi-foto dengan kompresi otomatis (Intervention Image) serta unduh galeri dalam format berkas ZIP.
- **Desain UI Responsif Berbasis Tailwind CSS:** Mematuhi panduan desain pada `DESIGN.md` dengan pendekatan *Mobile First*, interaksi mikro 6 kondisi, dan aksesibilitas ramah pengguna.

---

## Tumpukan Teknologi (Tech Stack)

- **Backend:** Laravel 12.x, PHP 8.2+
- **Frontend / Reaktivitas:** Laravel Livewire 3 (Volt), Alpine.js, Tailwind CSS
- **Basis Data:** SQLite / MySQL / MariaDB
- **Autentikasi & Otorisasi:** Laravel Breeze, Spatie Laravel Permission
- **Utilitas Tambahan:** SimpleSoftwareIO QR Code, Intervention Image, Barryvdh DomPDF, ZipArchive

---

## Panduan Instalasi & Menjalankan Aplikasi

1. **Klon Repositori:**
   ```bash
   git clone https://github.com/peraja/kehadiran.git
   cd kehadiran
   ```

2. **Pasang Dependensi PHP & Node.js:**
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Lingkungan (.env):**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migrasi Basis Data & Seeding:**
   ```bash
   php artisan migrate --seed
   ```

5. **Buat Symlink Storage:**
   ```bash
   php artisan storage:link
   ```

6. **Jalankan Server Pengembangan:**
   ```bash
   npm run dev
   php artisan serve
   ```

---

## 📄 Lisensi
Hak Cipta Dilindungi &copy; Pemerintah Kabupaten Sinjai.
