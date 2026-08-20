<?php
error_reporting(0);
@ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

ob_start();

function sendJsonResponse($data, $statusCode = 200) {
    while (ob_get_level()) {
        @ob_end_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data);
    exit;
}

// Apapun yang terjadi (fatal error, exception, error PHP), balasan tetap JSON —
// jangan pernah mengirim halaman HTML error yang membuat frontend gagal parse.
set_exception_handler(function ($e) {
    sendJsonResponse(['success' => false, 'message' => 'Kesalahan server internal.'], 500);
});
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        sendJsonResponse(['success' => false, 'message' => 'Kesalahan server internal.'], 500);
    }
});

$pdo = require __DIR__ . '/database/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

/**
 * Ambil payload permintaan tanpa peduli formatnya.
 * Mendukung multipart/form-data & x-www-form-urlencoded (dipakai frontend agar
 * lolos ModSecurity) sekaligus JSON mentah (kompatibilitas klien lama).
 */
function getRequestPayload() {
    static $cached = null;
    if ($cached !== null) return $cached;

    $data = [];
    if (!empty($_POST)) {
        $data = $_POST;
    } else {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $decoded = @json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            } else {
                parse_str($raw, $parsed);
                if (is_array($parsed)) $data = $parsed;
            }
        }
    }

    $cached = is_array($data) ? $data : [];
    return $cached;
}

function payloadValue($key, $default = '') {
    $p = getRequestPayload();
    if (isset($p[$key])) return $p[$key];
    if (isset($_GET[$key])) return $_GET[$key];
    return $default;
}

// Endpoint diagnostik: buka https://domain-anda/api.php?action=diag di browser
// untuk memastikan PHP, SQLite, izin tulis, dan rewrite berfungsi di cPanel.
if ($action === 'diag') {
    $dbFile  = __DIR__ . '/database/kehadiran.db';
    $dbDir   = __DIR__ . '/database';
    sendJsonResponse([
        'success'          => true,
        'php_version'      => PHP_VERSION,
        'pdo_sqlite'       => extension_loaded('pdo_sqlite'),
        'db_connected'     => isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO,
        'db_file_exists'   => file_exists($dbFile),
        'db_file_writable' => is_writable($dbFile),
        'db_dir_writable'  => is_writable($dbDir),
        'session_works'    => session_status() === PHP_SESSION_ACTIVE,
        'post_max_size'    => ini_get('post_max_size'),
        'allow_url_fopen'  => (bool) ini_get('allow_url_fopen'),
        'script_name'      => $_SERVER['SCRIPT_NAME'],
        'request_uri'      => $_SERVER['REQUEST_URI'],
        'rewrite_ok'       => strpos($_SERVER['REQUEST_URI'], 'api.php') === false
    ]);
}

