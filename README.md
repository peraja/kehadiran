# eRapat — Pemkab Sinjai

Sistem administrasi rapat terpadu Pemerintah Kabupaten Sinjai untuk penjadwalan agenda, presensi QR & tanda tangan digital, notulen cerdas Gemini AI, ekspor PDF kedinasan, dan Tanda Tangan Elektronik (TTE) BSrE - BSSN.

---

## Fitur Utama

- **Login SIMPEG & Multirole:** Integrasi akun kepegawaian ASN & pemindahan peran (*Super Admin*, *Admin OPD*, *Pimpinan*, *Pegawai*).
- **Manajemen Rapat:** Penjadwalan, delegasi OPD pelaksana, dan penunjukan pejabat penandatangan.
- **Presensi Mandiri:** Scan QR Code / input NIP (PNS, PPPK Paruh Waktu, & Tamu Eksternal) + TTD digital canvas.
- **TTE BSrE - BSSN:** Sertifikasi digital dokumen (Notulen, Presensi, Dokumentasi) & *Batch Sign All*.
- **Notulen Otomatis AI:** Format notulen standar kedinasan dengan Google Gemini AI.
- **Ekspor Dokumen:** Cetak PDF resmi berkop dinas (Notulen, Presensi, Dokumentasi) & unduh bundel dokumen ZIP.
- **Audit Trail:** Pemantauan aktivitas keamanan dengan *auto-prune* 90 hari.

---

## Teknologi

- **Backend:** Laravel 11/12, PHP 8.2+, MySQL / MariaDB
- **Frontend:** Livewire 3 (Volt), Alpine.js, Tailwind CSS
- **Integrasi:** API SIMPEG Sinjai, API BSrE BSSN, Google Gemini API

---

## Instalasi Lokal

```bash
git clone https://github.com/peraja/kehadiran.git
cd kehadiran
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed && php artisan storage:link
npm run dev
php artisan serve
```

---

## Deployment Produksi (Server)

### 1. Build Lokal
```bash
npm run build
composer install --no-dev --optimize-autoloader
```

### 2. Eksekusi di Server (`/home/rapat/rapat`)
```bash
git pull origin main
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan view:cache
php artisan event:cache
```

### 3. Cron Job (cPanel)
```text
* * * * * cd /home/rapat/rapat && php artisan schedule:run >> /dev/null 2>&1
```

---

## Lisensi
Hak Cipta &copy; Pemerintah Kabupaten Sinjai.

