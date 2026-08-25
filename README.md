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

## Panduan Deploy & Optimasi Performa Production (cPanel)

### 1. Persiapan di Lingkungan Lokal
Sebelum mengunggah atau melakukan *push* ke repositori:
```bash
# 1. Build aset frontend produksi (CSS & JS terkompresi Vite)
npm run build

# 2. Pastikan dependensi composer siap produksi
composer install --no-dev --optimize-autoloader

# 3. Commit dan push ke repository
git add .
git commit -m "chore: prepare production deployment"
git push origin main
```

### 2. Konfigurasi di cPanel / Server Hosting

1. **Konfigurasi Lingkungan (`.env`):**
   Pastikan variabel kunci berikut diatur untuk lingkungan produksi:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://nama-domain-anda.go.id

   # Driver Performa Ringan (Cocok untuk cPanel/Shared Hosting)
   CACHE_STORE=file
   SESSION_DRIVER=file
   QUEUE_CONNECTION=sync
   ```

2. **Eksekusi Migrasi & Link Storage di Terminal cPanel:**
   ```bash
   # Masuk ke direktori proyek di cPanel
   cd /home/username/public_html (atau path proyek Anda)

   # Jalankan migrasi database (termasuk indeks performa)
   php artisan migrate --force

   # Buat symlink storage publik jika belum ada
   php artisan storage:link
   ```

3. **Perintah Optimasi Kecepatan (Artisan Cache):**
   Jalankan serangkaian perintah berikut untuk mengaktifkan pre-compiled cache bawaan Laravel:
   ```bash
   # Optimasi konfigurasi, rute, view, dan event sekaligus
   php artisan optimize
   php artisan view:cache
   php artisan event:cache
   ```

   *(Catatan: Jika sewaktu-waktu terdapat perubahan kode atau file `.env`, bersihkan dan segarkan cache dengan perintah `php artisan optimize:clear && php artisan optimize`)*.

4. **Kompresi Aset & Caching Web Server (`.htaccess`):**
   Proyek ini telah dilengkapi konfigurasi otomatis pada file `public/.htaccess` meliputi:
   - **Gzip / Deflate Compression (`mod_deflate`)** untuk transfer file teks/JSON/SVG/CSS/JS yang 70%+ lebih hemat kuota dan cepat.
   - **Browser Caching (`mod_expires`)** selama 1 tahun untuk gambar, web font, dan aset bundle Vite.
   - **Security Headers** (`X-Frame-Options`, `X-Content-Type-Options`).

5. **Cron Job cPanel (Opsional / Otomatisasi Terjadwal):**
   Untuk menjalankan pembersihan log berkala (*auto-pruning audit logs* 90 hari):
   - Buka menu **Cron Jobs** di cPanel.
   - Tambahkan jadwal setiap menit:
     ```text
     * * * * * cd /home/username/path-proyek && php artisan schedule:run >> /dev/null 2>&1
     ```

---

## Lisensi
Hak Cipta &copy; Pemerintah Kabupaten Sinjai.