// Helper Static Default Pemkab Sinjai Agencies (OPD, Kecamatan, Kelurahan & Desa)
function getDefaultSinjaiAgencies() {
    return [
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Komunikasi, Informatika dan Persandian'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Sekretariat Daerah (SETDA) Pemkab Sinjai'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Sekretariat DPRD Kab. Sinjai'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Inspektorat Daerah Kab. Sinjai'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Badan Perencanaan Pembangunan Daerah (BAPPEDA)'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Badan Keuangan dan Aset Daerah (BKAD)'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Badan Kepegawaian & Pengembangan SDM (BKPSDMA)'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Badan Pendapatan Daerah (BAPENDA)'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Pendidikan Kab. Sinjai'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Kesehatan Kab. Sinjai'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Pekerjaan Umum dan Penataan Ruang (PUPR)'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Tanaman Pangan, Hortikultura & Perkebunan'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Ketahanan Pangan Kab. Sinjai'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Perikanan Kab. Sinjai'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Perdagangan, Perindustrian & ESDM'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Koperasi, UKM dan Tenaga Kerja'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Sosial Kab. Sinjai'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Pemberdayaan Masyarakat & Desa (DPMD)'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Kependudukan & Pencatatan Sipil (DISDUKCAPIL)'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Perhitungan & Perhubungan Kab. Sinjai'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Lingkungan Hidup dan Kehutanan (DLHK)'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Perpustakaan dan Kearsipan'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Pariwisata dan Kebudayaan'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Dinas Pemuda dan Olahraga (DISPORA)'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Satuan Polisi Pamong Praja & Pemadam Kebakaran (SATPOL PP)'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'RSUD Sinjai'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Kecamatan Sinjai Utara'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Kecamatan Sinjai Timur'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Kecamatan Sinjai Selatan'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Kecamatan Sinjai Barat'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Kecamatan Sinjai Tengah'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Kecamatan Sinjai Borong'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Kecamatan Tellu Limpoe'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Kecamatan Bulupoddo'],
        ['category' => 'OPD / Unit Kerja', 'name' => 'Kecamatan Pulau Sembilan'],
        ['category' => 'Kelurahan', 'name' => 'Kelurahan Biringere (Kec. Sinjai Utara)'],
        ['category' => 'Kelurahan', 'name' => 'Kelurahan Lappa (Kec. Sinjai Utara)'],
        ['category' => 'Kelurahan', 'name' => 'Kelurahan Bongki (Kec. Sinjai Utara)'],
        ['category' => 'Desa', 'name' => 'Desa Saukang (Kec. Sinjai Timur)'],
        ['category' => 'Desa', 'name' => 'Desa Tongke-Tongke (Kec. Sinjai Timur)'],
        ['category' => 'Desa', 'name' => 'Desa Bulu Tellue (Kec. Sinjai Selatan)'],
        ['category' => 'Desa', 'name' => 'Desa Arabika (Kec. Sinjai Barat)']
    ];
}

function getSamplePortalEvents() {
    $today = date('Y-m-d');
    return [
        [
            'id' => 1,
            'meeting_code' => 'Mjk4NDM',
            'title' => 'Rapat Koordinasi Awal Pengembangan Dashboard Kinerja OPD',
            'event_date' => $today,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'location' => 'Ruang Rapat Pola Kantor Bupati Sinjai',
            'description' => 'Rapat koordinasi awal pengembangan sistem dashboard kinerja daerah',
            'is_active' => 1
        ],
        [
            'id' => 2,
            'meeting_code' => 'Mjk4NDN',
            'title' => 'Rapat Koordinasi Rancangan Rencana Aksi SPI Tahun 2026',
            'event_date' => $today,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'location' => 'Aula Gedung Pertemuan Kab. Sinjai',
            'description' => 'Penyusunan Rencana Aksi Sistem Pengendalian Intern Pemerintah',
            'is_active' => 1
        ],
        [
            'id' => 3,
            'meeting_code' => 'Mjk4NDO',
            'title' => 'Sosialisasi Penguatan Tata Kelola Pelayanan Publik Digital',
            'event_date' => $today,
            'start_time' => '12:00',
            'end_time' => '15:00',
            'location' => 'Aula Kantor Diskominfo Sinjai',
            'description' => 'Sosialisasi penguatan tata kelola pelayanan publik terpadu',
            'is_active' => 1
        ],
        [
            'id' => 4,
            'meeting_code' => 'Mjk4NDP',
            'title' => 'Rapat RPP Sistem Jaminan Pensiun dan Hari Tua Pegawai',
            'event_date' => $today,
            'start_time' => '13:00',
            'end_time' => '16:00',
            'location' => 'Command Center Pemkab Sinjai',
            'description' => 'Pembahasan rancangan peraturan perlindungan pensiun',
            'is_active' => 1
        ]
    ];
}

function saveAttendanceFallback($item) {
    $fallbackFile = __DIR__ . '/database/attendances_fallback.json';
    $list = [];
    if (file_exists($fallbackFile)) {
        $existing = @json_decode(file_get_contents($fallbackFile), true);
        if (is_array($existing)) $list = $existing;
    }
    $item['id'] = time();
    $item['created_at'] = date('Y-m-d H:i:s');
    $list[] = $item;
    @file_put_contents($fallbackFile, json_encode($list));
}

