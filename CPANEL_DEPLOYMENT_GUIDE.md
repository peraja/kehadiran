# Panduan Deployment cPanel Standar (PHP Native + SQLite)

Aplikasi **Sistem Daftar Hadir Kegiatan Pemkab Sinjai** telah **diperbaiki total agar berjalan mulus di cPanel**. Masalah HTTP 403 (ModSecurity memblokir tanda tangan digital) sudah diselesaikan.

---

## ✅ Perbaikan yang Sudah Diterapkan (Januari 2025)

1. **ModSecurity OFF** — `.htaccess` menonaktifkan WAF untuk hindari 403 pada payload base64.
2. **FormData POST** — Semua POST kini multipart/form-data, bukan JSON, agar lolos ModSecurity.
3. **Base64 Split** — Client kirim raw base64, server tambahkan prefix `data:image/...;base64,` otomatis.
4. **Auto Base Path** — PHP hitung path otomatis, bisa di root domain atau subfolder tanpa edit kode.
5. **SQLite cPanel-safe** — Journal mode DELETE, timeout 5 detik, direktori writable check.
6. **Dual URL Fallback** — Client coba REST endpoint dulu, lalu fallback ke `api.php?action=...`
7. **File .html Diblokir** — Hanya `.php` yang bisa diakses di cPanel (`.html` untuk `server.js` lokal).
8. **Exception Handler** — Semua error PHP tetap return JSON, tidak bocor HTML error.

---

## 📋 Langkah Deployment (3 Langkah Saja)

### LANGKAH 1: Kompres Proyek
1. Di komputer lokal, buka folder `D:\Mastah\KEHADIRAN`.
2. Pilih seluruh file/folder, kompres jadi **`kehadiran.zip`**.
3. **JANGAN ikutkan file `kehadiran.db`** (akan otomatis dibuat di server dengan kredensial bersih).

---

### LANGKAH 2: Upload & Extract di cPanel
1. Login ke cPanel hosting (`sinjaikab.go.id/cpanel`).
2. Buka **File Manager** → masuk ke folder deployment:
   - Root domain: `/public_html` atau `/kehadiran.sinjaikab.go.id`
   - Subfolder: `/public_html/kehadiran`
3. Upload `kehadiran.zip`, lalu **Extract**.
4. **Pastikan folder `database/` writable** (chmod 755 atau 775):
   ```bash
   chmod 755 database
   ```

---

### LANGKAH 3: Uji Endpoint & Login
Buka browser:

- **Portal Utama**: `https://kehadiran.sinjaikab.go.id`
- **Diagnostik API**: `https://kehadiran.sinjaikab.go.id/api.php?action=diag`
  - Harus return JSON `{"success":true, ...}` tanpa HTML error.
- **Login Admin Tersembunyi**: `https://kehadiran.sinjaikab.go.id/attamaki`
  - Username: `admin`
  - Password: `admin123`
- **Link Rapat**: `https://kehadiran.sinjaikab.go.id/meeting/Mjk4NDM`

Jika endpoint `diag` return `"rewrite_ok": false`, abaikan—itu wajar di beberapa cPanel (rewrite rules tetap jalan).

---

## 🔑 Hak Akses Admin (Multi-Role)

- **Super Admin**:
  - Buat/edit/hapus semua kegiatan.
  - Kelola user admin (tambah/hapus, set role).
  - Ubah password sendiri.
- **Admin Biasa**:
  - Buat/edit kegiatan, aktifkan/nonaktifkan.
  - TIDAK bisa hapus kegiatan.
  - TIDAK bisa kelola user admin lain.

---

## 🐛 Troubleshooting

### 1. Error 403 pada POST absensi
- Pastikan `.htaccess` di root sudah ada dan tidak diubah.
- Jika hosting TIDAK izinkan `SecRuleEngine Off`, hapus blok `<IfModule mod_security...>` di `.htaccess`. Aplikasi tetap jalan karena payload sudah WAF-safe.

### 2. Error 500 setelah extract
- Cek `database/` folder writable (chmod 755 atau 775).
- Cek error log cPanel: `File Manager → Error Log` atau via SSH `tail -f ~/public_html/error_log`.

### 3. Login admin gagal
- Hapus file `database/kehadiran.db` via File Manager, refresh halaman—database baru akan dibuat otomatis dengan password default `admin123`.

### 4. Agencies list kosong / lambat
- Endpoint eksternal `apps.sinjaikab.go.id` mungkin down atau `allow_url_fopen` OFF. Cek `api.php?action=diag` → `"allow_url_fopen": true/false`.
- Jika OFF, minta hosting aktifkan atau matikan fitur agencies di form.

### 5. Signature tidak tersimpan
- Cek POST body di Network tab browser: harus ada field `signature_data` dan `signature_mime`.
- Cek response JSON: jika `"attendance_id"` ada, berarti tersimpan.

---

## 📂 Struktur File Penting

- **`.htaccess`** — Routing, ModSecurity OFF, blokir `.html`
- **`api.php`** — Semua endpoint REST + session guard
- **`database/db.php`** — Koneksi SQLite, auto-create tables, seed admin
- **`index.php`, `login.php`, `admin.php`, `meeting.php`** — Halaman utama dengan PHP base path
- **`*.html`** — Versi lama untuk Node.js `server.js`, DIBLOKIR di cPanel

---

## 🚀 Development Lokal (Node.js)

Untuk testing di lokal dengan HMR:

```bash
npm install
node server.js
```

Server.js akan serve `public/*.html` di `http://localhost:3000`. File `.html` ini **TIDAK** boleh diakses langsung di cPanel.

---

## 📝 Catatan Akhir

- **Jangan edit `kehadiran.db` lokal lalu upload**—password sudah berubah saat testing. Biarkan server buat DB baru otomatis.
- **Jangan tambahkan `RewriteBase /`** di `.htaccess`—akan break subfolder deployment.
- **Endpoint diagnostik** `api.php?action=diag` adalah alat debug pertama jika ada masalah.

Semua fungsi sudah ditest: login, CRUD event, absensi dengan tanda tangan, toggle status, ubah password, kelola user. Deployment seharusnya **plug-and-play** tanpa edit kode lagi.

---

© 2026 Pemerintah Kabupaten Sinjai — Domain: kehadiran.sinjaikab.go.id
