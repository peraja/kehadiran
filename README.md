# eRapat - Sistem Manajemen Rapat Terpadu Pemkab Sinjai

Aplikasi tata kelola administrasi rapat resmi di lingkungan Pemerintah Kabupaten Sinjai. Mendukung penjadwalan agenda, presensi mandiri berbasis QR Code dan tanda tangan digital, perapian notulen cerdas dengan Google Gemini AI, ekspor PDF kedinasan, galeri foto dokumentasi terkompresi, hingga sertifikasi Tanda Tangan Elektronik (TTE) BSrE - BSSN.

---

## Fitur Utama

- **Autentikasi SIMPEG & Multirole:** Login langsung menggunakan NIP dan password akun kepegawaian Pemkab Sinjai, dengan dukungan peran ganda (*Super Admin*, *Admin OPD*, *Pimpinan*, *Pegawai*) dan fitur peralihan peran aktif (*role switcher*).
- **Manajemen Agenda Rapat:** Penjadwalan rapat, pembatasan akses OPD, pembaruan status otomatis (*Dijadwalkan*, *Berlangsung*, *Selesai*), dan riwayat rapat terlaksana.
- **Presensi Mandiri & TTD Digital:** Presensi kehadiran peserta via scan QR Code / NIP SIMPEG dan registrasi tamu eksternal dengan tanda tangan canvas digital.
- **TTE BSrE - BSSN:** Pengesahan digital dokumen resmi (Notulen, Daftar Hadir, Dokumentasi) via API BSrE disertai halaman verifikasi publik QR Code.
- **Asisten Notulen AI:** Format otomatis notulen rapat standar kedinasan menggunakan Google Gemini AI dalam satu klik.
- **Ekspor Dokumen & Dokumentasi:** Cetak PDF bertata letak kop dinas resmi dan unduh arsip seluruh dokumentasi foto dalam format ZIP.
- **Audit Log Aktivitas:** Pencatatan dan monitoring rekam jejak aktivitas keamanan (Login, Logout, Buat/Hapus Rapat, TTE) khusus Super Admin dengan auto-pruning 90 hari.

---

## Tumpukan Teknologi

- **Backend:** Laravel 11/12, PHP 8.2+
- **Frontend:** Laravel Livewire 3 (Volt), Alpine.js, Tailwind CSS
- **Basis Data:** MySQL / MariaDB / SQLite
- **Otorisasi:** Spatie Laravel Permission
- **Integrasi Eksternal:** API Kepegawaian SIMPEG Sinjai, API BSrE BSSN, Google Gemini API

---

## Instalasi Lokal

1. **Klon Repositori & Pasang Dependensi:**
   ```bash
   git clone https://github.com/peraja/kehadiran.git
   cd kehadiran
   composer install
   npm install
   ```

2. **Konfigurasi Lingkungan:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Migrasi, Seeder, dan Storage Link:**
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

4. **Jalankan Server:**
   ```bash
   npm run dev
   php artisan serve
   ```

---

## Panduan Deploy ke cPanel

1. **Di Lokal (Build Aset & Push):**
   ```bash
   npm run build
   git add .
   git commit -m "Deploy production"
   git push origin main
   ```

2. **Di Terminal cPanel:**
   ```bash
   # Clone pertama kali (atau git pull origin main untuk update)
   git clone <URL_REPO> erapat && cd erapat

   # Setup file environment
   cp .env.example .env
   nano .env  # sesuaikan kredensial database & URL

   # Inisialisasi Laravel
   php artisan key:generate
   php artisan migrate --seed --force
   php artisan storage:link
   php artisan optimize
   ```

---

## Lisensi
Hak Cipta &copy; Pemerintah Kabupaten Sinjai.