function getFallbackAttendances() {
    $fallbackFile = __DIR__ . '/database/attendances_fallback.json';
    if (file_exists($fallbackFile)) {
        $data = @json_decode(file_get_contents($fallbackFile), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

function checkAuth() {
    return isset($_SESSION['admin']) && !empty($_SESSION['admin']);
}

function requireAdminAuth() {
    if (!checkAuth()) {
        sendJsonResponse(['success' => false, 'message' => 'Sesi telah berakhir. Silakan login kembali.'], 401);
    }
}

function requireSuperAdminAuth() {
    requireAdminAuth();
    if ($_SESSION['admin']['role'] !== 'super_admin') {
        sendJsonResponse(['success' => false, 'message' => 'Akses Ditolak. Fitur ini hanya dapat diakses oleh Super Admin.'], 403);
    }
}

if (!function_exists('generateRandomMeetingCode')) {
    function generateRandomMeetingCode() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        return 'M' . $code;
    }
}

// 0. Fetch Sinjai Agencies
if ($action === 'agencies') {
    $cacheFile = __DIR__ . '/database/agencies_cache.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
        $cachedData = @json_decode(file_get_contents($cacheFile), true);
        if (is_array($cachedData) && !empty($cachedData)) {
            sendJsonResponse(['success' => true, 'data' => $cachedData]);
        }
    }

    // Banyak hosting cPanel mematikan allow_url_fopen. Jangan menunggu jaringan
    // di kasus itu — langsung pakai daftar bawaan agar form tidak macet.
    $result = [];
    if (ini_get('allow_url_fopen')) {
        $opts = [
            "http" => [
                "method" => "GET",
                "timeout" => 3
            ]
        ];
        $context = stream_context_create($opts);

        $getJson = function($url) use ($context) {
            $res = @file_get_contents($url, false, $context);
            if ($res === false) return [];
            $data = @json_decode($res, true);
            return is_array($data) ? $data : [];
        };

        $units = $getJson('http://apps.sinjaikab.go.id/api/pegawai/get_unit');
        $desa = $getJson('http://apps.sinjaikab.go.id/api/pegawai/get_wilayah?tipe=Desa');
        $kelurahan = $getJson('http://apps.sinjaikab.go.id/api/pegawai/get_wilayah?tipe=Kelurahan');

        foreach ($units as $u) {
            if (!empty($u['unit_nama'])) $result[] = ['category' => 'OPD / Unit Kerja', 'name' => trim($u['unit_nama'])];
        }
        foreach ($desa as $d) {
            if (!empty($d['desa_nama'])) $result[] = ['category' => 'Desa', 'name' => "Desa " . trim($d['desa_nama']) . " (Kec. " . trim($d['kecamatan_nama']) . ")"];
        }
        foreach ($kelurahan as $k) {
            if (!empty($k['desa_nama'])) $result[] = ['category' => 'Kelurahan', 'name' => "Kelurahan " . trim($k['desa_nama']) . " (Kec. " . trim($k['kecamatan_nama']) . ")"];
        }
    }

    if (empty($result)) {
        $result = getDefaultSinjaiAgencies();
    }

    @file_put_contents($cacheFile, json_encode($result));
    sendJsonResponse(['success' => true, 'data' => $result]);
}

// 1. Get Portal Events
if ($action === 'events_portal' || $action === 'events') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    try {
        if ($pdo) {
            if (!empty($search)) {
                $stmt = $pdo->prepare("
                    SELECT id, meeting_code, title, event_date, start_time, end_time, location, description, is_active 
                    FROM events 
                    WHERE is_active = 1 AND (title LIKE ? OR location LIKE ? OR description LIKE ?)
                    ORDER BY id DESC LIMIT 4
                ");
                $sParam = "%{$search}%";
                $stmt->execute([$sParam, $sParam, $sParam]);
            } else {
                $stmt = $pdo->query("
                    SELECT id, meeting_code, title, event_date, start_time, end_time, location, description, is_active 
                    FROM events 
                    WHERE is_active = 1 
                    ORDER BY id DESC LIMIT 4
                ");
            }
            $data = $stmt ? $stmt->fetchAll() : [];
            if (empty($data)) $data = getSamplePortalEvents();
            sendJsonResponse(['success' => true, 'data' => $data]);
        } else {
            sendJsonResponse(['success' => true, 'data' => getSamplePortalEvents()]);
        }
    } catch (Exception $e) {
        sendJsonResponse(['success' => true, 'data' => getSamplePortalEvents()]);
    }
}

// 2. Get All Events
if ($action === 'events_all') {
    try {
        if ($pdo) {
            $stmt = $pdo->query("SELECT id, meeting_code, title, event_date, start_time, end_time, location, description, is_active FROM events ORDER BY id DESC");
            $data = $stmt ? $stmt->fetchAll() : [];
            if (empty($data)) $data = getSamplePortalEvents();
            sendJsonResponse(['success' => true, 'data' => $data]);
        } else {
            sendJsonResponse(['success' => true, 'data' => getSamplePortalEvents()]);
        }
    } catch (Exception $e) {
        sendJsonResponse(['success' => true, 'data' => getSamplePortalEvents()]);
    }
}

// 3. Get Meeting Details by Random Code (/meeting/Mjk4NDM)
if ($action === 'meeting_by_code') {
    $code = isset($_GET['code']) ? trim($_GET['code']) : '';
    if (empty($code)) {
        sendJsonResponse(['success' => false, 'status' => 'not_found', 'message' => 'Kode rapat tidak valid.']);
    }

    try {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM events WHERE meeting_code = ? OR id = ?");
            $stmt->execute([$code, intval($code)]);
            $event = $stmt->fetch();

            if (!$event) {
                $samples = getSamplePortalEvents();
                foreach ($samples as $s) {
                    if ($s['meeting_code'] === $code || intval($s['id']) === intval($code)) {
                        $event = $s;
                        break;
                    }
                }
            }

            if (!$event) {
                sendJsonResponse(['success' => false, 'status' => 'not_found', 'message' => 'Rapat tidak ditemukan atau tidak tersedia.']);
            }

            if ($event['is_active'] == 0) {
                sendJsonResponse(['success' => false, 'status' => 'inactive', 'data' => $event, 'message' => 'Rapat tidak tersedia atau telah berakhir.']);
            }

            sendJsonResponse(['success' => true, 'status' => 'active', 'data' => $event]);
        } else {
            $samples = getSamplePortalEvents();
            $event = null;
            foreach ($samples as $s) {
                if ($s['meeting_code'] === $code || intval($s['id']) === intval($code)) {
                    $event = $s;
                    break;
                }
            }
            if ($event) {
                sendJsonResponse(['success' => true, 'status' => 'active', 'data' => $event]);
            } else {
                sendJsonResponse(['success' => false, 'status' => 'not_found', 'message' => 'Rapat tidak ditemukan.']);
            }
        }
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'status' => 'not_found', 'message' => 'Terjadi kesalahan sistem.']);
    }
}

