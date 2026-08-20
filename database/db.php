<?php
// 100% Fail-Safe SQLite Database Connection Header
$dbPath = __DIR__ . '/kehadiran.db';
$pdo = null;

try {
    if (extension_loaded('pdo_sqlite')) {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Di shared hosting cPanel, mode WAL sering gagal karena file -wal/-shm
        // tidak bisa dibuat. Pakai journal DELETE yang selalu didukung, dan beri
        // waktu tunggu agar tulis bersamaan tidak langsung "database is locked".
        @$pdo->exec("PRAGMA journal_mode = DELETE");
        @$pdo->exec("PRAGMA busy_timeout = 5000");

        // Auto Create Tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                meeting_code TEXT UNIQUE,
                title TEXT NOT NULL,
                event_date DATE NOT NULL,
                start_time TIME,
                end_time TIME,
                location TEXT,
                description TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

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
            );

            CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                name TEXT NOT NULL,
                role TEXT DEFAULT 'admin',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Ensure default Super Admin exists
        $stmtAdmin = $pdo->query("SELECT COUNT(*) as cnt FROM admins");
        if ($stmtAdmin && $stmtAdmin->fetch()['cnt'] == 0) {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmtInsert = $pdo->prepare("INSERT INTO admins (username, password_hash, name, role) VALUES ('admin', ?, 'Administrator Utama', 'super_admin')");
            $stmtInsert->execute([$hash]);
        }

        // Ensure default SKM setting exists
        $stmtSkm = $pdo->query("SELECT COUNT(*) as cnt FROM settings WHERE key = 'skm_url'");
        if ($stmtSkm && $stmtSkm->fetch()['cnt'] == 0) {
            $stmtSkmIns = $pdo->prepare("INSERT INTO settings (key, value) VALUES ('skm_url', ?)");
            $stmtSkmIns->execute(['https://kehadiran.sinjaikab.go.id/skm']);
        }

        // Ensure sample events exist
        $stmtEvent = $pdo->query("SELECT COUNT(*) as cnt FROM events");
        if ($stmtEvent && $stmtEvent->fetch()['cnt'] == 0) {
            $today = date('Y-m-d');
            $stmtInsertE = $pdo->prepare("INSERT INTO events (meeting_code, title, event_date, start_time, end_time, location, description, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsertE->execute(['Mjk4NDM', 'Rapat Koordinasi Awal Pengembangan Dashboard Kinerja OPD', $today, '09:00', '12:00', 'Ruang Rapat Pola Kantor Bupati Sinjai', 'Rapat koordinasi awal pengembangan sistem dashboard kinerja daerah', 1]);
            $stmtInsertE->execute(['Mjk4NDN', 'Rapat Koordinasi Rancangan Rencana Aksi SPI Tahun 2026', $today, '08:00', '16:00', 'Aula Gedung Pertemuan Kab. Sinjai', 'Penyusunan Rencana Aksi Sistem Pengendalian Intern Pemerintah', 1]);
            $stmtInsertE->execute(['Mjk4NDO', 'Sosialisasi Penguatan Tata Kelola Pelayanan Publik Digital', $today, '12:00', '15:00', 'Aula Kantor Diskominfo Sinjai', 'Sosialisasi penguatan tata kelola pelayanan publik terpadu', 1]);
            $stmtInsertE->execute(['Mjk4NDP', 'Rapat RPP Sistem Jaminan Pensiun dan Hari Tua Pegawai', $today, '13:00', '16:00', 'Command Center Pemkab Sinjai', 'Pembahasan rancangan peraturan perlindungan pensiun', 1]);
        }
    }
} catch (Exception $e) {
    $pdo = null;
}

return $pdo;
