const express = require('express');
const session = require('express-session');
const path = require('path');
const fs = require('fs');
const bcrypt = require('bcryptjs');
const db = require('./database/db');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

app.use(session({
  secret: 'sinjai-kehadiran-secret-key-2026',
  resave: false,
  saveUninitialized: false,
  cookie: { maxAge: 24 * 60 * 60 * 1000 }
}));

// Static files (logos, assets, public)
app.use(express.static(path.join(__dirname, 'public')));
app.use('/assets', express.static(path.join(__dirname, 'public', 'assets')));
app.use('/assets', express.static(path.join(__dirname, 'assets')));

function getDefaultSinjaiAgencies() {
  return [
    { category: 'OPD / Unit Kerja', name: 'Dinas Komunikasi, Informatika dan Persandian' },
    { category: 'OPD / Unit Kerja', name: 'Sekretariat Daerah (SETDA) Pemkab Sinjai' },
    { category: 'OPD / Unit Kerja', name: 'Sekretariat DPRD Kab. Sinjai' },
    { category: 'OPD / Unit Kerja', name: 'Inspektorat Daerah Kab. Sinjai' },
    { category: 'OPD / Unit Kerja', name: 'Badan Perencanaan Pembangunan Daerah (BAPPEDA)' },
    { category: 'OPD / Unit Kerja', name: 'Badan Keuangan dan Aset Daerah (BKAD)' },
    { category: 'OPD / Unit Kerja', name: 'Badan Kepegawaian & Pengembangan SDM (BKPSDMA)' },
    { category: 'OPD / Unit Kerja', name: 'Badan Pendapatan Daerah (BAPENDA)' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Pendidikan Kab. Sinjai' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Kesehatan Kab. Sinjai' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Pekerjaan Umum dan Penataan Ruang (PUPR)' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Tanaman Pangan, Hortikultura & Perkebunan' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Ketahanan Pangan Kab. Sinjai' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Perikanan Kab. Sinjai' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Perdagangan, Perindustrian & ESDM' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Koperasi, UKM dan Tenaga Kerja' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Sosial Kab. Sinjai' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Pemberdayaan Masyarakat & Desa (DPMD)' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Kependudukan & Pencatatan Sipil (DISDUKCAPIL)' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Perhitungan & Perhubungan Kab. Sinjai' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Lingkungan Hidup dan Kehutanan (DLHK)' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Perpustakaan dan Kearsipan' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Pariwisata dan Kebudayaan' },
    { category: 'OPD / Unit Kerja', name: 'Dinas Pemuda dan Olahraga (DISPORA)' },
    { category: 'OPD / Unit Kerja', name: 'Satuan Polisi Pamong Praja & Pemadam Kebakaran (SATPOL PP)' },
    { category: 'OPD / Unit Kerja', name: 'RSUD Sinjai' },
    { category: 'OPD / Unit Kerja', name: 'Kecamatan Sinjai Utara' },
    { category: 'OPD / Unit Kerja', name: 'Kecamatan Sinjai Timur' },
    { category: 'OPD / Unit Kerja', name: 'Kecamatan Sinjai Selatan' },
    { category: 'OPD / Unit Kerja', name: 'Kecamatan Sinjai Barat' },
    { category: 'OPD / Unit Kerja', name: 'Kecamatan Sinjai Tengah' },
    { category: 'OPD / Unit Kerja', name: 'Kecamatan Sinjai Borong' },
    { category: 'OPD / Unit Kerja', name: 'Kecamatan Tellu Limpoe' },
    { category: 'OPD / Unit Kerja', name: 'Kecamatan Bulupoddo' },
    { category: 'OPD / Unit Kerja', name: 'Kecamatan Pulau Sembilan' },
    { category: 'Kelurahan', name: 'Kelurahan Biringere (Kec. Sinjai Utara)' },
    { category: 'Kelurahan', name: 'Kelurahan Lappa (Kec. Sinjai Utara)' },
    { category: 'Kelurahan', name: 'Kelurahan Bongki (Kec. Sinjai Utara)' },
    { category: 'Desa', name: 'Desa Saukang (Kec. Sinjai Timur)' },
    { category: 'Desa', name: 'Desa Tongke-Tongke (Kec. Sinjai Timur)' },
    { category: 'Desa', name: 'Desa Bulu Tellue (Kec. Sinjai Selatan)' },
    { category: 'Desa', name: 'Desa Arabika (Kec. Sinjai Barat)' }
  ];
}

// Helper Random Code Generator
function generateRandomMeetingCode() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let code = '';
  for (let i = 0; i < 6; i++) {
    code += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return 'M' + code;
}

// UNIFIED ENGINE: HANDLE BOTH PHP NATIVE QUERY & EXPRESS REST ENDPOINTS
function handleUnifiedApi(req, res) {
  const action = req.query.action || req.params.action || '';

  if (action === 'events_portal' || action === 'events') {
    const search = req.query.search ? req.query.search.trim() : '';
    if (search) {
      const sParam = `%${search}%`;
      const events = db.prepare(`
        SELECT id, meeting_code, title, event_date, start_time, end_time, location, description, is_active 
        FROM events 
        WHERE is_active = 1 AND (title LIKE ? OR location LIKE ? OR description LIKE ?)
        ORDER BY id DESC LIMIT 4
      `).all(sParam, sParam, sParam);
      return res.json({ success: true, data: events });
    }
    const events = db.prepare(`
      SELECT id, meeting_code, title, event_date, start_time, end_time, location, description, is_active 
      FROM events 
      WHERE is_active = 1 
      ORDER BY id DESC LIMIT 4
    `).all();
    return res.json({ success: true, data: events });
  }

  if (action === 'events_all') {
    const events = db.prepare('SELECT id, meeting_code, title, event_date, start_time, end_time, location, description, is_active FROM events ORDER BY id DESC').all();
    return res.json({ success: true, data: events });
  }

  if (action === 'meeting_by_code') {
    const code = req.query.code || req.params.code;
    const event = db.prepare('SELECT * FROM events WHERE meeting_code = ? OR id = ?').get(code, parseInt(code) || 0);
    if (!event) return res.json({ success: false, status: 'not_found', message: 'Rapat tidak ditemukan.' });
    if (event.is_active === 0) return res.json({ success: false, status: 'inactive', data: event, message: 'Rapat telah berakhir.' });
    return res.json({ success: true, status: 'active', data: event });
  }

  if (action === 'agencies') {
    const cacheFile = path.join(__dirname, 'database', 'agencies_cache.json');
    if (fs.existsSync(cacheFile)) {
      try {
        const cachedData = JSON.parse(fs.readFileSync(cacheFile, 'utf8'));
        if (Array.isArray(cachedData) && cachedData.length > 0) {
          return res.json({ success: true, data: cachedData });
        }
      } catch (e) {}
    }
    return res.json({ success: true, data: getDefaultSinjaiAgencies() });
  }

  if (action === 'attendance' && req.method === 'POST') {
    const { event_id, participant_type, role_type, name, position, agency, nip_nik, phone, email, signature_data } = req.body;
    if (!event_id || !participant_type || !role_type || !name || !position || !agency || !signature_data) {
      return res.status(400).json({ success: false, message: 'Mohon lengkapi seluruh field wajib (*)' });
    }

    const event = db.prepare('SELECT * FROM events WHERE id = ?').get(event_id);
    if (!event || event.is_active === 0) {
      return res.status(404).json({ success: false, message: 'Rapat tidak tersedia atau telah berakhir.' });
    }

    const stmt = db.prepare(`
      INSERT INTO attendances 
      (event_id, participant_type, role_type, name, position, agency, nip_nik, phone, email, signature_data)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `);

    const info = stmt.run(event_id, participant_type, role_type, name, position, agency, nip_nik || '', phone || '', email || '', signature_data);
    const skmSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('skm_url');

    return res.json({
      success: true,
      message: 'Kehadiran berhasil dicatat!',
      attendance_id: info.lastInsertRowid,
      event_title: event.title,
      skm_url: skmSetting ? skmSetting.value : 'https://kehadiran.sinjaikab.go.id/skm'
    });
  }

  if (action === 'skm_setting') {
    const skmSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('skm_url');
    return res.json({
      success: true,
      skm_url: skmSetting ? skmSetting.value : 'https://kehadiran.sinjaikab.go.id/skm'
    });
  }

  if (action === 'login' && req.method === 'POST') {
    const { username, password } = req.body;
    if (!username || !password) return res.status(400).json({ success: false, message: 'Username dan password wajib diisi.' });

    const admin = db.prepare('SELECT * FROM admins WHERE username = ?').get(username);
    if (!admin || !bcrypt.compareSync(password, admin.password_hash)) {
      return res.status(401).json({ success: false, message: 'Username atau password salah.' });
    }

    req.session.admin = {
      id: admin.id,
      username: admin.username,
      name: admin.name,
      role: admin.role || 'admin'
    };

    return res.json({ success: true, message: 'Login berhasil!', admin: req.session.admin });
  }

  if (action === 'logout') {
    req.session.destroy();
    return res.json({ success: true, message: 'Logout berhasil.' });
  }

  if (action === 'check_auth') {
    if (req.session && req.session.admin) {
      return res.json({ success: true, authenticated: true, admin: req.session.admin });
    }
    return res.json({ success: true, authenticated: false });
  }

  // Admin Actions
  if (action === 'admin_dashboard') {
    if (!req.session || !req.session.admin) return res.status(401).json({ success: false });
    const totalEvents = db.prepare('SELECT COUNT(*) as cnt FROM events').get().cnt;
    const activeEvents = db.prepare('SELECT COUNT(*) as cnt FROM events WHERE is_active = 1').get().cnt;
    const totalAttendances = db.prepare('SELECT COUNT(*) as cnt FROM attendances').get().cnt;
    const todayAttendances = db.prepare("SELECT COUNT(*) as cnt FROM attendances WHERE date(created_at) = date('now', 'localtime')").get().cnt;
    return res.json({ success: true, data: { totalEvents, activeEvents, totalAttendances, todayAttendances } });
  }

  if (action === 'admin_events') {
    if (!req.session || !req.session.admin) return res.status(401).json({ success: false });
    const events = db.prepare(`
      SELECT e.*, COUNT(a.id) as attendance_count 
      FROM events e 
      LEFT JOIN attendances a ON e.id = a.event_id 
      GROUP BY e.id 
      ORDER BY e.id DESC
    `).all();
    return res.json({ success: true, data: events });
  }

  if (action === 'admin_create_event' && req.method === 'POST') {
    if (!req.session || !req.session.admin) return res.status(401).json({ success: false });
    const { title, event_date, start_time, end_time, location, description, is_active } = req.body;
    const meetingCode = generateRandomMeetingCode();
    const info = db.prepare('INSERT INTO events (meeting_code, title, event_date, start_time, end_time, location, description, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
      .run(meetingCode, title, event_date || new Date().toISOString().split('T')[0], start_time || '09:00', end_time || '16:00', location || '', description || '', is_active !== undefined ? is_active : 1);
    return res.json({ success: true, message: 'Kegiatan berhasil ditambahkan.', id: info.lastInsertRowid, meeting_code: meetingCode });
  }

  if (action === 'admin_update_event' && req.method === 'POST') {
    if (!req.session || !req.session.admin) return res.status(401).json({ success: false });
    const id = req.query.id || req.body.id;
    const { title, event_date, start_time, end_time, location, description, is_active } = req.body;
    db.prepare('UPDATE events SET title = ?, event_date = ?, start_time = ?, end_time = ?, location = ?, description = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
      .run(title, event_date, start_time, end_time, location, description, is_active, id);
    return res.json({ success: true, message: 'Kegiatan berhasil diperbarui.' });
  }

  if (action === 'admin_toggle_event_status' && req.method === 'POST') {
    if (!req.session || !req.session.admin) return res.status(401).json({ success: false });
    const id = req.query.id;
    const { is_active } = req.body;
    db.prepare('UPDATE events SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?').run(is_active ? 1 : 0, id);
    return res.json({ success: true, message: 'Status kegiatan berhasil diubah.' });
  }

  if (action === 'admin_delete_event' && req.method === 'POST') {
    if (!req.session || !req.session.admin || req.session.admin.role !== 'super_admin') return res.status(403).json({ success: false, message: 'Hanya Super Admin yang berhak menghapus kegiatan.' });
    const id = req.query.id;
    db.prepare('DELETE FROM events WHERE id = ?').run(id);
    return res.json({ success: true, message: 'Kegiatan berhasil dihapus.' });
  }

  if (action === 'admin_users') {
    if (!req.session || !req.session.admin || req.session.admin.role !== 'super_admin') return res.status(403).json({ success: false });
    const users = db.prepare('SELECT id, username, name, role, created_at FROM admins ORDER BY id DESC').all();
    return res.json({ success: true, data: users });
  }

  if (action === 'admin_create_user' && req.method === 'POST') {
    if (!req.session || !req.session.admin || req.session.admin.role !== 'super_admin') return res.status(403).json({ success: false });
    const { username, password, name, role } = req.body;
    const salt = bcrypt.genSaltSync(10);
    const hash = bcrypt.hashSync(password, salt);
    db.prepare('INSERT INTO admins (username, password_hash, name, role) VALUES (?, ?, ?, ?)').run(username, hash, name, role || 'admin');
    return res.json({ success: true, message: `Admin ${name} berhasil ditambahkan!` });
  }

  if (action === 'admin_delete_user' && req.method === 'POST') {
    if (!req.session || !req.session.admin || req.session.admin.role !== 'super_admin') return res.status(403).json({ success: false });
    const id = parseInt(req.query.id);
    db.prepare('DELETE FROM admins WHERE id = ?').run(id);
    return res.json({ success: true, message: 'Akun admin berhasil dihapus.' });
  }

  if (action === 'admin_attendances') {
    if (!req.session || !req.session.admin) return res.status(401).json({ success: false });
    const { event_id, participant_type, role_type, search } = req.query;
    let query = "SELECT a.*, e.title as event_title FROM attendances a JOIN events e ON a.event_id = e.id WHERE 1=1";
    const params = [];
    if (event_id && event_id !== 'all') { query += " AND a.event_id = ?"; params.push(event_id); }
    if (participant_type && participant_type !== 'all') { query += " AND a.participant_type = ?"; params.push(participant_type); }
    if (role_type && role_type !== 'all') { query += " AND a.role_type = ?"; params.push(role_type); }
    if (search) {
      query += " AND (a.name LIKE ? OR a.position LIKE ? OR a.agency LIKE ? OR a.nip_nik LIKE ?)";
      const sParam = `%${search.trim()}%`;
      params.push(sParam, sParam, sParam, sParam);
    }
    query += " ORDER BY a.id DESC";
    const attendances = db.prepare(query).all(...params);
    return res.json({ success: true, data: attendances });
  }

  if (action === 'admin_update_skm' && req.method === 'POST') {
    if (!req.session || !req.session.admin) return res.status(401).json({ success: false });
    const { skm_url } = req.body;
    db.prepare("INSERT INTO settings (key, value, updated_at) VALUES ('skm_url', ?, CURRENT_TIMESTAMP) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP").run(skm_url);
    return res.json({ success: true, message: 'URL SKM berhasil diperbarui!', skm_url });
  }

  if (action === 'admin_change_password' && req.method === 'POST') {
    if (!req.session || !req.session.admin) return res.status(401).json({ success: false });
    const { old_password, new_password } = req.body;
    const admin = db.prepare('SELECT * FROM admins WHERE id = ?').get(req.session.admin.id);
    if (!admin || !bcrypt.compareSync(old_password, admin.password_hash)) {
      return res.status(400).json({ success: false, message: 'Password lama tidak sesuai.' });
    }
    const salt = bcrypt.genSaltSync(10);
    const hash = bcrypt.hashSync(new_password, salt);
    db.prepare('UPDATE admins SET password_hash = ? WHERE id = ?').run(hash, req.session.admin.id);
    return res.json({ success: true, message: 'Password admin berhasil diperbarui!' });
  }

  return res.status(404).json({ success: false, message: 'API tidak ditemukan' });
}

// Bind both Express REST routes & api.php query route
app.all('/api.php', handleUnifiedApi);
app.all('/api/*', handleUnifiedApi);

// ROUTING HALAMAN
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.get('/attamaki', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

app.get('/panel-admin', (req, res) => {
  if (!req.session.admin) {
    return res.redirect('/attamaki');
  }
  res.sendFile(path.join(__dirname, 'public', 'admin.html'));
});

app.get('/meeting/:code', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'meeting.html'));
});

app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.listen(PORT, () => {
  console.log(`🚀 Server Sistem Kehadiran Sinjai berjalan di http://localhost:${PORT}`);
});