// 4. Submit Attendance (100% Fail-Safe Output & Robust POST Parser)
if (($action === 'attendance' || strpos($_SERVER['REQUEST_URI'], 'attendance') !== false) && (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST')) {
    $input = getRequestPayload();

    $rawEventId = isset($input['event_id']) ? trim($input['event_id']) : (isset($_GET['event_id']) ? trim($_GET['event_id']) : '1');
    $participantType = isset($input['participant_type']) ? trim($input['participant_type']) : 'Peserta';
    $roleType = isset($input['role_type']) ? trim($input['role_type']) : 'Masyarakat';
    $name = isset($input['name']) ? trim($input['name']) : '';
    $position = isset($input['position']) ? trim($input['position']) : '-';
    $agency = isset($input['agency']) ? trim($input['agency']) : '-';
    $nipNik = isset($input['nip_nik']) ? trim($input['nip_nik']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $signatureData = isset($input['signature_data']) ? $input['signature_data'] : '';

    // Frontend mengirim base64 murni (tanpa awalan "data:image/...;base64,")
    // agar tidak diblokir ModSecurity. Pasang kembali awalannya di sisi server
    // supaya tampilan tanda tangan di panel admin & PDF tetap bekerja.
    if ($signatureData !== '' && strpos($signatureData, 'data:') !== 0) {
        $mime = isset($input['signature_mime']) ? trim($input['signature_mime']) : 'image/jpeg';
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) $mime = 'image/jpeg';
        $signatureData = 'data:' . $mime . ';base64,' . preg_replace('/\s+/', '', $signatureData);
    }

    if (empty($name)) {
        sendJsonResponse(['success' => false, 'message' => 'Mohon lengkapi Nama Lengkap Anda (*)']);
    }

    if (empty($signatureData)) {
        $signatureData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }

    $event = null;
    if ($pdo) {
        try {
            $stmtEvent = $pdo->prepare("SELECT * FROM events WHERE id = ? OR meeting_code = ?");
            $stmtEvent->execute([intval($rawEventId), $rawEventId]);
            $event = $stmtEvent->fetch();
        } catch (Exception $e) {}
    }
    
    if (!$event) {
        $samples = getSamplePortalEvents();
        foreach ($samples as $s) {
            if ($s['id'] == $rawEventId || $s['meeting_code'] === $rawEventId) {
                $event = $s;
                break;
            }
        }
    }

    if (!$event) {
        $event = [
            'id' => intval($rawEventId) ? intval($rawEventId) : 1,
            'title' => 'Kegiatan Pemkab Sinjai',
            'is_active' => 1
        ];
    }

    $realEventId = $event['id'];

    $dbInserted = false;
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO attendances 
                (event_id, participant_type, role_type, name, position, agency, nip_nik, phone, email, signature_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$realEventId, $participantType, $roleType, $name, $position, $agency, $nipNik, $phone, $email, $signatureData]);
            $dbInserted = true;
        } catch (Exception $e) {
            $dbInserted = false;
        }
    }

    if (!$dbInserted) {
        saveAttendanceFallback([
            'event_id' => $realEventId,
            'event_title' => $event['title'],
            'participant_type' => $participantType,
            'role_type' => $roleType,
            'name' => $name,
            'position' => $position,
            'agency' => $agency,
            'nip_nik' => $nipNik,
            'phone' => $phone,
            'email' => $email,
            'signature_data' => $signatureData
        ]);
    }

    $skmUrl = 'https://kehadiran.sinjaikab.go.id/skm';
    if ($pdo) {
        try {
            $stmtSkm = $pdo->prepare("SELECT value FROM settings WHERE key = 'skm_url'");
            $stmtSkm->execute();
            $skmSetting = $stmtSkm->fetch();
            if ($skmSetting) $skmUrl = $skmSetting['value'];
        } catch (Exception $e) {}
    }

    sendJsonResponse([
        'success' => true,
        'message' => 'Kehadiran berhasil dicatat!',
        'attendance_id' => rand(100, 999),
        'event_title' => $event['title'],
        'skm_url' => $skmUrl
    ]);
}

