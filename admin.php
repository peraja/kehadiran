<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Base path otomatis: bekerja di root domain maupun subfolder cPanel.
$__base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$__base = ($__base === '/' || $__base === '.' || $__base === '\\') ? '' : rtrim($__base, '/');
// URL root aplikasi, selalu berakhiran "/", untuk aset & tautan.
$__rootv = ($__base === '' ? '/' : $__base . '/');

if (!isset($_SESSION['admin'])) { header('Location: ' . $__rootv . 'attamaki'); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin Kehadiran | Pemkab Sinjai</title>

  <!-- Favicon Logo Pemkab Sinjai -->
  <link rel="icon" type="image/png" href="<?php echo $__rootv; ?>assets/sinjai2.png">
  <link rel="shortcut icon" type="image/png" href="<?php echo $__rootv; ?>assets/sinjai2.png">

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            sinjai: {
              cyan: '#029fe4',
              hover: '#0084c7',
              dark: '#005b8e'
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
          }
        }
      }
    }
  </script>

  <!-- SheetJS (XLSX) for Exporting to Excel -->
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  <!-- QRCode.js Library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <!-- html2pdf.js for Official PDF Generation -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

  <style>
    body {
      background-color: #f1f5f9;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .modal-backdrop {
      background-color: rgba(15, 23, 42, 0.65);
      backdrop-filter: blur(4px);
    }
    .print-pdf-table {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, sans-serif;
      font-size: 11px;
    }
    .print-pdf-table th, .print-pdf-table td {
      border: 1px solid #000000;
      padding: 6px 8px;
      vertical-align: middle;
    }
    .print-pdf-table th {
      background-color: #f3f4f6;
      font-weight: bold;
      text-align: center;
      text-transform: uppercase;
    }
  </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

  <!-- TOP NAV BAR -->
  <nav class="bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
      
      <!-- Brand -->
      <div class="flex items-center space-x-3">
        <img src="<?php echo $__rootv; ?>assets/sinjai2.png" onerror="if(this.src.includes('sinjai2.png')){this.src='<?php echo $__rootv; ?>assets/sinjai.png';}else{this.src='<?php echo $__rootv; ?>sinjai2.png';}" alt="Logo Pemkab Sinjai" class="h-9 w-auto object-contain">
        <div>
          <span class="font-extrabold text-sm sm:text-base tracking-tight text-white block">PEMKAB SINJAI</span>
          <span class="text-[10px] text-sinjai-cyan font-bold uppercase tracking-wider block">Panel Admin Kehadiran</span>
        </div>
      </div>

      <!-- User Admin Profile & Actions -->
      <div class="flex items-center space-x-4">
        <a href="./" target="_blank" class="hidden sm:flex items-center space-x-1.5 text-xs text-slate-300 hover:text-sinjai-cyan font-medium transition-colors">
          <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
          <span>Lihat Portal Utama</span>
        </a>
        <div class="h-4 w-px bg-slate-700 hidden sm:block"></div>
        <div class="flex items-center space-x-2 text-xs font-semibold text-slate-200">
          <i class="fa-solid fa-user-gear text-sinjai-cyan"></i>
          <span id="admin-display-name">Administrator</span>
          <span id="admin-role-badge" class="px-2 py-0.5 rounded text-[10px] font-black uppercase"></span>
        </div>
        <button onclick="handleLogout()" class="px-3 py-1.5 bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 rounded-lg text-xs font-bold transition-all flex items-center space-x-1 cursor-pointer">
          <i class="fa-solid fa-power-off"></i>
          <span>Keluar</span>
        </button>
      </div>

    </div>
  </nav>

  <!-- MAIN BODY -->
  <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 py-8 flex-grow space-y-8">
    
    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-xs font-bold text-slate-400 uppercase">Total Kegiatan</p>
          <h3 id="stat-total-events" class="text-2xl font-black text-slate-800 mt-1">0</h3>
        </div>
        <div class="w-12 h-12 bg-blue-50 text-sinjai-cyan rounded-xl flex items-center justify-center text-xl">
          <i class="fa-solid fa-calendar-days"></i>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-xs font-bold text-slate-400 uppercase">Kegiatan Aktif</p>
          <h3 id="stat-active-events" class="text-2xl font-black text-emerald-600 mt-1">0</h3>
        </div>
        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl">
          <i class="fa-solid fa-circle-check"></i>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-xs font-bold text-slate-400 uppercase">Total Hadir</p>
          <h3 id="stat-total-attendances" class="text-2xl font-black text-slate-800 mt-1">0</h3>
        </div>
        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-xl">
          <i class="fa-solid fa-users"></i>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-xs font-bold text-slate-400 uppercase">Hadir Hari Ini</p>
          <h3 id="stat-today-attendances" class="text-2xl font-black text-sinjai-cyan mt-1">0</h3>
        </div>
        <div class="w-12 h-12 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center text-xl">
          <i class="fa-solid fa-user-clock"></i>
        </div>
      </div>

    </div>

    <!-- TABS NAVIGATION -->
    <div class="flex border-b border-slate-200 space-x-2 overflow-x-auto">
      <button onclick="switchTab('attendances')" id="tab-btn-attendances" 
              class="px-5 py-3 font-extrabold text-sm border-b-2 border-sinjai-cyan text-sinjai-cyan flex items-center space-x-2 transition-all whitespace-nowrap">
        <i class="fa-solid fa-clipboard-user"></i>
        <span>Data Kehadiran Peserta</span>
      </button>
      
      <button onclick="switchTab('events')" id="tab-btn-events" 
              class="px-5 py-3 font-bold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center space-x-2 transition-all whitespace-nowrap">
        <i class="fa-solid fa-folder-plus"></i>
        <span>Kelola Kegiatan</span>
      </button>

      <button onclick="switchTab('skm')" id="tab-btn-skm" 
              class="px-5 py-3 font-bold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center space-x-2 transition-all whitespace-nowrap">
        <i class="fa-solid fa-qrcode"></i>
        <span>Pengaturan SKM</span>
      </button>

      <!-- TAB KHUSUS SUPER ADMIN -->
      <button onclick="switchTab('users')" id="tab-btn-users" 
              class="hidden px-5 py-3 font-bold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center space-x-2 transition-all whitespace-nowrap">
        <i class="fa-solid fa-users-gear"></i>
        <span>Kelola Akun Admin</span>
      </button>

      <button onclick="switchTab('security')" id="tab-btn-security" 
              class="px-5 py-3 font-bold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center space-x-2 transition-all whitespace-nowrap">
        <i class="fa-solid fa-shield-halved"></i>
        <span>Keamanan Password</span>
      </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: DATA KEHADIRAN PESERTA & EXPORT -->
    <!-- ========================================================================= -->
    <div id="tab-content-attendances" class="space-y-6">
      
      <!-- Filter Bar & Export Buttons -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <h3 class="text-base font-extrabold text-slate-800 flex items-center">
            <i class="fa-solid fa-filter text-sinjai-cyan mr-2"></i> Filter & Export Data Kehadiran
          </h3>

          <!-- TOMBOL EXPORT: CETAK/PREVIEW & PDF, EXCEL, CSV -->
          <div class="flex flex-wrap items-center gap-2">
            <button onclick="openPdfModal()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold rounded-xl shadow-xs transition-all flex items-center space-x-1.5 cursor-pointer">
              <i class="fa-solid fa-print text-sm"></i>
              <span>Cetak / Export PDF</span>
            </button>
            <button onclick="exportToExcel()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow-xs transition-all flex items-center space-x-1.5 cursor-pointer">
              <i class="fa-solid fa-file-excel text-sm"></i>
              <span>Export Excel (.xlsx)</span>
            </button>
            <button onclick="exportToCsv()" class="px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs font-extrabold rounded-xl shadow-xs transition-all flex items-center space-x-1.5 cursor-pointer">
              <i class="fa-solid fa-file-csv text-sm"></i>
              <span>Export CSV</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Pilih Kegiatan:</label>
            <select id="filter-event-id" onchange="loadAttendances()" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sinjai-cyan">
              <option value="all">-- Semua Kegiatan --</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Jenis Peserta:</label>
            <select id="filter-participant-type" onchange="loadAttendances()" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sinjai-cyan">
              <option value="all">-- Semua Jenis --</option>
              <option value="Pegawai Pemkab">Pegawai Pemkab</option>
              <option value="Eksternal">Eksternal</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Tipe Peserta:</label>
            <select id="filter-role-type" onchange="loadAttendances()" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sinjai-cyan">
              <option value="all">-- Semua Tipe --</option>
              <option value="Narasumber">Narasumber</option>
              <option value="Peserta">Peserta</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Cari Kata Kunci:</label>
            <div class="relative">
              <input type="text" id="filter-search" onkeyup="debounceLoadAttendances()" placeholder="Nama / NIP / Instansi..." 
                     class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-sinjai-cyan">
              <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-xs text-slate-400"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Attendance Table -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-900 text-slate-200 text-xs uppercase font-extrabold tracking-wider border-b border-slate-800">
                <th class="p-3.5 text-center">No</th>
                <th class="p-3.5">Kegiatan</th>
                <th class="p-3.5">Nama & Identitas</th>
                <th class="p-3.5">Jabatan & Instansi</th>
                <th class="p-3.5">Kontak</th>
                <th class="p-3.5 text-center">Tanda Tangan</th>
                <th class="p-3.5 text-center">Waktu Hadir</th>
              </tr>
            </thead>
            <tbody id="attendance-table-body" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
              <!-- Dynamic Rows -->
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: KELOLA KEGIATAN (CRUD) -->
    <!-- ========================================================================= -->
    <div id="tab-content-events" class="hidden space-y-6">
      
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div>
          <h3 class="text-base font-extrabold text-slate-800">Daftar Kegiatan Pemkab Sinjai</h3>
          <p class="text-xs text-slate-500">Tambah, Edit, dan Atur Status Sakelar Aktif/Nonaktif Kegiatan.</p>
        </div>
        <button onclick="openCreateEventModal()" class="px-4 py-2.5 bg-[#029fe4] hover:bg-[#0084c7] text-white text-xs font-extrabold rounded-xl shadow-md transition-all flex items-center space-x-1.5 cursor-pointer">
          <i class="fa-solid fa-plus"></i>
          <span>Tambah Kegiatan Baru</span>
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-900 text-slate-200 text-xs uppercase font-extrabold tracking-wider border-b border-slate-800">
                <th class="p-3.5 text-center">ID / Kode Link</th>
                <th class="p-3.5">Nama Kegiatan</th>
                <th class="p-3.5">Waktu & Lokasi</th>
                <th class="p-3.5 text-center">Total Hadir</th>
                <th class="p-3.5 text-center">Status Sakelar</th>
                <th class="p-3.5 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="events-table-body" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
              <!-- Dynamic Rows -->
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: PENGATURAN SKM -->
    <!-- ========================================================================= -->
    <div id="tab-content-skm" class="hidden space-y-6">
      <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm max-w-2xl mx-auto space-y-6">
        
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-lg font-extrabold text-slate-800 flex items-center">
            <i class="fa-solid fa-qrcode text-sinjai-cyan mr-2"></i> Pengaturan Survei Kepuasan Masyarakat (SKM)
          </h3>
          <p class="text-xs text-slate-500 mt-1">Ubah URL SKM kapan saja. QR Code pada popup publik akan diperbarui secara otomatis.</p>
        </div>

        <form onsubmit="handleUpdateSkm(event)" class="space-y-4">
          <div>
            <label for="skm-input-url" class="block text-xs font-bold text-slate-700 uppercase mb-2">URL Tautan SKM *</label>
            <input type="url" id="skm-input-url" required placeholder="https://kehadiran.sinjaikab.go.id/skm"
                   class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-sinjai-cyan">
          </div>

          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-center space-y-3">
            <span class="text-xs font-bold text-slate-500 block">Preview QR Code Otomatis (Warna Hitam #000000):</span>
            <div class="flex justify-center">
              <div id="admin-skm-qrcode" class="p-3 bg-white border rounded-xl shadow-xs inline-block"></div>
            </div>
          </div>

          <button type="submit" id="btn-save-skm" class="w-full py-3 bg-sinjai-cyan hover:bg-sinjai-hover text-white font-extrabold text-sm rounded-xl shadow-md transition-all cursor-pointer">
            Simpan Perubahan URL SKM
          </button>
        </form>

      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 4: KELOLA AKUN ADMIN (KHUSUS SUPER ADMIN) -->
    <!-- ========================================================================= -->
    <div id="tab-content-users" class="hidden space-y-6">
      
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div>
          <h3 class="text-base font-extrabold text-slate-800">Manajemen Akun Administrator</h3>
          <p class="text-xs text-slate-500">Fitur Khusus Super Admin: Tambah dan hapus akun admin beserta peran (Role).</p>
        </div>
        <button onclick="openCreateUserModal()" class="px-4 py-2.5 bg-purple-700 hover:bg-purple-800 text-white text-xs font-extrabold rounded-xl shadow-md transition-all flex items-center space-x-1.5 cursor-pointer">
          <i class="fa-solid fa-user-plus"></i>
          <span>Tambah Admin Baru</span>
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-900 text-slate-200 text-xs uppercase font-extrabold tracking-wider border-b border-slate-800">
                <th class="p-3.5 text-center">ID</th>
                <th class="p-3.5">Username</th>
                <th class="p-3.5">Nama Administrator</th>
                <th class="p-3.5 text-center">Role Akses</th>
                <th class="p-3.5 text-center">Tanggal Dibuat</th>
                <th class="p-3.5 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="users-table-body" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
              <!-- Dynamic User Rows -->
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 5: KEAMANAN ADMIN -->
    <!-- ========================================================================= -->
    <div id="tab-content-security" class="hidden space-y-6">
      <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm max-w-lg mx-auto space-y-6">
        
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-lg font-extrabold text-slate-800 flex items-center">
            <i class="fa-solid fa-lock text-sinjai-cyan mr-2"></i> Ubah Password Saya
          </h3>
          <p class="text-xs text-slate-500 mt-1">Perbarui kata sandi akun Anda untuk menjaga keamanan.</p>
        </div>

        <form onsubmit="handleChangePassword(event)" class="space-y-4">
          <div>
            <label for="old_password" class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Password Lama *</label>
            <input type="password" id="old_password" required
                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-sinjai-cyan">
          </div>

          <div>
            <label for="new_password" class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Password Baru *</label>
            <input type="password" id="new_password" required minlength="6"
                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-sinjai-cyan">
          </div>

          <button type="submit" id="btn-change-pwd" class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-sm rounded-xl shadow-md transition-all cursor-pointer">
            Perbarui Password
          </button>
        </form>

      </div>
    </div>

  </div>

  <!-- MODAL EXPORT PDF OPTIONS & PREVIEW SELECTOR -->
  <div id="modal-pdf-options" class="hidden fixed inset-0 z-50 modal-backdrop flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl space-y-5">
      
      <div class="flex justify-between items-center border-b border-slate-100 pb-3">
        <h3 class="text-lg font-black text-slate-900 flex items-center">
          <i class="fa-solid fa-print text-rose-600 mr-2"></i> Pengaturan Cetak & Preview Dokumen
        </h3>
        <button onclick="closePdfModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
      </div>

      <div class="space-y-4">
        
        <!-- Pilihan Kolom -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-2">
            Pilih Kolom yang Akan Ditampilkan:
          </label>
          <div class="grid grid-cols-2 gap-2.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800">
            <label class="inline-flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" id="pdf-col-no" checked class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
              <span>Nomor Urut (NO)</span>
            </label>

            <label class="inline-flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" id="pdf-col-name" checked class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
              <span>Nama Lengkap</span>
            </label>

            <label class="inline-flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" id="pdf-col-nip" checked class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
              <span>NIP / NIK</span>
            </label>

            <label class="inline-flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" id="pdf-col-position" checked class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
              <span>Jabatan</span>
            </label>

            <label class="inline-flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" id="pdf-col-agency" checked class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
              <span>Instansi / Asal Sekolah</span>
            </label>

            <label class="inline-flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" id="pdf-col-type" class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
              <span>Jenis & Tipe Peserta</span>
            </label>

            <label class="inline-flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" id="pdf-col-phone" class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
              <span>No. Telepon / HP</span>
            </label>

            <label class="inline-flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" id="pdf-col-signature" checked class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
              <span>Tanda Tangan Digital</span>
            </label>
          </div>
        </div>

        <!-- Orientasi Halaman -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Orientasi Kertas:</label>
            <select id="pdf-orientation" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800">
              <option value="portrait">Potret (Portrait)</option>
              <option value="landscape" selected>Lanskap (Landscape - Rekomendasi)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Ukuran Kertas:</label>
            <select id="pdf-paper-size" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800">
              <option value="a4" selected>A4</option>
              <option value="legal">F4 / Legal / Folio</option>
            </select>
          </div>
        </div>

      </div>

      <!-- FOOTER BUTTONS: PREVIEW VS DIRECT DOWNLOAD -->
      <div class="pt-3 border-t border-slate-100 flex flex-wrap justify-end gap-2">
        <button type="button" onclick="closePdfModal()" class="px-3.5 py-2 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl hover:bg-slate-200">
          Batal
        </button>
        <button type="button" onclick="previewAndPrintPdf()" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center space-x-1.5 cursor-pointer">
          <i class="fa-solid fa-eye"></i>
          <span>👁️ Preview & Cetak Dokumen</span>
        </button>
        <button type="button" onclick="generatePdf()" id="btn-generate-pdf" 
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center space-x-1.5 cursor-pointer">
          <i class="fa-solid fa-download"></i>
          <span>Direct Download PDF</span>
        </button>
      </div>

    </div>
  </div>

  <!-- MODAL CREATE / EDIT EVENT -->
  <div id="modal-event-form" class="hidden fixed inset-0 z-50 modal-backdrop flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl space-y-5">
      <div class="flex justify-between items-center border-b border-slate-100 pb-3">
        <h3 id="modal-event-title" class="text-lg font-black text-slate-900">Tambah Kegiatan Baru</h3>
        <button onclick="closeEventModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
      </div>

      <form onsubmit="handleSaveEvent(event)" class="space-y-4">
        <input type="hidden" id="event-form-id" value="">

        <div>
          <label for="event-form-title" class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Kegiatan *</label>
          <input type="text" id="event-form-title" required placeholder="Contoh: Rapat Koordinasi OPD Pemkab Sinjai"
                 class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-sinjai-cyan">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label for="event-form-date" class="block text-xs font-bold text-slate-700 uppercase mb-1">Tanggal</label>
            <input type="date" id="event-form-date"
                   class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-800">
          </div>
          <div>
            <label for="event-form-start-time" class="block text-xs font-bold text-slate-700 uppercase mb-1">Jam Mulai</label>
            <input type="time" id="event-form-start-time" value="09:00"
                   class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-800">
          </div>
          <div>
            <label for="event-form-end-time" class="block text-xs font-bold text-slate-700 uppercase mb-1">Jam Selesai</label>
            <input type="time" id="event-form-end-time" value="16:00"
                   class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-800">
          </div>
        </div>

        <div>
          <label for="event-form-location" class="block text-xs font-bold text-slate-700 uppercase mb-1">Lokasi Rapat</label>
          <input type="text" id="event-form-location" placeholder="Ruang Pola Kantor Bupati Sinjai"
                 class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-sinjai-cyan">
        </div>

        <div>
          <label for="event-form-desc" class="block text-xs font-bold text-slate-700 uppercase mb-1">Keterangan Singkat</label>
          <textarea id="event-form-desc" rows="2" placeholder="Catatan tambahan mengenai kegiatan..."
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-sinjai-cyan"></textarea>
        </div>

        <div>
          <label class="inline-flex items-center cursor-pointer text-xs font-bold text-slate-700">
            <input type="checkbox" id="event-form-active" checked class="w-4 h-4 text-sinjai-cyan border-slate-300 rounded focus:ring-sinjai-cyan">
            <span class="ml-2">Set Langsung Aktif (Tampil di Portal Utama)</span>
          </label>
        </div>

        <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
          <button type="button" onclick="closeEventModal()" class="px-4 py-2 bg-slate-200 text-slate-700 font-bold text-xs rounded-xl hover:bg-slate-300">
            Batal
          </button>
          <button type="submit" class="px-5 py-2 bg-sinjai-cyan hover:bg-sinjai-hover text-white font-extrabold text-xs rounded-xl shadow-md">
            Simpan Kegiatan
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL CREATE ADMIN USER (SUPER ADMIN ONLY) -->
  <div id="modal-user-form" class="hidden fixed inset-0 z-50 modal-backdrop flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-5">
      <div class="flex justify-between items-center border-b border-slate-100 pb-3">
        <h3 class="text-lg font-black text-slate-900">Tambah Akun Admin Baru</h3>
        <button onclick="closeUserModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
      </div>

      <form onsubmit="handleCreateUser(event)" class="space-y-4">
        <div>
          <label for="user-form-username" class="block text-xs font-bold text-slate-700 uppercase mb-1">Username *</label>
          <input type="text" id="user-form-username" required placeholder="Contoh: admin_opd"
                 class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800">
        </div>

        <div>
          <label for="user-form-name" class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Administrator *</label>
          <input type="text" id="user-form-name" required placeholder="Contoh: Budi Santoso"
                 class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800">
        </div>

        <div>
          <label for="user-form-password" class="block text-xs font-bold text-slate-700 uppercase mb-1">Password *</label>
          <input type="password" id="user-form-password" required minlength="6" placeholder="Minimal 6 karakter"
                 class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800">
        </div>

        <div>
          <label for="user-form-role" class="block text-xs font-bold text-slate-700 uppercase mb-1">Role Peran Akses *</label>
          <select id="user-form-role" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold text-slate-800">
            <option value="admin">Admin (Tambah & Edit Kegiatan, Tidak Bisa Hapus)</option>
            <option value="super_admin">Super Admin (Akses Penuh + Hapus Kegiatan + Kelola User)</option>
          </select>
        </div>

        <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
          <button type="button" onclick="closeUserModal()" class="px-4 py-2 bg-slate-200 text-slate-700 font-bold text-xs rounded-xl hover:bg-slate-300">
            Batal
          </button>
          <button type="submit" class="px-5 py-2 bg-purple-700 hover:bg-purple-800 text-white font-extrabold text-xs rounded-xl shadow-md">
            Buat Akun
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL PREVIEW TANDA TANGAN -->
  <div id="modal-signature-preview" class="hidden fixed inset-0 z-50 modal-backdrop flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl text-center space-y-4">
      <div class="flex justify-between items-center border-b border-slate-100 pb-2">
        <h4 class="font-bold text-slate-800 text-sm">Preview Tanda Tangan Digital</h4>
        <button onclick="closeSignatureModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
      </div>

      <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex justify-center">
        <img id="preview-signature-img" src="" alt="Tanda Tangan" class="max-h-48 object-contain">
      </div>

      <p id="preview-signature-name" class="text-xs font-bold text-slate-700"></p>

      <button onclick="closeSignatureModal()" class="px-6 py-2 bg-slate-800 text-white font-bold text-xs rounded-xl hover:bg-slate-900">
        Tutup
      </button>
    </div>
  </div>

  <!-- HIDDEN PDF PRINT CONTAINER -->
  <div id="pdf-print-area" class="hidden p-6 bg-white"></div>

  <!-- JAVASCRIPT LOGIC PANEL ADMIN -->
  <script>
    let attendancesData = [];
    let eventsData = [];
    let currentUserRole = 'admin';
    let currentUserId = 0;
    let debounceTimer;

    // ===== KLIEN API TERPADU (aman untuk cPanel/ModSecurity) =====
    const API_BASE = <?php echo json_encode($__base); ?>;
    const API_ROOT = <?php echo json_encode($__rootv); ?>;

    const API_REST_MAP = {
      'events_portal': '/api/events/portal',
      'events_all': '/api/events/all',
      'meeting_by_code': '/api/meeting',
      'attendance': '/api/attendance',
      'skm_setting': '/api/skm-setting',
      'login': '/api/login',
      'logout': '/api/logout',
      'agencies': '/api/agencies',
      'check_auth': '/api/admin/check-auth',
      'admin_dashboard': '/api/admin/dashboard',
      'admin_events': '/api/admin/events',
      'admin_attendances': '/api/admin/attendances',
      'admin_users': '/api/admin/users',
      'admin_create_user': '/api/admin/create-user',
      'admin_update_skm': '/api/admin/settings/skm',
      'admin_change_password': '/api/admin/change-password'
    };

    async function apiFetch(actionName, options = {}) {
      let action = String(actionName || '');
      let query = '';
      const sep = action.search(/[?&]/);
      if (sep !== -1) { query = action.slice(sep + 1); action = action.slice(0, sep); }

      const opts = Object.assign({ credentials: 'same-origin', cache: 'no-store' }, options);
      opts.headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, options.headers || {});

      const rest = API_REST_MAP[action];
      const urls = [
        API_BASE + '/api.php?action=' + encodeURIComponent(action) + (query ? '&' + query : ''),
        rest ? (API_BASE + rest + (query ? '?' + query : '')) : null
      ].filter(Boolean);

      let firstError = null;
      for (const url of urls) {
        try {
          const res = await fetch(url, opts);
          const text = await res.text();
          let json = null;
          try { json = JSON.parse(text); }
          catch (e) {
            const m = text.match(/\{[\s\S]*\}/);
            if (m) { try { json = JSON.parse(m[0]); } catch (e2) {} }
          }
          if (json && json.success !== undefined) return json;
          if (firstError === null) firstError = 'HTTP ' + res.status;
        } catch (e) {
          if (firstError === null) firstError = 'Koneksi gagal';
        }
      }
      return { success: false, authenticated: false, message: 'Gagal terhubung ke API (' + (firstError || 'tanpa respon') + ').' };
    }

    // POST sebagai multipart/form-data agar tidak diblokir ModSecurity.
    function apiPost(action, data) {
      const fd = new FormData();
      Object.keys(data || {}).forEach(k => {
        const v = data[k];
        fd.append(k, (v === null || v === undefined) ? '' : v);
      });
      return apiFetch(action, { method: 'POST', body: fd });
    }

    document.addEventListener('DOMContentLoaded', async () => {
      const auth = await checkAuth();
      if (auth) {
        loadDashboardStats();
        loadEvents();
        loadAttendances();
        loadSkmSetting();
        if (currentUserRole === 'super_admin') {
          loadUsers();
        }
      }
    });

    async function checkAuth() {
      try {
        const json = await apiFetch('check_auth');
        if (!json || !json.authenticated) {
          window.location.href = API_ROOT + 'attamaki';
          return false;
        }
        currentUserId = json.admin.id;
        currentUserRole = json.admin.role || 'admin';
        document.getElementById('admin-display-name').textContent = json.admin.name || json.admin.username;

        const roleBadge = document.getElementById('admin-role-badge');
        if (currentUserRole === 'super_admin') {
          roleBadge.textContent = 'SUPER ADMIN';
          roleBadge.className = 'px-2 py-0.5 rounded text-[10px] font-black uppercase bg-purple-500/30 text-purple-300 border border-purple-400/40';
          document.getElementById('tab-btn-users').classList.remove('hidden');
        } else {
          roleBadge.textContent = 'ADMIN';
          roleBadge.className = 'px-2 py-0.5 rounded text-[10px] font-black uppercase bg-sky-500/30 text-sky-300 border border-sky-400/40';
        }
        return true;
      } catch (e) {
        window.location.href = API_ROOT + 'attamaki';
        return false;
      }
    }

    async function handleLogout() {
      if (confirm('Apakah Anda yakin ingin keluar dari panel admin?')) {
        await apiFetch('logout');
        window.location.href = API_ROOT + 'attamaki';
      }
    }

    function switchTab(tabName) {
      ['attendances', 'events', 'skm', 'users', 'security'].forEach(t => {
        const contentEl = document.getElementById(`tab-content-${t}`);
        if (contentEl) contentEl.classList.add('hidden');
        const btn = document.getElementById(`tab-btn-${t}`);
        if (btn) {
          btn.classList.remove('border-sinjai-cyan', 'text-sinjai-cyan');
          btn.classList.add('border-transparent', 'text-slate-500');
        }
      });

      const activeContent = document.getElementById(`tab-content-${tabName}`);
      if (activeContent) activeContent.classList.remove('hidden');
      const activeBtn = document.getElementById(`tab-btn-${tabName}`);
      if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-slate-500');
        activeBtn.classList.add('border-sinjai-cyan', 'text-sinjai-cyan');
      }
    }

    async function loadDashboardStats() {
      try {
        const json = await apiFetch('admin_dashboard');
        if (json && json.success) {
          document.getElementById('stat-total-events').textContent = json.data.totalEvents;
          document.getElementById('stat-active-events').textContent = json.data.activeEvents;
          document.getElementById('stat-total-attendances').textContent = json.data.totalAttendances;
          document.getElementById('stat-today-attendances').textContent = json.data.todayAttendances;
        }
      } catch (e) {}
    }

    async function loadEvents() {
      try {
        const json = await apiFetch('admin_events');
        if (json && json.success) {
          eventsData = json.data;
          renderEventsTable(eventsData);
          populateEventDropdownFilter(eventsData);
        }
      } catch (e) {}
    }

    function renderEventsTable(events) {
      const tbody = document.getElementById('events-table-body');
      tbody.innerHTML = '';

      if (!events || events.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-slate-400">Belum ada data kegiatan.</td></tr>`;
        return;
      }

      events.forEach(e => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50 transition-colors';
        const meetingCode = e.meeting_code || e.id;
        const meetingUrl = API_ROOT + `meeting/${meetingCode}`;

        tr.innerHTML = `
          <td class="p-3.5 text-center font-bold">
            <span class="text-slate-400 text-xs font-mono block">#${e.id}</span>
            <a href="${meetingUrl}" target="_blank" class="text-[11px] font-extrabold text-sinjai-cyan hover:underline flex items-center justify-center space-x-1 mt-0.5">
              <i class="fa-solid fa-link text-[10px]"></i>
              <span>${meetingCode}</span>
            </a>
          </td>
          <td class="p-3.5">
            <span class="font-extrabold text-slate-900 block text-sm">${e.title}</span>
            <span class="text-[11px] text-slate-500">${e.description || '-'}</span>
          </td>
          <td class="p-3.5">
            <span class="block font-semibold text-slate-800"><i class="fa-solid fa-calendar text-sinjai-cyan mr-1"></i> ${e.event_date || '-'} (${e.start_time || '09:00'}-${e.end_time || '16:00'})</span>
            <span class="text-[11px] text-slate-500"><i class="fa-solid fa-location-dot text-sinjai-cyan mr-1"></i> ${e.location || 'Kab. Sinjai'}</span>
          </td>
          <td class="p-3.5 text-center">
            <span class="px-2.5 py-1 bg-blue-50 text-sinjai-cyan font-black rounded-lg text-xs border border-blue-100">
              ${e.attendance_count || 0} Orang
            </span>
          </td>
          <td class="p-3.5 text-center">
            <button onclick="toggleEventStatus(${e.id}, ${e.is_active ? 0 : 1})" 
                    class="px-3 py-1 rounded-full text-[11px] font-extrabold shadow-2xs transition-all cursor-pointer ${e.is_active ? 'bg-emerald-100 text-emerald-700 border border-emerald-300 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300'}">
              <i class="fa-solid ${e.is_active ? 'fa-toggle-on text-emerald-600' : 'fa-toggle-off'} mr-1 text-sm"></i>
              ${e.is_active ? 'AKTIF' : 'NONAKTIF'}
            </button>
          </td>
          <td class="p-3.5 text-center space-x-1.5">
            <button onclick="openEditEventModal(${e.id})" class="p-2 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg transition-colors" title="Edit Nama & Detail Kegiatan">
              <i class="fa-solid fa-pen-to-square"></i>
            </button>
            ${currentUserRole === 'super_admin' ? `
              <button onclick="deleteEvent(${e.id})" class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg transition-colors" title="Hapus Kegiatan (Super Admin Only)">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            ` : ''}
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    function populateEventDropdownFilter(events) {
      const select = document.getElementById('filter-event-id');
      const currentVal = select.value;
      select.innerHTML = '<option value="all">-- Semua Kegiatan --</option>';
      if (events) {
        events.forEach(e => {
          const opt = document.createElement('option');
          opt.value = e.id;
          opt.textContent = e.title;
          select.appendChild(opt);
        });
      }
      select.value = currentVal;
    }

    async function toggleEventStatus(id, newStatus) {
      try {
        const json = await apiPost(`admin_toggle_event_status&id=${id}`, { is_active: newStatus });
        if (json && json.success) {
          loadEvents();
          loadDashboardStats();
        } else {
          alert(json ? json.message : 'Gagal merubah status.');
        }
      } catch (e) {
        alert('Gagal merubah status.');
      }
    }

    function openCreateEventModal() {
      document.getElementById('modal-event-title').textContent = 'Tambah Kegiatan Baru';
      document.getElementById('event-form-id').value = '';
      document.getElementById('event-form-title').value = '';
      document.getElementById('event-form-date').value = new Date().toISOString().split('T')[0];
      document.getElementById('event-form-start-time').value = '09:00';
      document.getElementById('event-form-end-time').value = '16:00';
      document.getElementById('event-form-location').value = 'Ruang Pola Kantor Bupati Sinjai';
      document.getElementById('event-form-desc').value = '';
      document.getElementById('event-form-active').checked = true;

      document.getElementById('modal-event-form').classList.remove('hidden');
    }

    function openEditEventModal(id) {
      const evt = eventsData.find(e => e.id === id);
      if (!evt) return;

      document.getElementById('modal-event-title').textContent = 'Edit Kegiatan';
      document.getElementById('event-form-id').value = evt.id;
      document.getElementById('event-form-title').value = evt.title;
      document.getElementById('event-form-date').value = evt.event_date || '';
      document.getElementById('event-form-start-time').value = evt.start_time || '09:00';
      document.getElementById('event-form-end-time').value = evt.end_time || '16:00';
      document.getElementById('event-form-location').value = evt.location || '';
      document.getElementById('event-form-desc').value = evt.description || '';
      document.getElementById('event-form-active').checked = evt.is_active === 1;

      document.getElementById('modal-event-form').classList.remove('hidden');
    }

    function closeEventModal() {
      document.getElementById('modal-event-form').classList.add('hidden');
    }

    async function handleSaveEvent(e) {
      e.preventDefault();
      const id = document.getElementById('event-form-id').value;
      const data = {
        title: document.getElementById('event-form-title').value,
        event_date: document.getElementById('event-form-date').value,
        start_time: document.getElementById('event-form-start-time').value,
        end_time: document.getElementById('event-form-end-time').value,
        location: document.getElementById('event-form-location').value,
        description: document.getElementById('event-form-desc').value,
        is_active: document.getElementById('event-form-active').checked ? 1 : 0
      };

      const action = id ? `admin_update_event&id=${id}` : 'admin_create_event';

      try {
        const json = await apiPost(action, data);
        if (json && json.success) {
          closeEventModal();
          loadEvents();
          loadDashboardStats();
          alert('Kegiatan berhasil disimpan!');
        } else {
          alert(json ? json.message : 'Gagal menyimpan kegiatan.');
        }
      } catch (e) {
        alert('Terjadi kesalahan koneksi.');
      }
    }

    async function deleteEvent(id) {
      if (currentUserRole !== 'super_admin') {
        alert('Hanya Super Admin yang berhak menghapus kegiatan.');
        return;
      }

      if (confirm('Apakah Anda yakin ingin menghapus kegiatan ini? SELURUH DATA KEHADIRAN PESERTA KEGIATAN INI JUGA AKAN TERHAPUS!')) {
        try {
          const json = await apiFetch(`admin_delete_event&id=${id}`, { method: 'POST' });
          if (json && json.success) {
            loadEvents();
            loadAttendances();
            loadDashboardStats();
          } else {
            alert(json ? json.message : 'Gagal menghapus kegiatan.');
          }
        } catch (e) {
          alert('Gagal menghapus kegiatan.');
        }
      }
    }

    function debounceLoadAttendances() {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(loadAttendances, 300);
    }

    async function loadAttendances() {
      const eventId = document.getElementById('filter-event-id').value;
      const participantType = document.getElementById('filter-participant-type').value;
      const roleType = document.getElementById('filter-role-type').value;
      const search = document.getElementById('filter-search').value;

      const params = new URLSearchParams({
        event_id: eventId,
        participant_type: participantType,
        role_type: roleType,
        search: search
      });

      try {
        const json = await apiFetch('admin_attendances&' + params.toString());
        if (json && json.success) {
          attendancesData = json.data;
          renderAttendanceTable(attendancesData);
        }
      } catch (e) {}
    }

    function renderAttendanceTable(list) {
      const tbody = document.getElementById('attendance-table-body');
      tbody.innerHTML = '';

      if (!list || list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-slate-400">Tidak ada data kehadiran yang cocok.</td></tr>`;
        return;
      }

      list.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50 transition-colors';
        tr.innerHTML = `
          <td class="p-3.5 text-center font-bold text-slate-400">${index + 1}</td>
          <td class="p-3.5">
            <span class="font-extrabold text-slate-900 block text-xs">${item.event_title}</span>
          </td>
          <td class="p-3.5">
            <span class="font-extrabold text-slate-900 block text-sm">${item.name}</span>
            <div class="flex items-center space-x-1.5 mt-1">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold ${item.participant_type === 'Pegawai Pemkab' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'}">
                ${item.participant_type}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold ${item.role_type === 'Narasumber' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}">
                ${item.role_type}
              </span>
            </div>
            ${item.nip_nik ? `<span class="text-[11px] text-slate-500 block mt-1">NIP/NIK: ${item.nip_nik}</span>` : ''}
          </td>
          <td class="p-3.5">
            <span class="font-bold text-slate-800 block">${item.position}</span>
            <span class="text-[11px] text-slate-500 block">${item.agency}</span>
          </td>
          <td class="p-3.5 text-[11px] text-slate-600">
            <div><i class="fa-solid fa-phone text-slate-400 mr-1"></i> ${item.phone || '-'}</div>
            <div><i class="fa-solid fa-envelope text-slate-400 mr-1"></i> ${item.email || '-'}</div>
          </td>
          <td class="p-3.5 text-center">
            <button onclick="previewSignature('${encodeURIComponent(item.signature_data)}', '${encodeURIComponent(item.name)}')" 
                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-700 text-[11px] font-bold rounded-lg transition-colors inline-flex items-center space-x-1 cursor-pointer">
              <i class="fa-solid fa-signature text-sinjai-cyan"></i>
              <span>Lihat TTD</span>
            </button>
          </td>
          <td class="p-3.5 text-center text-[11px] text-slate-500 font-semibold">
            ${item.created_at ? new Date(item.created_at).toLocaleString('id-ID') : '-'}
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    function previewSignature(encodedImg, encodedName) {
      document.getElementById('preview-signature-img').src = decodeURIComponent(encodedImg);
      document.getElementById('preview-signature-name').textContent = 'Tanda Tangan: ' + decodeURIComponent(encodedName);
      document.getElementById('modal-signature-preview').classList.remove('hidden');
    }

    function closeSignatureModal() {
      document.getElementById('modal-signature-preview').classList.add('hidden');
    }

    // =========================================================================
    // EXPORT & PREVIEW PRINT TABLE GENERATOR
    // =========================================================================
    function openPdfModal() {
      if (!attendancesData || attendancesData.length === 0) {
        alert('Tidak ada data kehadiran yang akan dicetak!');
        return;
      }
      document.getElementById('modal-pdf-options').classList.remove('hidden');
    }

    function closePdfModal() {
      document.getElementById('modal-pdf-options').classList.add('hidden');
    }

    function buildPrintHtml() {
      const showNo = document.getElementById('pdf-col-no').checked;
      const showName = document.getElementById('pdf-col-name').checked;
      const showNip = document.getElementById('pdf-col-nip').checked;
      const showPos = document.getElementById('pdf-col-position').checked;
      const showAgency = document.getElementById('pdf-col-agency').checked;
      const showType = document.getElementById('pdf-col-type').checked;
      const showPhone = document.getElementById('pdf-col-phone').checked;
      const showSig = document.getElementById('pdf-col-signature').checked;

      const selectedEventId = document.getElementById('filter-event-id').value;
      let eventTitle = 'DAFTAR HADIR KEGIATAN';
      let eventDate = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
      let eventLocation = 'Kabupaten Sinjai';

      if (selectedEventId !== 'all') {
        const evt = eventsData.find(e => e.id == selectedEventId);
        if (evt) {
          eventTitle = evt.title;
          if (evt.event_date) eventDate = evt.event_date;
          if (evt.location) eventLocation = evt.location;
        }
      }

      let html = `
        <div style="font-family: Arial, sans-serif; color: #000; padding: 10px;">
          
          <div style="text-align: center; margin-bottom: 12px;">
            <div style="font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">DAFTAR HADIR</div>
            <div style="font-size: 14px; font-weight: bold; text-transform: uppercase; margin-top: 4px;">${eventTitle}</div>
            <div style="font-size: 11px; margin-top: 6px; color: #333;">
              <strong>Hari / Tanggal :</strong> ${eventDate} &nbsp;|&nbsp; <strong>Lokasi :</strong> ${eventLocation}
            </div>
          </div>

          <table class="print-pdf-table">
            <thead>
              <tr>
                ${showNo ? '<th style="width: 35px;">NO</th>' : ''}
                ${showName ? '<th>NAMA LENGKAP</th>' : ''}
                ${showNip ? '<th>NIP / NIK</th>' : ''}
                ${showPos ? '<th>JABATAN</th>' : ''}
                ${showAgency ? '<th>INSTANSI / ASAL SEKOLAH</th>' : ''}
                ${showType ? '<th>JENIS / TIPE</th>' : ''}
                ${showPhone ? '<th>NO. TELEPON / HP</th>' : ''}
                ${showSig ? '<th colspan="2" style="width: 250px;">TANDA TANGAN</th>' : ''}
              </tr>
            </thead>
            <tbody>
      `;

      attendancesData.forEach((item, idx) => {
        const rowNo = idx + 1;
        const isOdd = rowNo % 2 !== 0;

        html += `<tr>`;
        if (showNo) html += `<td style="text-align: center; font-weight: bold;">${rowNo}</td>`;
        if (showName) html += `<td style="font-weight: bold;">${item.name}</td>`;
        if (showNip) html += `<td>${item.nip_nik || '-'}</td>`;
        if (showPos) html += `<td>${item.position}</td>`;
        if (showAgency) html += `<td>${item.agency}</td>`;
        if (showType) html += `<td>${item.participant_type} (${item.role_type})</td>`;
        if (showPhone) html += `<td>${item.phone || '-'}</td>`;

        if (showSig) {
          if (isOdd) {
            html += `
              <td style="width: 125px; height: 65px; vertical-align: top; padding: 3px 6px;">
                <div style="font-size: 10px; font-weight: bold; color: #111;">${rowNo}.</div>
                <div style="text-align: center; margin-top: -8px;"><img src="${item.signature_data}" style="max-height: 55px; max-width: 115px; object-fit: contain; display: inline-block;" /></div>
              </td>
              <td style="width: 125px; height: 65px; background-color: #fafafa;"></td>
            `;
          } else {
            html += `
              <td style="width: 125px; height: 65px; background-color: #fafafa;"></td>
              <td style="width: 125px; height: 65px; vertical-align: top; padding: 3px 6px;">
                <div style="font-size: 10px; font-weight: bold; color: #111;">${rowNo}.</div>
                <div style="text-align: center; margin-top: -8px;"><img src="${item.signature_data}" style="max-height: 55px; max-width: 115px; object-fit: contain; display: inline-block;" /></div>
              </td>
            `;
          }
        }
        html += `</tr>`;
      });

      html += `
            </tbody>
          </table>

          <div style="margin-top: 15px; text-align: right; font-size: 10px; color: #666;">
            Dicetak otomatis via Sistem Kehadiran Sinjai — kehadiran.sinjaikab.go.id
          </div>

        </div>
      `;

      return html;
    }

    // 1. PREVIEW & PRINT WINDOW (Preview Cetak Asli Browser)
    function previewAndPrintPdf() {
      const htmlContent = buildPrintHtml();
      const orientation = document.getElementById('pdf-orientation').value;
      const paperSize = document.getElementById('pdf-paper-size').value;

      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>Preview Daftar Hadir - Pemkab Sinjai</title>
          <style>
            @page {
              size: ${paperSize === 'legal' ? 'legal' : 'A4'} ${orientation};
              margin: 10mm;
            }
            body { font-family: Arial, sans-serif; margin: 0; padding: 15px; color: #000; background: #fff; }
            .print-pdf-table { width: 100%; border-collapse: collapse; font-size: 11px; }
            .print-pdf-table th, .print-pdf-table td { border: 1px solid #000; padding: 6px 8px; vertical-align: middle; }
            .print-pdf-table th { background-color: #f3f4f6; font-weight: bold; text-align: center; text-transform: uppercase; }
            @media print {
              .no-print { display: none !important; }
            }
          </style>
        </head>
        <body>
          <div class="no-print" style="margin-bottom: 15px; padding: 12px; background: #e0f2fe; border: 1px solid #0284c7; border-radius: 8px; text-align: center; font-family: sans-serif;">
            <strong style="color: #0369a1; font-size: 14px;">👁️ Tampilan Preview Dokumen Daftar Hadir</strong>
            <div style="margin-top: 4px; font-size: 12px; color: #334155;">Anda dapat melihat hasil tata letak dokumen di bawah ini sebelum dicetak / disimpan sebagai PDF.</div>
            <button onclick="window.print()" style="margin-top: 10px; padding: 8px 20px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; font-size: 13px; cursor: pointer;">🖨️ Buka Dialog Cetak / Simpan PDF</button>
          </div>
          ${htmlContent}
          <script>
            setTimeout(function() { window.print(); }, 600);
          <\/script>
        </body>
        </html>
      `);
      printWindow.document.close();
      closePdfModal();
    }

    // 2. DIRECT DOWNLOAD PDF
    function generatePdf() {
      const htmlContent = buildPrintHtml();
      const orientation = document.getElementById('pdf-orientation').value;
      const paperSize = document.getElementById('pdf-paper-size').value;

      const printArea = document.getElementById('pdf-print-area');
      printArea.innerHTML = htmlContent;
      printArea.classList.remove('hidden');

      const btn = document.getElementById('btn-generate-pdf');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Memproses...';

      const opt = {
        margin:       [8, 8, 8, 8],
        filename:     `Daftar_Hadir_${new Date().toISOString().split('T')[0]}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'mm', format: paperSize === 'legal' ? [215.9, 330] : 'a4', orientation: orientation }
      };

      html2pdf().set(opt).from(printArea).save().then(() => {
        printArea.classList.add('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-download"></i> <span>Direct Download PDF</span>';
        closePdfModal();
      }).catch(err => {
        previewAndPrintPdf();
      });
    }

    function exportToExcel() {
      if (!attendancesData || attendancesData.length === 0) {
        alert('Tidak ada data untuk diexport!');
        return;
      }

      const rows = attendancesData.map((item, idx) => ({
        "No": idx + 1,
        "Nama Kegiatan": item.event_title,
        "Nama Peserta": item.name,
        "Jenis Peserta": item.participant_type,
        "Tipe Peserta": item.role_type,
        "Jabatan": item.position,
        "Instansi": item.agency,
        "NIP / NIK": item.nip_nik || "-",
        "No. Telepon": item.phone || "-",
        "Email": item.email || "-",
        "Waktu Presensi": item.created_at
      }));

      const worksheet = XLSX.utils.json_to_sheet(rows);
      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, "Daftar Kehadiran");

      XLSX.writeFile(workbook, `Daftar_Hadir_Sinjai_${new Date().toISOString().split('T')[0]}.xlsx`);
    }

    function exportToCsv() {
      if (!attendancesData || attendancesData.length === 0) {
        alert('Tidak ada data untuk diexport!');
        return;
      }

      let csv = 'No,Kegiatan,Nama,Jenis,Tipe,Jabatan,Instansi,NIP_NIK,Telepon,Email,Waktu_Hadir\n';
      attendancesData.forEach((item, idx) => {
        const row = [
          idx + 1,
          `"${item.event_title.replace(/"/g, '""')}"`,
          `"${item.name.replace(/"/g, '""')}"`,
          `"${item.participant_type}"`,
          `"${item.role_type}"`,
          `"${item.position.replace(/"/g, '""')}"`,
          `"${item.agency.replace(/"/g, '""')}"`,
          `"${item.nip_nik || ''}"`,
          `"${item.phone || ''}"`,
          `"${item.email || ''}"`,
          `"${item.created_at}"`
        ];
        csv += row.join(',') + '\n';
      });

      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.setAttribute('download', `Daftar_Hadir_Sinjai_${new Date().toISOString().split('T')[0]}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    async function loadSkmSetting() {
      try {
        const json = await apiFetch('skm_setting');
        if (json && json.success) {
          document.getElementById('skm-input-url').value = json.skm_url;
          renderAdminSkmQr(json.skm_url);
        }
      } catch (e) {}
    }

    function renderAdminSkmQr(url) {
      const container = document.getElementById('admin-skm-qrcode');
      container.innerHTML = '';
      new QRCode(container, {
        text: url,
        width: 140,
        height: 140,
        colorDark: "#000000",
        colorLight: "#ffffff"
      });
    }

    async function handleUpdateSkm(e) {
      e.preventDefault();
      const skm_url = document.getElementById('skm-input-url').value;

      try {
        const json = await apiPost('admin_update_skm', { skm_url });
        if (json && json.success) {
          renderAdminSkmQr(skm_url);
          alert('URL SKM berhasil diperbarui!');
        } else {
          alert(json ? json.message : 'Gagal memperbarui URL SKM.');
        }
      } catch (e) {
        alert('Gagal memperbarui URL SKM.');
      }
    }

    // SUPER ADMIN USER MANAGEMENT FUNCTIONS
    async function loadUsers() {
      try {
        const json = await apiFetch('admin_users');
        if (json && json.success) {
          renderUsersTable(json.data);
        }
      } catch (e) {}
    }

    function renderUsersTable(users) {
      const tbody = document.getElementById('users-table-body');
      tbody.innerHTML = '';

      if (!users) return;

      users.forEach(u => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50 transition-colors';
        const isSelf = u.id === currentUserId;

        tr.innerHTML = `
          <td class="p-3.5 text-center font-bold text-slate-400">#${u.id}</td>
          <td class="p-3.5 font-bold text-slate-800">${u.username} ${isSelf ? '<span class="text-[10px] bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded font-black ml-1">(Saya)</span>' : ''}</td>
          <td class="p-3.5 font-semibold text-slate-700">${u.name}</td>
          <td class="p-3.5 text-center">
            <span class="px-2.5 py-1 rounded text-[10px] font-black uppercase ${u.role === 'super_admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-sky-100 text-sky-800 border border-sky-200'}">
              ${u.role === 'super_admin' ? 'Super Admin' : 'Admin'}
            </span>
          </td>
          <td class="p-3.5 text-center text-slate-500 text-[11px]">${u.created_at || '-'}</td>
          <td class="p-3.5 text-center">
            ${!isSelf ? `
              <button onclick="deleteUser(${u.id}, '${u.username}')" class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg transition-colors" title="Hapus Pengguna Admin">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            ` : '<span class="text-[11px] text-slate-400 italic">Aktif</span>'}
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    function openCreateUserModal() {
      document.getElementById('user-form-username').value = '';
      document.getElementById('user-form-name').value = '';
      document.getElementById('user-form-password').value = '';
      document.getElementById('user-form-role').value = 'admin';
      document.getElementById('modal-user-form').classList.remove('hidden');
    }

    function closeUserModal() {
      document.getElementById('modal-user-form').classList.add('hidden');
    }

    async function handleCreateUser(e) {
      e.preventDefault();
      const username = document.getElementById('user-form-username').value;
      const name = document.getElementById('user-form-name').value;
      const password = document.getElementById('user-form-password').value;
      const role = document.getElementById('user-form-role').value;

      try {
        const json = await apiPost('admin_create_user', { username, name, password, role });
        if (json && json.success) {
          closeUserModal();
          loadUsers();
          alert(json.message);
        } else {
          alert(json ? json.message : 'Gagal membuat akun.');
        }
      } catch (err) {
        alert('Terjadi kesalahan koneksi.');
      }
    }

    async function deleteUser(id, username) {
      if (confirm(`Apakah Anda yakin ingin menghapus akun admin "${username}"?`)) {
        try {
          const json = await apiFetch(`admin_delete_user&id=${id}`, { method: 'POST' });
          if (json && json.success) {
            loadUsers();
            alert(json.message);
          } else {
            alert(json ? json.message : 'Gagal menghapus akun admin.');
          }
        } catch (e) {
          alert('Gagal menghapus akun admin.');
        }
      }
    }

    async function handleChangePassword(e) {
      e.preventDefault();
      const old_password = document.getElementById('old_password').value;
      const new_password = document.getElementById('new_password').value;

      try {
        const json = await apiPost('admin_change_password', { old_password, new_password });
        if (json && json.success) {
          alert('Password admin berhasil diubah!');
          document.getElementById('old_password').value = '';
          document.getElementById('new_password').value = '';
        } else {
          alert(json ? json.message : 'Gagal mengubah password.');
        }
      } catch (e) {
        alert('Terjadi kesalahan koneksi.');
      }
    }
  </script>

</body>
</html>
