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
  <title>Kehadiran Kegiatan | Pemerintah Kabupaten Sinjai</title>

  <!-- SEO Meta Tags -->
  <meta name="description" content="Portal Kehadiran Kegiatan Resmi Pemerintah Kabupaten Sinjai (kehadiran.sinjaikab.go.id).">
  <meta name="keywords" content="Kehadiran Sinjai, Absensi Rapat Sinjai, Pemkab Sinjai, Diskominfo Sinjai">
  <meta name="author" content="Pemerintah Kabupaten Sinjai">

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
              cyan: '#00a8e8',
              hover: '#008dc6',
              dark: '#002b49',
              bg: '#e8f4f8'
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
          }
        }
      }
    }
  </script>

  <style>
    body {
      background: linear-gradient(135deg, #e0f2fe 0%, #ebf8ff 50%, #e8f4f8 100%);
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: #1e293b;
    }
    .card-hover {
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .card-hover:hover {
      transform: translateY(-3px);
      box-shadow: 0 14px 28px -6px rgba(0, 168, 232, 0.2);
    }
    .modal-backdrop {
      background-color: rgba(15, 23, 42, 0.65);
      backdrop-filter: blur(4px);
    }
  </style>
</head>
<body class="min-h-screen flex flex-col justify-between p-3 sm:p-6 antialiased selection:bg-sinjai-cyan selection:text-white pb-28 sm:pb-24">

  <!-- HEADER NAVIGATION & BRANDING -->
  <header class="max-w-4xl mx-auto w-full flex flex-col sm:flex-row items-center justify-between gap-4 py-3 px-2 mb-4">
    
    <!-- BRANDING LOGO (Sinjai & Diskominfo) -->
    <div class="flex items-center space-x-3">
      <img src="<?php echo $__rootv; ?>assets/sinjai2.png" 
           onerror="if(this.src.includes('sinjai2.png')){this.src='<?php echo $__rootv; ?>assets/sinjai.png';}else{this.src='<?php echo $__rootv; ?>sinjai2.png';}" 
           alt="Logo Pemkab Sinjai" class="h-10 sm:h-12 w-auto object-contain">
      <div class="h-8 w-px bg-blue-200/80 hidden sm:block"></div>
      <div>
        <div class="flex items-center space-x-1.5 text-sinjai-dark">
          <i class="fa-regular fa-square-check text-xl text-sinjai-cyan"></i>
          <h1 class="text-base sm:text-lg font-black tracking-tight leading-none text-slate-800">Kehadiran Kegiatan</h1>
        </div>
        <p class="text-[11px] sm:text-xs font-semibold text-slate-500 mt-0.5">
          Pemerintah Kabupaten Sinjai
        </p>
      </div>
    </div>

    <!-- RIGHT HEADER: LOGO KOMINFO & TOMBOL LOGIN ADMIN -->
    <div class="flex items-center space-x-3 w-full sm:w-auto justify-between sm:justify-end">
      <img src="<?php echo $__rootv; ?>assets/kominfo.png" 
           onerror="if(this.src.includes('assets/kominfo.png')){this.src='<?php echo $__rootv; ?>kominfo.png';}else{this.src='<?php echo $__rootv; ?>assets/kominfo.svg';}" 
           alt="Logo Diskominfo Sinjai" class="h-10 sm:h-12 w-auto object-contain">
      
      <!-- TOMBOL LOGIN ADMIN -->
      <a href="<?php echo $__rootv; ?>attamaki" 
         class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center space-x-1.5 transition-all cursor-pointer">
        <i class="fa-solid fa-user-gear text-sinjai-cyan"></i>
        <span>Login Admin</span>
      </a>
    </div>

  </header>

  <!-- MAIN FLOATING PORTAL CARD -->
  <main class="max-w-4xl mx-auto w-full bg-white rounded-3xl shadow-xl border border-blue-100/80 p-4 sm:p-8 my-auto space-y-6">
    
    <!-- TOP SEARCH BAR & VIEW ALL BUTTON -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
      
      <!-- SEARCH INPUT BAR -->
      <div class="relative flex-grow">
        <input type="text" id="portal-search-input" onkeyup="handleSearchInput()" placeholder="Cari kegiatan atau rapat..." 
               class="w-full pl-11 pr-4 py-3.5 bg-[#f0f9ff] border border-sky-200/90 rounded-2xl text-xs sm:text-sm font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-sinjai-cyan focus:border-sinjai-cyan transition-all shadow-inner">
        <i class="fa-solid fa-magnifying-glass absolute left-4 top-4 text-sky-400 text-sm"></i>
      </div>

      <!-- VIEW ALL ARCHIVE BUTTON -->
      <button onclick="openAllEventsModal()" class="px-4 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-extrabold rounded-2xl border border-slate-200 shadow-2xs transition-all flex items-center justify-center space-x-1.5 whitespace-nowrap cursor-pointer">
        <i class="fa-solid fa-list-check text-sinjai-cyan"></i>
        <span>Lihat Semua Kegiatan</span>
      </button>

    </div>

    <!-- GRID 4 CARD RAPAT (PRESISI MENPAN RB SAMPLE) -->
    <div id="events-grid-container" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
      <!-- 4 Cards Rendered Dynamically -->
    </div>

    <!-- INFO BOX KETERANGAN -->
    <div class="p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100/70 flex items-center space-x-3 text-xs font-semibold text-slate-600">
      <i class="fa-solid fa-circle-info text-sinjai-cyan text-base shrink-0"></i>
      <span>Klik tombol <strong>Isi Daftar Hadir</strong> pada kegiatan di atas untuk membubuhkan tanda tangan digital presensi.</span>
    </div>

  </main>

  <!-- FOOTER COPYRIGHT -->
  <footer class="max-w-4xl mx-auto w-full text-center py-4 text-xs font-medium text-slate-500">
    © 2026 Pemerintah Kabupaten Sinjai — Domain Resmi: <a href="<?php echo $__rootv; ?>" class="text-sinjai-cyan hover:underline font-bold">kehadiran.sinjaikab.go.id</a>
  </footer>

  <!-- BANNER PERSETUJUAN PRIVASI DI BAGIAN BAWAH (BARIS SELALU MUNcul TAMPIL UTAMA) -->
  <div id="privacy-banner" class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t-2 border-[#1d4ed8] shadow-[0_-6px_25px_rgba(0,0,0,0.18)] transition-all">
    <div class="max-w-6xl mx-auto px-4 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs sm:text-sm font-medium text-slate-700">
      <div class="flex-1 leading-snug text-slate-800">
        Website ini dikelola oleh Dinas Komunikasi Informatika dan Persandian Kabupaten Sinjai. Kami berkomitmen melindungi privasi dan data pribadi Anda. Info lebih lanjut dapat mengunjungi halaman <a href="https://ppidkab.sinjaikab.go.id/v2/kebijakan-privasi" target="_blank" rel="noopener noreferrer" class="text-[#1d4ed8] hover:underline font-bold">Kebijakan Privasi</a>
      </div>
      <button onclick="acceptPrivacy()" class="px-6 py-2 bg-[#1d4ed8] hover:bg-blue-800 active:scale-95 text-white font-extrabold text-xs sm:text-sm rounded-full shadow-md transition-all shrink-0 cursor-pointer">
        Setuju
      </button>
    </div>
  </div>

  <!-- MODAL ARSIP SEMUA KEGIATAN -->
  <div id="modal-all-events" class="hidden fixed inset-0 z-50 modal-backdrop flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-5 sm:p-7 shadow-2xl space-y-4 max-h-[90vh] flex flex-col">
      
      <div class="flex justify-between items-center border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-base sm:text-lg font-black text-slate-900">Arsip Seluruh Kegiatan</h3>
          <p class="text-xs text-slate-500">Daftar lengkap kegiatan rapat Pemerintah Kabupaten Sinjai.</p>
        </div>
        <button onclick="closeAllEventsModal()" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">&times;</button>
      </div>

      <!-- SEARCH FILTER IN MODAL -->
      <div class="relative">
        <input type="text" id="modal-search-input" onkeyup="filterModalEvents()" placeholder="Filter kegiatan..."
               class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800">
        <i class="fa-solid fa-filter absolute left-3 top-2.5 text-slate-400 text-xs"></i>
      </div>

      <div id="modal-events-list" class="overflow-y-auto space-y-2.5 pr-1 flex-1 min-h-[250px]">
        <!-- Dynamic Archive Rows -->
      </div>

      <div class="pt-2 border-t border-slate-100 flex justify-end">
        <button onclick="closeAllEventsModal()" class="px-5 py-2 bg-slate-800 text-white font-extrabold text-xs rounded-xl hover:bg-slate-900">
          Tutup
        </button>
      </div>

    </div>
  </div>

  <!-- JAVASCRIPT LOGIC LANDING PAGE -->
  <script>
    let portalEvents = [];
    let archiveEvents = [];
    let searchDebounce;

    // ===== KLIEN API TERPADU (aman untuk cPanel/ModSecurity) =====
    // API_BASE dihitung oleh PHP, jadi URL selalu benar baik di root domain
    // maupun di subfolder, termasuk saat URL di-rewrite.
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

    // Semua POST dikirim sebagai multipart/form-data, bukan JSON berisi data: URI,
    // supaya tidak diblokir ModSecurity cPanel (penyebab HTTP 403).
    function apiPost(action, data) {
      const fd = new FormData();
      Object.keys(data || {}).forEach(k => {
        const v = data[k];
        fd.append(k, (v === null || v === undefined) ? '' : v);
      });
      return apiFetch(action, { method: 'POST', body: fd });
    }

    document.addEventListener('DOMContentLoaded', async () => {
      checkPrivacyConsent();
      await loadPortalEvents();
    });

    function checkPrivacyConsent() {
      if (localStorage.getItem('sinjai_privacy_agreed') === 'true') {
        document.getElementById('privacy-banner').classList.add('hidden');
      } else {
        document.getElementById('privacy-banner').classList.remove('hidden');
      }
    }

    function acceptPrivacy() {
      localStorage.setItem('sinjai_privacy_agreed', 'true');
      document.getElementById('privacy-banner').classList.add('hidden');
    }

    async function loadPortalEvents() {
      try {
        const json = await apiFetch('events_portal');
        if (json && json.success && json.data) {
          portalEvents = json.data;
          renderGridCards(portalEvents);
        }
      } catch (e) {
        console.error('Error loading events:', e);
      }
    }

    function renderGridCards(events) {
      const container = document.getElementById('events-grid-container');
      container.innerHTML = '';

      if (!events || events.length === 0) {
        container.innerHTML = `
          <div class="col-span-full py-12 text-center space-y-3 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
            <i class="fa-solid fa-calendar-xmark text-4xl text-slate-300"></i>
            <p class="text-xs sm:text-sm font-bold text-slate-500">Tidak ada kegiatan aktif saat ini.</p>
          </div>
        `;
        return;
      }

      events.slice(0, 4).forEach((evt, idx) => {
        const cardNum = idx + 1;
        const meetingCode = evt.meeting_code || evt.id;
        const meetingUrl = API_ROOT + `meeting/${meetingCode}`;

        let formattedDate = '04 Agustus 2026';
        if (evt.event_date) {
          try {
            const d = new Date(evt.event_date);
            if (!isNaN(d.getTime())) {
              formattedDate = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
            } else {
              formattedDate = evt.event_date;
            }
          } catch(e) { formattedDate = evt.event_date; }
        }

        const startTimeStr = evt.start_time || '09:00';
        const endTimeStr = evt.end_time || '16:00';

        const card = document.createElement('div');
        card.className = 'card-hover bg-white p-5 rounded-3xl border-2 border-sky-100 hover:border-[#00a8e8] shadow-sm flex flex-col justify-between space-y-4 relative overflow-hidden group';

        card.innerHTML = `
          <!-- CARD HEADER & BADGE NO (PRESISI FOTO SAMPEL) -->
          <div class="flex items-start space-x-3.5">
            <span class="w-10 h-10 rounded-2xl bg-[#00a8e8] text-white flex items-center justify-center font-black text-base shrink-0 shadow-[0_4px_12px_rgba(0,168,232,0.35)]">
              ${cardNum}
            </span>
            <h3 class="font-extrabold text-sm sm:text-base text-[#002b49] leading-snug line-clamp-2 flex-grow pt-0.5">
              ${evt.title}
            </h3>
          </div>

          <!-- WAKTU BOX (PRESISI FOTO SAMPEL) -->
          <div class="p-3.5 bg-[#f0f9ff] rounded-2xl border border-sky-100/80 flex items-start space-x-3 text-xs">
            <div class="w-7 h-7 rounded-full bg-[#00a8e8] text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
              <i class="fa-regular fa-clock text-xs"></i>
            </div>
            <div class="space-y-0.5 flex-1 min-w-0">
              <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">WAKTU</span>
              <div class="font-semibold text-slate-700 leading-tight">
                <div>${formattedDate} Pukul ${startTimeStr}</div>
                <div>s/d ${formattedDate} Pukul ${endTimeStr}</div>
              </div>
            </div>
          </div>

          <!-- LOKASI BOX (PRESISI FOTO SAMPEL) -->
          <div class="p-3.5 bg-[#f0f9ff] rounded-2xl border border-sky-100/80 flex items-start space-x-3 text-xs">
            <div class="w-7 h-7 rounded-full bg-[#00a8e8] text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
              <i class="fa-solid fa-location-dot text-xs"></i>
            </div>
            <div class="space-y-0.5 flex-1 min-w-0">
              <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">LOKASI</span>
              <div class="font-semibold text-slate-700 leading-tight line-clamp-2">
                ${evt.location || 'Kab. Sinjai'}
              </div>
            </div>
          </div>

          <!-- ACTION BUTTON (PRESISI FOTO SAMPEL) -->
          <div class="pt-1">
            <a href="${meetingUrl}" 
               class="w-full py-3 px-4 bg-[#00a8e8] hover:bg-[#008dc6] active:scale-[0.98] text-white font-extrabold text-xs sm:text-sm rounded-2xl shadow-[0_4px_14px_rgba(0,168,232,0.35)] flex items-center justify-center space-x-2 transition-all cursor-pointer">
              <i class="fa-regular fa-pen-to-square text-sm"></i>
              <span>Isi Daftar Hadir</span>
            </a>
          </div>
        `;

        container.appendChild(card);
      });
    }

    function handleSearchInput() {
      clearTimeout(searchDebounce);
      searchDebounce = setTimeout(performSearch, 300);
    }

    async function performSearch() {
      const q = document.getElementById('portal-search-input').value.trim();
      if (q === '') {
        renderGridCards(portalEvents);
        return;
      }
      try {
        const json = await apiFetch('events_portal&search=' + encodeURIComponent(q));
        if (json && json.success) {
          renderGridCards(json.data);
        }
      } catch (e) {}
    }

    async function openAllEventsModal() {
      const modal = document.getElementById('modal-all-events');
      const container = document.getElementById('modal-events-list');
      container.innerHTML = '<p class="text-center text-slate-400 py-4 text-xs">Memuat arsip...</p>';
      modal.classList.remove('hidden');

      try {
        const json = await apiFetch('events_all');
        if (json && json.success) {
          archiveEvents = json.data;
          renderModalEvents(archiveEvents);
        }
      } catch (err) {
        container.innerHTML = '<p class="text-center text-red-500 py-4 text-xs">Gagal memuat arsip kegiatan.</p>';
      }
    }

    function renderModalEvents(events) {
      const container = document.getElementById('modal-events-list');
      container.innerHTML = '';

      if (!events || events.length === 0) {
        container.innerHTML = '<p class="text-center text-slate-400 py-4 text-xs">Tidak ada kegiatan ditemukan.</p>';
        return;
      }

      events.forEach(evt => {
        const div = document.createElement('div');
        div.className = 'p-3 rounded-xl border border-slate-200 hover:border-sinjai-cyan bg-slate-50/70 hover:bg-blue-50/40 transition-all flex justify-between items-center text-xs gap-3';
        
        const meetingCode = evt.meeting_code || evt.id;
        const meetingUrl = API_ROOT + `meeting/${meetingCode}`;

        div.innerHTML = `
          <div class="min-w-0 flex-1">
            <h4 class="font-bold text-slate-800 text-xs truncate">${evt.title}</h4>
            <p class="text-slate-500 text-[11px] mt-0.5">
              <i class="fa-regular fa-calendar text-sinjai-cyan mr-1"></i>${evt.event_date || 'Hari ini'} • 
              <i class="fa-solid fa-location-dot text-sinjai-cyan mr-1"></i>${evt.location || 'Sinjai'}
            </p>
          </div>
          ${evt.is_active ? `
            <a href="${meetingUrl}" class="px-3.5 py-1.5 bg-[#00a8e8] hover:bg-[#008dc6] text-white font-bold rounded-lg text-[11px] shrink-0 shadow-xs">Hadir</a>
          ` : '<span class="px-2.5 py-1 bg-slate-200 text-slate-500 font-semibold rounded text-[10px] shrink-0">Selesai</span>'}
        `;
        container.appendChild(div);
      });
    }

    function filterModalEvents() {
      const q = document.getElementById('modal-search-input').value.toLowerCase();
      renderModalEvents(archiveEvents.filter(e => e.title.toLowerCase().includes(q) || (e.location && e.location.toLowerCase().includes(q))));
    }

    function closeAllEventsModal() {
      document.getElementById('modal-all-events').classList.add('hidden');
    }
  </script>
</body>
</html>