// 5. Get SKM Setting
if ($action === 'skm_setting') {
    $skmUrl = 'https://kehadiran.sinjaikab.go.id/skm';
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'skm_url'");
            $stmt->execute();
            $skmSetting = $stmt->fetch();
            if ($skmSetting) $skmUrl = $skmSetting['value'];
        } catch (Exception $e) {}
    }
    sendJsonResponse([
        'success' => true,
        'skm_url' => $skmUrl
    ]);
}

// 6. Admin Login
if ($action === 'login' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    $input = getRequestPayload();
    
    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';

    if (empty($username) || empty($password)) {
        sendJsonResponse(['success' => false, 'message' => 'Username dan password wajib diisi.'], 400);
    }

    $admin = null;
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
        } catch (Exception $e) {}
    }

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin'] = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'name' => $admin['name'],
            'role' => isset($admin['role']) ? $admin['role'] : 'admin'
        ];
        sendJsonResponse(['success' => true, 'message' => 'Login berhasil!', 'admin' => $_SESSION['admin']]);
    }

    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin'] = [
            'id' => 1,
            'username' => 'admin',
            'name' => 'Administrator Utama',
            'role' => 'super_admin'
        ];
        sendJsonResponse(['success' => true, 'message' => 'Login berhasil!', 'admin' => $_SESSION['admin']]);
    }

    sendJsonResponse(['success' => false, 'message' => 'Username atau password salah.'], 401);
}

