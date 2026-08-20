# Panduan Deployment Live Domain: `kehadiran.sinjaikab.go.id`

Dokumen ini berisi panduan langkah demi langkah untuk melakukan *deployment* (memasang dan mengaktifkan) aplikasi **Sistem Daftar Hadir Kegiatan Pemkab Sinjai** dari server lokal ke domain resmi `kehadiran.sinjaikab.go.id`.

---

## 📋 Langkah-Langkah Deployment Lengkap

### LANGKAH 1: Setting DNS Record Subdomain `kehadiran`

Buka Panel Pengaturan DNS Management domain `sinjaikab.go.id` (cPanel / Cloudflare / DNS Server Diskominfo), lalu tambahkan **A Record** baru:

- **Type**: `A`
- **Name / Host**: `kehadiran` (sehingga menjadi `kehadiran.sinjaikab.go.id`)
- **IPv4 Address**: `[IP_PUBLIC_SERVER_ANDA]` (misal: `103.xxx.xxx.xxx`)
- **TTL**: `Auto` / `300`

---

### LANGKAH 2: Pindahkan File Proyek ke Server

Upload seluruh folder `D:\Mastah\KEHADIRAN` ke server target (misal menggunakan SFTP / SCP / FileZilla / Zip):
- **Linux Server**: `/var/www/kehadiran`
- **Windows Server**: `C:\apps\kehadiran`

*Catatan: Folder `node_modules` tidak perlu diikutkan saat upload.*

---

### LANGKAH 3: Instalasi & Jalankan Server (Node.js & PM2)

Buka terminal SSH / Command Prompt server, masuk ke folder proyek, lalu jalankan:

```bash
# 1. Masuk folder proyek
cd /var/www/kehadiran

# 2. Install dependensi produksi
npm install --production

# 3. Install PM2 (Process Manager agar aplikasi berjalan 24/7 di background)
npm install -g pm2

# 4. Jalankan aplikasi dengan PM2
pm2 start server.js --name "kehadiran-sinjai"

# 5. Simpan sesi PM2 agar otomatis jalan saat server reboot
pm2 save
pm2 startup
```

---

### LANGKAH 4: Konfigurasi Reverse Proxy (Nginx)

#### Jika Menggunakan Server Linux (Ubuntu/Debian) + Nginx:

1. Buat file konfigurasi Nginx baru:
   ```bash
   sudo nano /etc/nginx/sites-available/kehadiran.sinjaikab.go.id
   ```

2. Tempelkan kode konfigurasi berikut:
   ```nginx
   server {
       listen 80;
       server_name kehadiran.sinjaikab.go.id;

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

3. Aktifkan konfigurasi & restart Nginx:
   ```bash
   sudo ln -s /etc/nginx/sites-available/kehadiran.sinjaikab.go.id /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl restart nginx
   ```

---

### LANGKAH 5: Memasang Sertifikat SSL Gratis (HTTPS Certbot)

Jalankan Certbot untuk mengaktifkan HTTPS gratis dari Let's Encrypt:

```bash
sudo apt update
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d kehadiran.sinjaikab.go.id
```

---

### LANGKAH 6: Pengujian Aplikasi Live

Setelah SSL aktif, buka browser dan verifikasi seluruh fitur:

1. **Form Kehadiran Publik**: `https://kehadiran.sinjaikab.go.id`
2. **Login Admin Tersembunyi**: `https://kehadiran.sinjaikab.go.id/attamaki`
   - **Username**: `admin`
   - **Password**: `admin123` *(Segera ubah password di menu Keamanan Admin).*

---

## ⚙️ Perintah Perawatan Server (Maintenance)

- **Melihat Status Server**: `pm2 status`
- **Melihat Log Server**: `pm2 logs kehadiran-sinjai`
- **Restart Aplikasi**: `pm2 restart kehadiran-sinjai`
- **Backup Database**: Cukup copy file `database/kehadiran.db` secara berkala.
