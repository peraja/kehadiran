# eRapat - Sistem Manajemen Rapat Terpadu Pemkab Sinjai

Aplikasi tata kelola administrasi rapat kedinasan di lingkungan Pemerintah Kabupaten Sinjai. Mendukung alur kerja menyeluruh mulai dari penjadwalan agenda, presensi mandiri berbasis QR Code dan tanda tangan digital, penyusunan notulen cerdas dengan Google Gemini AI, ekspor dokumen resmi, hingga sertifikasi Tanda Tangan Elektronik (TTE) BSrE - BSSN.

---

## Fitur Utama

- **Autentikasi Terintegrasi & Multirole:** Login menggunakan NIP akun SIMPEG Pemkab Sinjai dengan dukungan peran ganda (*Super Admin*, *Admin OPD*, *Pimpinan*, *Pegawai*) dan fitur peralihan peran aktif (*role switcher*).
- **Manajemen Agenda & Delegasi:** Penjadwalan rapat, alokasi OPD pelaksana, penentuan pejabat penandatangan dokumen, dan pembaruan status rapat (*Dijadwalkan*, *Berlangsung*, *Selesai*).
- **Presensi Mandiri & TTD Digital:** Presensi via scan QR Code / input NIP untuk ASN & PPPK Paruh Waktu, serta registrasi tamu eksternal dengan tanda tangan canvas digital.
- **TTE BSrE - BSSN:** Penandatanganan digital dokumen kedinasan (Notulen, Daftar Hadir, Dokumentasi) secara individu maupun serentak (*Batch Sign All*), dilengkapi verifikasi keaslian via QR Code publik.
- **Notulen Otomatis (Gemini AI):** Format otomatis catatan rapat menjadi notulen standar kedinasan menggunakan Google Gemini AI.
- **Ekspor Dokumen & Arsip:** Unduh dokumen berformat PDF resmi berkop dinas serta ekspor seluruh galeri dokumentasi foto dalam satu arsip ZIP.
- **Sinkronisasi SIMPEG:** Pembaruan dan sinkronisasi berkala master data OPD dan pegawai langsung dari server kepegawaian.
- **Audit Log Keamanan:** Monitoring rekam jejak aktivitas penting (*Login*, *Rapat*, *TTE*) dengan mekanisme pembersihan otomatis (*auto-prune* 90 hari).

---

## Tumpukan Teknologi

- **Backend:** Laravel 11/12, PHP 8.2+
- **Frontend:** Laravel Livewire 3 (Volt), Alpine.js, Tailwind CSS
- **Basis Data:** MySQL / MariaDB / SQLite
- **Otorisasi:** Spatie Laravel Permission
- **Integrasi API:** API SIMPEG & PPPK Paruh Waktu BKPSDMA Sinjai, API BSrE BSSN, Google Gemini API

---

## Instalasi Lokal

```bash
# 1. Klon repositori & pasang dependensi
git clone https://github.com/peraja/kehadiran.git
cd kehadiran
composer install
npm install

# 2. Konfigurasi environment
cp .env.example .env
php artisan key:generate

# 3. Migrasi database & storage link
php artisan migrate --seed
php artisan storage:link

# 4. Jalankan aplikasi
npm run dev
php artisan serve
```

---

## Panduan Deployment (cPanel / Production)

### 1. Persiapan Build Lokal
```bash
npm run build
composer install --no-dev --optimize-autoloader
```

### 2. Konfigurasi Lingkungan (`.env`)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://rapat.sinjaikab.go.id

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### 3. Eksekusi di Server Production
```bash
# Migrasi database
php artisan migrate --force

# Symlink storage
php artisan storage:link

# Cache konfigurasi, rute, dan view untuk performa maksimal
php artisan optimize
php artisan view:cache
php artisan event:cache
```

### 4. Cron Job (Pembersihan Log Otomatis)
Tambahkan entri berikut pada menu **Cron Jobs** di cPanel:
```text
* * * * * cd /home/rapat/rapat && php artisan schedule:run >> /dev/null 2>&1
```

---

## Lisensi
Hak Cipta &copy; Pemerintah Kabupaten Sinjai.