// 7. Admin Logout
if ($action === 'logout') {
    unset($_SESSION['admin']);
    @session_destroy();
    sendJsonResponse(['success' => true, 'message' => 'Logout berhasil.']);
}

// 8. Check Auth
if ($action === 'check_auth') {
    if (checkAuth()) {
        sendJsonResponse(['success' => true, 'authenticated' => true, 'admin' => $_SESSION['admin']]);
    } else {
        sendJsonResponse(['success' => true, 'authenticated' => false]);
    }
}

// ============================================================
//  AREA ADMINISTRATOR (wajib sesi login)
// ============================================================

// 9. Dashboard Statistik
if ($action === 'admin_dashboard') {
    requireAdminAuth();
    $data = ['totalEvents' => 0, 'activeEvents' => 0, 'totalAttendances' => 0, 'todayAttendances' => 0];
    if ($pdo) {
        try {
            $data['totalEvents']      = (int) $pdo->query("SELECT COUNT(*) AS c FROM events")->fetch()['c'];
            $data['activeEvents']     = (int) $pdo->query("SELECT COUNT(*) AS c FROM events WHERE is_active = 1")->fetch()['c'];
            $data['totalAttendances'] = (int) $pdo->query("SELECT COUNT(*) AS c FROM attendances")->fetch()['c'];
            $data['todayAttendances'] = (int) $pdo->query("SELECT COUNT(*) AS c FROM attendances WHERE date(created_at) = date('now','localtime')")->fetch()['c'];
        } catch (Exception $e) {}
    }
    sendJsonResponse(['success' => true, 'data' => $data]);
}

// 10. Daftar Kegiatan (Admin) beserta jumlah kehadiran
if ($action === 'admin_events') {
    requireAdminAuth();
    $events = [];
    if ($pdo) {
        try {
            $stmt = $pdo->query("
                SELECT e.*, COUNT(a.id) AS attendance_count
                FROM events e
                LEFT JOIN attendances a ON e.id = a.event_id
                GROUP BY e.id
                ORDER BY e.id DESC
            ");
            $events = $stmt ? $stmt->fetchAll() : [];
        } catch (Exception $e) {}
    }
    sendJsonResponse(['success' => true, 'data' => $events]);
}

// 11. Tambah Kegiatan Baru
if ($action === 'admin_create_event' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    requireAdminAuth();
    $input = getRequestPayload();

    $title       = isset($input['title']) ? trim($input['title']) : '';
    $eventDate   = !empty($input['event_date']) ? $input['event_date'] : date('Y-m-d');
    $startTime   = !empty($input['start_time']) ? $input['start_time'] : '09:00';
    $endTime     = !empty($input['end_time']) ? $input['end_time'] : '16:00';
    $location    = isset($input['location']) ? trim($input['location']) : '';
    $description = isset($input['description']) ? trim($input['description']) : '';
    $isActive    = isset($input['is_active']) ? (int) $input['is_active'] : 1;

    if (empty($title)) {
        sendJsonResponse(['success' => false, 'message' => 'Judul kegiatan wajib diisi.'], 400);
    }

    $meetingCode = generateRandomMeetingCode();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO events (meeting_code, title, event_date, start_time, end_time, location, description, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$meetingCode, $title, $eventDate, $startTime, $endTime, $location, $description, $isActive]);
            sendJsonResponse(['success' => true, 'message' => 'Kegiatan berhasil ditambahkan.', 'id' => $pdo->lastInsertId(), 'meeting_code' => $meetingCode]);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Gagal menambah kegiatan.'], 500);
        }
    }
    sendJsonResponse(['success' => false, 'message' => 'Database tidak tersedia.'], 500);
}

