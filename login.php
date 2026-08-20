<?php
// Base path otomatis: bekerja di root domain maupun subfolder cPanel.
$__base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$__base = ($__base === '/' || $__base === '.' || $__base === '\\') ? '' : rtrim($__base, '/');
// URL root aplikasi, selalu berakhiran "/", untuk aset & tautan.
$__rootv = ($__base === '' ? '/' : $__base . '/');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Administrator | Pemkab Sinjai</title>

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
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased">

  <div class="max-w-md w-full">
    
    <!-- BRANDING CARD -->
    <div class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-8 shadow-2xl backdrop-blur-xl relative overflow-hidden">
      
      <!-- Top Decorative Bar -->
      <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-sinjai-cyan via-blue-500 to-emerald-400"></div>

      <!-- LOGO & HEADER -->
      <div class="text-center space-y-3 mb-8">
        <div class="inline-block p-3 bg-slate-900/80 rounded-2xl border border-slate-700 shadow-inner">
          <img src="<?php echo $__rootv; ?>assets/sinjai2.png" onerror="if(this.src.includes('sinjai2.png')){this.src='<?php echo $__rootv; ?>assets/sinjai.png';}else if(this.src.includes('sinjai.png')){this.src='<?php echo $__rootv; ?>sinjai2.png';}else{this.src='<?php echo $__rootv; ?>sinjai.png';}" alt="Logo Pemkab Sinjai" class="h-16 w-auto mx-auto drop-shadow-md object-contain">
        </div>
        <h1 class="text-xl font-extrabold text-white tracking-tight">PANEL ADMINISTRATOR</h1>
        <p class="text-xs text-slate-400 font-medium">Sistem Kehadiran Kegiatan Pemkab Sinjai</p>
      </div>

      <!-- ALERT BOX -->
      <div id="login-alert" class="hidden mb-6 p-4 rounded-xl text-xs font-semibold flex items-center space-x-2 border">
        <i id="login-alert-icon" class="text-sm"></i>
        <span id="login-alert-text"></span>
      </div>

      <!-- FORM LOGIN -->
      <form onsubmit="handleLogin(event)" class="space-y-5">
        
        <div>
          <label for="username" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
            Username Admin
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
              <i class="fa-solid fa-user-shield text-sm"></i>
            </div>
            <input type="text" id="username" required placeholder="Masukkan username"
                   class="w-full pl-10 pr-4 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sinjai-cyan focus:border-sinjai-cyan transition-all font-medium">
          </div>
        </div>

        <div>
          <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
            Password Admin
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
              <i class="fa-solid fa-key text-sm"></i>
            </div>
            <input type="password" id="password" required placeholder="Masukkan password"
                   class="w-full pl-10 pr-10 py-3 bg-slate-900/90 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sinjai-cyan focus:border-sinjai-cyan transition-all font-medium">
            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white">
              <i id="pwd-icon" class="fa-solid fa-eye text-xs"></i>
            </button>
          </div>
        </div>

        <button type="submit" id="btn-login"
                class="w-full py-3.5 bg-[#029fe4] hover:bg-[#0084c7] active:bg-[#0069b4] text-white font-extrabold text-sm rounded-xl shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center space-x-2 cursor-pointer">
          <i class="fa-solid fa-right-to-bracket"></i>
          <span>MASUK PANEL ADMIN</span>
        </button>

      </form>

      <div class="mt-8 pt-4 border-t border-slate-700/50 text-center">
        <a href="<?php echo $__rootv; ?>" class="text-xs text-slate-400 hover:text-sinjai-cyan font-medium transition-colors flex items-center justify-center space-x-1.5">
          <i class="fa-solid fa-arrow-left text-[10px]"></i>
          <span>Kembali ke Halaman Form Kehadiran</span>
        </a>
      </div>

    </div>

    <!-- FOOTER COPYRIGHT -->
    <p class="text-center text-xs text-slate-500 mt-6">
      © 2026 Pemerintah Kabupaten Sinjai — Domain: kehadiran.sinjaikab.go.id
    </p>

  </div>

  <script>
    // ===== KLIEN API TERPADU (aman untuk cPanel/ModSecurity) =====
    const API_BASE = <?php echo json_encode($__base); ?>;
    const API_ROOT = <?php echo json_encode($__rootv); ?>;

    const API_REST_MAP = {
      'login': '/api/login',
      'logout': '/api/logout',
      'check_auth': '/api/admin/check-auth'
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

    document.addEventListener('DOMContentLoaded', checkExistingSession);

    async function checkExistingSession() {
      try {
        const json = await apiFetch('check_auth');
        if (json.authenticated) {
          window.location.href = API_ROOT + 'panel-admin';
        }
      } catch (e) {}
    }

    function togglePasswordVisibility() {
      const pwd = document.getElementById('password');
      const icon = document.getElementById('pwd-icon');
      if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'fa-solid fa-eye-slash text-xs';
      } else {
        pwd.type = 'password';
        icon.className = 'fa-solid fa-eye text-xs';
      }
    }

    async function handleLogin(e) {
      e.preventDefault();
      const btn = document.getElementById('btn-login');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Memeriksa...';

      const alertBox = document.getElementById('login-alert');
      const alertText = document.getElementById('login-alert-text');
      const alertIcon = document.getElementById('login-alert-icon');
      alertBox.classList.add('hidden');

      try {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        const json = await apiPost('login', { username, password });

        if (json.success) {
          alertBox.className = 'mb-6 p-4 rounded-xl text-xs font-semibold flex items-center space-x-2 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
          alertIcon.className = 'fa-solid fa-circle-check text-emerald-400';
          alertText.textContent = 'Login berhasil! Membuka panel...';
          alertBox.classList.remove('hidden');

          setTimeout(() => {
            window.location.href = API_ROOT + 'panel-admin';
          }, 800);
        } else {
          alertBox.className = 'mb-6 p-4 rounded-xl text-xs font-semibold flex items-center space-x-2 bg-rose-500/20 text-rose-300 border border-rose-500/40';
          alertIcon.className = 'fa-solid fa-triangle-exclamation text-rose-400';
          alertText.textContent = json.message || 'Username atau password salah.';
          alertBox.classList.remove('hidden');
        }
      } catch (err) {
        alertBox.className = 'mb-6 p-4 rounded-xl text-xs font-semibold flex items-center space-x-2 bg-rose-500/20 text-rose-300 border border-rose-500/40';
        alertIcon.className = 'fa-solid fa-wifi text-rose-400';
        alertText.textContent = 'Terjadi kesalahan koneksi ke server.';
        alertBox.classList.remove('hidden');
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> <span>MASUK PANEL ADMIN</span>';
      }
    }
  </script>

</body>
</html>
