# Sistem Daftar Hadir Kegiatan Pemerintah Kabupaten Sinjai
### Target Domain: `kehadiran.sinjaikab.go.id`

Aplikasi Web Resmi Sistem Daftar Hadir Kegiatan Pemerintah Kabupaten Sinjai (Sulawesi Selatan). Aplikasi ini dikembangkan untuk mencatat kehadiran peserta rapat/kegiatan secara digital, presisi, cepat, dan terintegrasi dengan Survei Kepuasan Masyarakat (SKM).

---

## 🌟 Fitur Utama

### 1. Form Kehadiran Publik (Presisi Sesuai Spesifikasi)
- **Header Biru/Cyan Signature Pemkab Sinjai (`#029fe4`)**:
  - Logo Resmi Pemkab Sinjai + Teks Resmi.
  - Judul "Daftar Kehadiran" + Subtitle "Sistem Kehadiran Kegiatan Pemkab Sinjai".
- **Pilihan Kegiatan**:
  - Dropdown otomatis untuk memilih kegiatan aktif.
  - Tombol modal *"Klik disini untuk melihat rapat/kegiatan lain"* (lengkap dengan filter pencarian).
- **Section Identitas Peserta (Card Krem Muda `#fdfbf7`)**:
  - Radio Jenis Peserta: `[Pegawai Pemkab]` / `[Eksternal]`.
  - Radio Tipe Peserta: `[Narasumber]` / `[Peserta]`.
  - Input Nama Lengkap, Jabatan, dan Instansi / Organisasi.
  - Section Opsional: NIP/NIK, No. Telepon, dan Email.
- **Section Tanda Tangan Digital**:
  - Canvas interaktif dengan fitur `Undo last stroke` (Urungkan) dan `Bersihkan`.
  - Checkbox konfirmasi keabsahan tanda tangan.
  - Verifikasi Captcha Matematika interaktif.
- **Alur Konfirmasi & SKM (Survei Kepuasan Masyarakat)**:
  - **Modal Konfirmasi**: Popup konfirmasi sebelum pengiriman data dengan ikon `?` dan judul kegiatan.
  - **Modal SKM**: Tampil otomatis setelah data tersimpan, menyajikan **QR Code otomatis** & tautan URL SKM.

### 2. Panel Administrator (URL Tersembunyi)
- **URL Akses Admin**: `http://localhost:3000/attamaki` (atau `/panel-admin`)
- **Username Default**: `admin`
- **Password Default**: `admin123`
- **Fitur Admin**:
  - **Dashboard Statistik**: Total kegiatan, kegiatan aktif, total presensi, dan presensi hari ini.
  - **CRUD Kegiatan**: Tambah kegiatan baru, Edit data kegiatan, Hapus kegiatan, Sakelar Aktif/Nonaktif.
  - **Manajemen & Log Presensi**: Filter berdasarkan kegiatan, jenis/tipe peserta, pencarian kata kunci, serta Modal Preview Tanda Tangan Digital.
  - **Export Data**: Ekspor laporan kehadiran ke format **Excel (.xlsx)** & **CSV** secara instant.
  - **Pengaturan SKM**: Ubah URL Survei Kepuasan Masyarakat kapan saja (QR Code publik terupdate otomatis).
  - **Manajemen Keamanan**: Pengubahan Password Administrator.

---

## 🛠️ Stack Teknologi

- **Backend**: Node.js + Express.js
- **Database**: SQLite 3 (`better-sqlite3`) — File DB: `database/kehadiran.db`
- **Frontend**: HTML5, Tailwind CSS, Font Plus Jakarta Sans, FontAwesome 6
- **Pustaka Pendukung**:
  - `Signature Canvas HTML5` (Digital Signature)
  - `QRCode.js` (Dynamic QR Code Generator)
  - `SheetJS (XLSX)` (Ekspor Excel .xlsx & CSV)
  - `bcryptjs` & `express-session` (Autentikasi & Keamanan Admin)

---

## 🚀 Instruksi Cara Menjalankan

### 1. Instalasi Dependensi
Buka terminal / Command Prompt di folder proyek (`D:\Mastah\KEHADIRAN`), lalu jalankan:

```bash
npm install
```

### 2. Menjalankan Server
Jalankan perintah berikut untuk mengaktifkan server Node.js:

```bash
npm start
```

Atau untuk mode pengembangan (auto-reload):

```bash
npm run dev
```

Server akan berjalan pada:
- **Tampilan Publik**: [http://localhost:3000](http://localhost:3000)
- **Login Admin Tersembunyi**: [http://localhost:3000/attamaki](http://localhost:3000/attamaki)

---

## 🌐 Panduan Deployment Domain `kehadiran.sinjaikab.go.id`

Untuk memasang aplikasi ini di server produksi Pemkab Sinjai dengan HTTPS & Reverse Proxy Nginx, gunakan konfigurasi Nginx berikut:

```nginx
server {
    listen 80;
    server_name kehadiran.sinjaikab.go.id;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name kehadiran.sinjaikab.go.id;

    ssl_certificate /etc/letsencrypt/live/kehadiran.sinjaikab.go.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/kehadiran.sinjaikab.go.id/privkey.pem;

    client_max_body_size 10M;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Gunakan **PM2** untuk menjaga aplikasi tetap running 24/7 di background:

```bash
npm install -g pm2
pm2 start server.js --name "kehadiran-sinjai"
pm2 save
```

---

## 📂 Struktur Direktori Proyek

```
D:\Mastah\KEHADIRAN\
├── database/
│   ├── db.js                 # Inisialisasi SQLite database, migrasi tabel & seed data
│   └── kehadiran.db          # File Database SQLite (otomatis dibuat)
├── public/
│   ├── assets/
│   │   └── logo-sinjai.svg   # Logo Resmi Pemkab Sinjai Vector SVG
│   ├── index.html            # Halaman Form Kehadiran Publik + Modal Konfirmasi + Modal SKM
│   ├── login.html            # Halaman Login Admin Tersembunyi (/attamaki)
│   └── admin.html            # Panel Dashboard Admin (/panel-admin)
├── package.json              # Konfigurasi dependensi Node.js & npm scripts
├── server.js                 # Server Express & Seluruh REST API Endpoints
└── README.md                 # Dokumentasi Lengkap Aplikasi
```

---
*© 2026 Pemerintah Kabupaten Sinjai — Diskominfo dan Persandian Kab. Sinjai*