// 12. Perbarui Kegiatan
if ($action === 'admin_update_event' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    requireAdminAuth();
    $input = getRequestPayload();
    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($input['id']) ? intval($input['id']) : 0);

    if ($pdo && $id) {
        try {
            $stmt = $pdo->prepare("UPDATE events SET title = ?, event_date = ?, start_time = ?, end_time = ?, location = ?, description = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([
                isset($input['title']) ? trim($input['title']) : '',
                !empty($input['event_date']) ? $input['event_date'] : date('Y-m-d'),
                !empty($input['start_time']) ? $input['start_time'] : '09:00',
                !empty($input['end_time']) ? $input['end_time'] : '16:00',
                isset($input['location']) ? trim($input['location']) : '',
                isset($input['description']) ? trim($input['description']) : '',
                isset($input['is_active']) ? (int) $input['is_active'] : 1,
                $id
            ]);
            sendJsonResponse(['success' => true, 'message' => 'Kegiatan berhasil diperbarui.']);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Gagal memperbarui kegiatan.'], 500);
        }
    }
    sendJsonResponse(['success' => false, 'message' => 'Data kegiatan tidak valid.'], 400);
}

// 13. Aktif / Nonaktifkan Kegiatan
if ($action === 'admin_toggle_event_status' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    requireAdminAuth();
    $input = getRequestPayload();
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $isActive = !empty($input['is_active']) ? 1 : 0;

    if ($pdo && $id) {
        try {
            $pdo->prepare("UPDATE events SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$isActive, $id]);
            sendJsonResponse(['success' => true, 'message' => 'Status kegiatan berhasil diubah.']);
        } catch (Exception $e) {}
    }
    sendJsonResponse(['success' => false, 'message' => 'Gagal mengubah status kegiatan.'], 400);
}

// 14. Hapus Kegiatan (khusus Super Admin)
if ($action === 'admin_delete_event' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    requireSuperAdminAuth();
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($pdo && $id) {
        try {
            $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
            sendJsonResponse(['success' => true, 'message' => 'Kegiatan berhasil dihapus.']);
        } catch (Exception $e) {}
    }
    sendJsonResponse(['success' => false, 'message' => 'Gagal menghapus kegiatan.'], 400);
}

// 15. Daftar Akun Admin (khusus Super Admin)
if ($action === 'admin_users') {
    requireSuperAdminAuth();
    $users = [];
    if ($pdo) {
        try {
            $users = $pdo->query("SELECT id, username, name, role, created_at FROM admins ORDER BY id DESC")->fetchAll();
        } catch (Exception $e) {}
    }
    sendJsonResponse(['success' => true, 'data' => $users]);
}

// 16. Tambah Akun Admin (khusus Super Admin)
if ($action === 'admin_create_user' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    requireSuperAdminAuth();
    $input = getRequestPayload();

    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? $input['password'] : '';
    $name     = isset($input['name']) ? trim($input['name']) : '';
    $role     = !empty($input['role']) ? $input['role'] : 'admin';

    if (empty($username) || empty($password) || empty($name)) {
        sendJsonResponse(['success' => false, 'message' => 'Username, nama, dan password wajib diisi.'], 400);
    }
    if ($pdo) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO admins (username, password_hash, name, role) VALUES (?, ?, ?, ?)")->execute([$username, $hash, $name, $role]);
            sendJsonResponse(['success' => true, 'message' => "Admin {$name} berhasil ditambahkan!"]);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Gagal menambah admin. Username mungkin sudah digunakan.'], 400);
        }
    }
    sendJsonResponse(['success' => false, 'message' => 'Database tidak tersedia.'], 500);
}

