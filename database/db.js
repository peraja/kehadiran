const Database = require('better-sqlite3');
const path = require('path');
const fs = require('fs');
const bcrypt = require('bcryptjs');

const dbDir = path.join(__dirname);
if (!fs.existsSync(dbDir)) {
  fs.mkdirSync(dbDir, { recursive: true });
}

const dbPath = path.join(dbDir, 'kehadiran.db');
const db = new Database(dbPath);

db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

function generateRandomMeetingCode() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let code = '';
  for (let i = 0; i < 6; i++) {
    code += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return 'M' + code;
}

function initDatabase() {
  // 1. Table events
  db.exec(`
    CREATE TABLE IF NOT EXISTS events (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      meeting_code TEXT UNIQUE,
      title TEXT NOT NULL,
      event_date TEXT,
      start_time TEXT,
      end_time TEXT,
      location TEXT,
      description TEXT,
      is_active INTEGER DEFAULT 1,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
  `);

  // Migrate columns if missing
  const columns = db.pragma("table_info(events)");
  const colNames = columns.map(c => c.name);
  if (!colNames.includes('meeting_code')) db.exec("ALTER TABLE events ADD COLUMN meeting_code TEXT");
  if (!colNames.includes('start_time')) db.exec("ALTER TABLE events ADD COLUMN start_time TEXT");
  if (!colNames.includes('end_time')) db.exec("ALTER TABLE events ADD COLUMN end_time TEXT");

  // 2. Table attendances
  db.exec(`
    CREATE TABLE IF NOT EXISTS attendances (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      event_id INTEGER NOT NULL,
      participant_type TEXT NOT NULL,
      role_type TEXT NOT NULL,
      name TEXT NOT NULL,
      position TEXT NOT NULL,
      agency TEXT NOT NULL,
      nip_nik TEXT,
      phone TEXT,
      email TEXT,
      signature_data TEXT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    )
  `);

  // 3. Table settings
  db.exec(`
    CREATE TABLE IF NOT EXISTS settings (
      key TEXT PRIMARY KEY,
      value TEXT NOT NULL,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
  `);

  // 4. Table admins
  db.exec(`
    CREATE TABLE IF NOT EXISTS admins (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT UNIQUE NOT NULL,
      password_hash TEXT NOT NULL,
      name TEXT NOT NULL,
      role TEXT DEFAULT 'admin',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
  `);

  const adminCols = db.pragma("table_info(admins)");
  const adminColNames = adminCols.map(c => c.name);
  if (!adminColNames.includes('role')) db.exec("ALTER TABLE admins ADD COLUMN role TEXT DEFAULT 'admin'");

  // Seed default SKM setting
  const skmSetting = db.prepare('SELECT * FROM settings WHERE key = ?').get('skm_url');
  if (!skmSetting) {
    db.prepare('INSERT INTO settings (key, value) VALUES (?, ?)').run(
      'skm_url',
      'https://kehadiran.sinjaikab.go.id/skm'
    );
  }

  // Seed default Super Admin (admin / admin123)
  const adminAccount = db.prepare('SELECT * FROM admins WHERE username = ?').get('admin');
  if (!adminAccount) {
    const salt = bcrypt.genSaltSync(10);
    const hash = bcrypt.hashSync('admin123', salt);
    db.prepare('INSERT INTO admins (username, password_hash, name, role) VALUES (?, ?, ?, ?)').run(
      'admin',
      hash,
      'Administrator Utama',
      'super_admin'
    );
  } else {
    db.prepare("UPDATE admins SET role = 'super_admin' WHERE username = 'admin'").run();
  }

  // Seed 4 default sample events if empty
  const countEvents = db.prepare('SELECT COUNT(*) as cnt FROM events').get();
  if (countEvents.cnt === 0) {
    const today = new Date().toISOString().split('T')[0];

    const sampleEvents = [
      {
        code: 'Mjk4NDM',
        title: 'Rapat Koordinasi Awal Pengembangan Dashboard Kinerja OPD',
        date: today,
        start: '09:00',
        end: '12:00',
        location: 'Ruang Rapat Pola Kantor Bupati Sinjai',
        desc: 'Rapat koordinasi awal pengembangan sistem dashboard kinerja daerah'
      },
      {
        code: 'Mjk4NDN',
        title: 'Rapat Koordinasi Rancangan Rencana Aksi SPI Tahun 2026',
        date: today,
        start: '08:00',
        end: '16:00',
        location: 'Aula Gedung Pertemuan Kab. Sinjai',
        desc: 'Penyusunan Rencana Aksi Sistem Pengendalian Intern Pemerintah'
      },
      {
        code: 'Mjk4NDO',
        title: 'Sosialisasi Penguatan Tata Kelola Pelayanan Publik Digital',
        date: today,
        start: '12:00',
        end: '15:00',
        location: 'Aula Kantor Diskominfo Sinjai',
        desc: 'Sosialisasi penguatan tata kelola pelayanan publik terpadu'
      },
      {
        code: 'Mjk4NDP',
        title: 'Rapat RPP Sistem Jaminan Pensiun dan Hari Tua Pegawai',
        date: today,
        start: '13:00',
        end: '16:00',
        location: 'Command Center Pemkab Sinjai',
        desc: 'Pembahasan rancangan peraturan perlindungan pensiun'
      }
    ];

    sampleEvents.forEach(se => {
      db.prepare(`
        INSERT INTO events (meeting_code, title, event_date, start_time, end_time, location, description, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
      `).run(se.code, se.title, se.date, se.start, se.end, se.location, se.desc);
    });
  } else {
    // Backfill meeting_code if missing
    const eventsNoCode = db.prepare("SELECT id FROM events WHERE meeting_code IS NULL OR meeting_code = ''").all();
    eventsNoCode.forEach(enc => {
      db.prepare("UPDATE events SET meeting_code = ? WHERE id = ?").run(generateRandomMeetingCode(), enc.id);
    });
  }

  console.log('✅ Database SQLite terinisialisasi dengan sukses.');
}

initDatabase();

module.exports = db;
