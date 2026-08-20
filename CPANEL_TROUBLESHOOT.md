# Panduan Troubleshooting Error 500 — Aplikasi Kehadiran Pemkab Sinjai

Error 500 setelah upload ZIP hampir selalu berarti file `.htaccess` memuat direktif yang hosting Anda larang.

---

## Langkah Diagnosis Bertahap

### LANGKAH 1: Uji tanpa `.htaccess` sama sekali

1. Login cPanel File Manager → masuk ke folder deployment
2. **Rename `.htaccess` menjadi `.htaccess-BACKUP`** (sementara nonaktifkan)
3. Refresh browser, akses: `https://kehadiran.sinjaikab.go.id`

**Hasil:**
- ✅ **Portal muncul** → `.htaccess` adalah penyebab. Lanjut LANGKAH 2.
- ❌ **Masih 500** → masalah BUKAN di `.htaccess`. Lihat bagian "Jika tetap 500 tanpa .htaccess" di bawah.

---

### LANGKAH 2: Coba `.htaccess` paling minim

File `KEHADIRAN/.htaccess-1-barebones` hanya berisi `DirectoryIndex index.php`.

1. Rename `.htaccess-BACKUP` jadi `.htaccess-LAMA`
2. **Rename `.htaccess-1-barebones` jadi `.htaccess`**
3. Refresh browser

**Pada tahap ini URL bersih BELUM aktif.** Akses langsung pakai nama file:
- `/` → portal
- `/login.php` → login admin
- `/admin.php` → panel admin
- `/meeting.php?code=Mjk4NDM` → form kehadiran
- `/api.php?action=diag` → diagnostik

**Hasil:**
- ✅ **Semua halaman OK** → `.htaccess` asli ada direktif terlarang. Lanjut LANGKAH 3.
- ❌ **Masih 500** → lihat "Jika tetap 500 tanpa .htaccess" di bawah.

---

### LANGKAH 3: Aktifkan routing, tanpa blokir file

File `KEHADIRAN/.htaccess-2-routing` punya routing tapi tidak ada blok `FilesMatch`.

1. Rename `.htaccess` jadi `.htaccess-1-OK`
2. **Rename `.htaccess-2-routing` jadi `.htaccess`**
3. Refresh browser

**Sekarang URL bersih sudah aktif:**
- `/attamaki` → login admin
- `/meeting/Mjk4NDM` → form kehadiran
- `/api/diag` → diagnostik

**Hasil:**
- ✅ **Semua OK** → blok `FilesMatch` di `.htaccess` asli yang bermasalah. Pakai `.htaccess-2-routing` sebagai final (tidak ada perlindungan file, tapi aplikasi tetap jalan).
- ❌ **Masih 500** → `mod_rewrite` dilarang. Pakai `.htaccess-1-barebones` dan akses langsung pakai nama file (`.php?action=...`).

---

### LANGKAH 4: Coba `.htaccess` utama (aman)

File `.htaccess` utama sudah dihapus direktif berisiko (`SecRuleEngine`, `php_value`).

1. Rename `.htaccess` (routing) jadi `.htaccess-2-OK`
2. **Rename `.htaccess-LAMA` jadi `.htaccess`** (atau upload ulang dari ZIP)
3. Refresh browser

**Hasil:**
- ✅ **Semua OK** → hosting Anda ketat tapi `.htaccess` aman sudah lolos. Selesai.
- ❌ **Masih 500** → blok `FilesMatch` dilarang. Pakai `.htaccess-2-routing` dari LANGKAH 3.

---

## Jika tetap 500 tanpa .htaccess

Penyebab bukan Apache config, tapi **PHP atau permission**.

### A. Cek Error Log cPanel

1. File Manager → root folder → cari file **`error_log`** (tanpa extension)
2. Klik kanan → View
3. Scroll ke bawah, cari error terbaru (punya timestamp)

**Error umum:**

#### 1. `Permission denied: database/kehadiran.db`
Folder `database/` tidak writable.

**Fix:**
```bash
chmod 755 database
```
Atau lewat File Manager: klik kanan folder `database` → Permissions → set `755`.

#### 2. `Call to undefined function mb_convert_encoding`
Extension PHP `mbstring` belum aktif.

**Fix:** cPanel → **Select PHP Version** → centang `mbstring` → Save.

#### 3. `session_start(): open(/tmp/sess_...) failed`
Session path tidak writable atau tidak ada.

**Fix:** Tambahkan di awal `api.php` (sebelum `session_start();`):
```php
ini_set('session.save_path', __DIR__ . '/database');
```
Lalu buat folder `database/` writable (chmod 755).

#### 4. `Fatal error: Allowed memory size exhausted`
Memory limit terlalu kecil.

**Fix:** Buat file `php.ini` di root folder:
```ini
memory_limit = 256M
upload_max_filesize = 12M
post_max_size = 12M
```

### B. Cek PHP Version

Aplikasi butuh **PHP 7.4 atau lebih baru**.

1. cPanel → **Select PHP Version**
2. Pastikan minimal PHP 7.4, disarankan 8.0 atau 8.1
3. Centang extension: `pdo`, `pdo_sqlite`, `mbstring`, `json`, `session`

### C. Pastikan file utama tidak korup

Upload ulang file `.php` satu per satu dari ZIP lokal.

---

## Diagnosis Cepat Lewat Endpoint

Jika portal bisa diakses (tidak 500), coba:

### 1. Diagnostik API
```
https://kehadiran.sinjaikab.go.id/api.php?action=diag
```

Harus return JSON murni. Cek:
- `"php_version"` → minimal 7.4
- `"pdo_sqlite": true` → SQLite aktif
- `"db_connected": true` → database OK
- `"db_file_writable": true` → folder writable
- `"session_works": true` → session OK
- `"allow_url_fopen": true` → kalau false, agencies list tidak jalan (tapi aplikasi tetap OK)

### 2. Portal Events
```
https://kehadiran.sinjaikab.go.id/api.php?action=events_portal
```

Harus return JSON dengan 4 event sampel.

### 3. Login Admin
```
https://kehadiran.sinjaikab.go.id/attamaki
```

Username: `admin`  
Password: `admin123`

Jika login gagal padahal credential benar, hapus `database/kehadiran.db` dan refresh — database baru akan dibuat otomatis.

---

## Kontak Terakhir

Jika semua langkah sudah dicoba dan masih error:

1. Screenshot **error log cPanel** (File Manager → `error_log`)
2. Screenshot hasil akses `/api.php?action=diag`
3. Screenshot cPanel **Select PHP Version** (versi + extension aktif)
4. Kirim ke tim IT atau hosting support dengan subject: "Error 500 aplikasi PHP native + SQLite"

---

**Catatan:** Error 500 di cPanel 99% karena:
- `.htaccess` punya direktif terlarang (`SecRuleEngine`, `php_value`)
- Folder `database/` tidak writable (chmod)
- Extension PHP (`pdo_sqlite`, `mbstring`) belum aktif
- PHP version terlalu lama (<7.4)

Langkah diagnosis di atas akan menemukan penyebab dalam 5 menit.