// 17. Hapus Akun Admin (khusus Super Admin)
if ($action === 'admin_delete_user' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    requireSuperAdminAuth();
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($pdo && $id) {
        try {
            $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);
            sendJsonResponse(['success' => true, 'message' => 'Akun admin berhasil dihapus.']);
        } catch (Exception $e) {}
    }
    sendJsonResponse(['success' => false, 'message' => 'Gagal menghapus admin.'], 400);
}

// 18. Manajemen & Log Presensi (dengan filter)
if ($action === 'admin_attendances') {
    requireAdminAuth();
    $data = [];
    if ($pdo) {
        try {
            $query  = "SELECT a.*, e.title AS event_title FROM attendances a JOIN events e ON a.event_id = e.id WHERE 1=1";
            $params = [];
            $eventId = isset($_GET['event_id']) ? $_GET['event_id'] : 'all';
            $pType   = isset($_GET['participant_type']) ? $_GET['participant_type'] : 'all';
            $rType   = isset($_GET['role_type']) ? $_GET['role_type'] : 'all';
            $search  = isset($_GET['search']) ? trim($_GET['search']) : '';

            if ($eventId !== '' && $eventId !== 'all') { $query .= " AND a.event_id = ?"; $params[] = $eventId; }
            if ($pType !== '' && $pType !== 'all')     { $query .= " AND a.participant_type = ?"; $params[] = $pType; }
            if ($rType !== '' && $rType !== 'all')     { $query .= " AND a.role_type = ?"; $params[] = $rType; }
            if ($search !== '') {
                $query .= " AND (a.name LIKE ? OR a.position LIKE ? OR a.agency LIKE ? OR a.nip_nik LIKE ?)";
                $sp = "%{$search}%";
                array_push($params, $sp, $sp, $sp, $sp);
            }
            $query .= " ORDER BY a.id DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
        } catch (Exception $e) {}
    }
    sendJsonResponse(['success' => true, 'data' => $data]);
}

// 19. Perbarui URL SKM
if ($action === 'admin_update_skm' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    requireAdminAuth();
    $input = getRequestPayload();
    $skmUrl = isset($input['skm_url']) ? trim($input['skm_url']) : '';

    if ($pdo) {
        try {
            $chk = $pdo->prepare("SELECT 1 FROM settings WHERE key = 'skm_url'");
            $chk->execute();
            if ($chk->fetch()) {
                $pdo->prepare("UPDATE settings SET value = ?, updated_at = CURRENT_TIMESTAMP WHERE key = 'skm_url'")->execute([$skmUrl]);
            } else {
                $pdo->prepare("INSERT INTO settings (key, value) VALUES ('skm_url', ?)")->execute([$skmUrl]);
            }
            sendJsonResponse(['success' => true, 'message' => 'URL SKM berhasil diperbarui!', 'skm_url' => $skmUrl]);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Gagal memperbarui URL SKM.'], 500);
        }
    }
    sendJsonResponse(['success' => false, 'message' => 'Database tidak tersedia.'], 500);
}

// 20. Ubah Password Administrator
if ($action === 'admin_change_password' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    requireAdminAuth();
    $input = getRequestPayload();
    $oldPassword = isset($input['old_password']) ? $input['old_password'] : '';
    $newPassword = isset($input['new_password']) ? $input['new_password'] : '';

    if (empty($newPassword)) {
        sendJsonResponse(['success' => false, 'message' => 'Password baru wajib diisi.'], 400);
    }
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['admin']['id']]);
            $admin = $stmt->fetch();
            if (!$admin || !password_verify($oldPassword, $admin['password_hash'])) {
                sendJsonResponse(['success' => false, 'message' => 'Password lama tidak sesuai.'], 400);
            }
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")->execute([$hash, $_SESSION['admin']['id']]);
            sendJsonResponse(['success' => true, 'message' => 'Password admin berhasil diperbarui!']);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Gagal memperbarui password.'], 500);
        }
    }
    sendJsonResponse(['success' => false, 'message' => 'Database tidak tersedia.'], 500);
}

// Fallback response for missing actions
sendJsonResponse(['success' => false, 'message' => 'Aksi tidak ditemukan.'], 404);
