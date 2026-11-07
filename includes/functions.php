<?php
/**
 * ==========================================================
 * Fungsi Pembantu (Helper Functions)
 * ==========================================================
 * Kumpulan fungsi pendukung keamanan, sesi, dan pengelolaan berkas.
 */

require_once __DIR__ . '/config.php';

// --- Polyfill mbstring (bila ekstensi tidak aktif di hosting) ---
if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null): int {
        return count(preg_split('//u', (string)$string, -1, PREG_SPLIT_NO_EMPTY));
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null): string {
        $chars = preg_split('//u', (string)$string, -1, PREG_SPLIT_NO_EMPTY);
        if ($length === null) {
            return implode('', array_slice($chars, (int)$start));
        }
        return implode('', array_slice($chars, (int)$start, (int)$length));
    }
}

/**
 * Mencegah XSS (Cross Site Scripting) pada output HTML
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Mengambil (atau membuat) token CSRF sesi
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Menghasilkan tag input tersembunyi berisi token CSRF
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Memverifikasi token CSRF pada request POST
 */
function csrf_check(): bool {
    $token = (string)($_POST['csrf_token'] ?? '');
    $expected = (string)($_SESSION['csrf_token'] ?? '');
    return $expected !== '' && hash_equals($expected, $token);
}

/**
 * Menolak request POST yang token CSRF-nya tidak valid
 */
function require_csrf(): void {
    if (!csrf_check()) {
        set_flash('danger', 'Token keamanan kedaluwarsa atau tidak valid. Silakan muat ulang halaman dan coba lagi.');
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? base_url('index.php')));
        exit;
    }
}

/**
 * Cek apakah pengguna sudah login
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Mengambil data pengguna saat ini
 */
function current_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * Cek apakah pengguna adalah Administrator
 */
function is_admin() {
    return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

/**
 * Memuat ulang data pengguna dari database ke sesi.
 * Mengembalikan false + memusnahkan sesi bila akun sudah dihapus/dinonaktifkan.
 */
function refresh_session_user(PDO $pdo): bool {
    if (!is_logged_in()) {
        return false;
    }
    try {
        $stmt = $pdo->prepare("SELECT id, nama, email, role, status FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user || $user['status'] !== 'aktif') {
            $_SESSION = [];
            session_destroy();
            return false;
        }
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'nama'  => $user['nama'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
        return true;
    } catch (Exception $e) {
        return true; // DB bermasalah — jangan paksa logout agar halaman tetap terjangkau
    }
}

/**
 * Pembatasan akses: wajib login
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('warning', 'Silakan masuk (login) terlebih dahulu untuk mengakses halaman tersebut.');
        header('Location: ' . base_url('auth/login.php'));
        exit;
    }
    // Cek ulang status akun dari DB (admin bisa menonaktifkan di tengah sesi)
    $pdoRef = $GLOBALS['pdo'] ?? null;
    if ($pdoRef instanceof PDO && !refresh_session_user($pdoRef)) {
        set_flash('warning', 'Sesi Anda berakhir karena akun tidak lagi aktif. Silakan masuk kembali.');
        header('Location: ' . base_url('auth/login.php'));
        exit;
    }
}

/**
 * Pembatasan akses: wajib Administrator
 * Guru tidak memiliki akses CRUD dan akan dialihkan ke portal materi
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash('danger', '<strong>Akses Ditolak!</strong> Akun Guru hanya memiliki izin untuk melihat dan mengunduh materi bahan ajar. Hak akses pengelolaan (CRUD) khusus untuk Administrator.');
        header('Location: ' . base_url('guru/index.php'));
        exit;
    }
}

/**
 * Menyimpan pesan notifikasi sementara (Flash Message)
 * @param string $type success | danger | warning | info
 * @param string $message Isi pesan
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Mengambil dan menghapus pesan notifikasi sementara
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Merender alert HTML Bootstrap jika ada flash message
 */
function render_flash() {
    $flash = get_flash();
    if (!$flash) return '';
    
    $icons = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-octagon-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'info'    => 'bi-info-circle-fill'
    ];
    $icon = $icons[$flash['type']] ?? 'bi-info-circle-fill';
    
    return '
    <div class="alert alert-' . e($flash['type']) . ' alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
        <i class="bi ' . $icon . ' me-2 fs-5"></i>
        <div>' . $flash['message'] . '</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

/**
 * Mengubah ukuran bytes ke format yang mudah dibaca (KB / MB)
 */
function format_bytes($bytes, $precision = 1) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Mendapatkan ikon dan warna badge berdasarkan tipe berkas
 */
function get_file_badge($extension) {
    $ext = strtolower($extension);
    switch ($ext) {
        case 'pdf':
            return [
                'icon' => 'bi-filetype-pdf',
                'bg' => 'bg-danger text-white',
                'label' => 'Dokumen PDF',
                'badge_class' => 'badge-pdf'
            ];
        case 'ppt':
        case 'pptx':
            return [
                'icon' => 'bi-filetype-pptx',
                'bg' => 'bg-warning text-dark',
                'label' => 'Slide Presentasi',
                'badge_class' => 'badge-ppt'
            ];
        default:
            return [
                'icon' => 'bi-file-earmark',
                'bg' => 'bg-secondary text-white',
                'label' => strtoupper($ext),
                'badge_class' => 'badge-other'
            ];
    }
}

/**
 * Fungsi proses unggah file materi
 * @param array $file_post $_FILES['nama_field']
 * @return array [status => bool, message => string, data => array]
 */
function upload_materi_file($file_post) {
    if (!isset($file_post) || $file_post['error'] === UPLOAD_ERR_NO_FILE) {
        return ['status' => false, 'message' => 'Pilih file terlebih dahulu untuk diunggah.'];
    }

    if ($file_post['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'message' => 'Terjadi kesalahan saat mengunggah berkas (Kode Error: ' . $file_post['error'] . ').'];
    }

    // Cek batas ukuran
    if ($file_post['size'] > MAX_FILE_SIZE) {
        return ['status' => false, 'message' => 'Ukuran berkas melebihi batas maksimal (' . format_bytes(MAX_FILE_SIZE) . ').'];
    }

    // Validasi ekstensi
    $original_name = $file_post['name'];
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['status' => false, 'message' => 'Format berkas tidak diizinkan! Hanya diperbolehkan format PDF, PPT, dan PPTX.'];
    }

    // Buat nama berkas baru yang aman dan unik
    $clean_base = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
    $clean_base = substr($clean_base, 0, 40); // batasi panjang
    $new_file_name = time() . '_' . $clean_base . '.' . $ext;

    // Pastikan folder uploads/materi/ ada
    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0777, true);
    }

    $target_path = UPLOAD_DIR . $new_file_name;
    $relative_path = 'uploads/materi/' . $new_file_name;

    if (!move_uploaded_file($file_post['tmp_name'], $target_path)) {
        return ['status' => false, 'message' => 'Gagal memindahkan file ke server. Periksa izin folder uploads.'];
    }

    return [
        'status' => true,
        'message' => 'Berkas berhasil diunggah.',
        'data' => [
            'nama_asli' => $original_name,
            'nama_file' => $new_file_name,
            'path_file' => $relative_path,
            'tipe_file' => $ext,
            'ukuran_file' => (int)$file_post['size']
        ]
    ];
}

/**
 * Menghapus berkas fisik di server secara aman
 */
function delete_materi_file($relative_path) {
    if (empty($relative_path)) return false;
    $full_path = __DIR__ . '/../' . ltrim($relative_path, '/');
    if (file_exists($full_path) && is_file($full_path)) {
        return @unlink($full_path);
    }
    return false;
}

/**
 * Mengambil ringkasan statistik untuk dashboard
 */
function get_app_stats($pdo) {
    $stats = [
        'total_kelas' => 0,
        'total_topik' => 0,
        'total_referensi' => 0,
        'total_file' => 0,
        'total_unduhan' => 0,
        'total_guru' => 0,
        'guru_pending' => 0
    ];

    try {
        $stats['total_kelas'] = (int)$pdo->query("SELECT COUNT(*) FROM kelas")->fetchColumn();
        $stats['total_topik'] = (int)$pdo->query("SELECT COUNT(*) FROM topik")->fetchColumn();
        $stats['total_referensi'] = (int)$pdo->query("SELECT COUNT(*) FROM referensi")->fetchColumn();
        $stats['total_file'] = (int)$pdo->query("SELECT COUNT(*) FROM file_materi")->fetchColumn();
        $stats['total_unduhan'] = (int)($pdo->query("SELECT SUM(download_count) FROM file_materi")->fetchColumn() ?: 0);
        $stats['total_guru'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guru'")->fetchColumn();
        $stats['guru_pending'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guru' AND status = 'pending'")->fetchColumn();
    } catch (Exception $e) {
        // Fallback jika tabel belum diimpor
    }

    return $stats;
}

/**
 * Daftar 8 Elemen Kurikulum Merdeka Informatika SMA
 */
function get_elemen_list() {
    return [
        'BK' => [
            'kode' => 'BK',
            'nama' => 'Berpikir Komputasional',
            'singkat' => 'BK',
            'warna' => '#4338ca',
            'bg' => 'rgba(67, 56, 202, 0.12)',
            'icon' => 'bi-cpu'
        ],
        'TIK' => [
            'kode' => 'TIK',
            'nama' => 'Teknologi Informasi & Komunikasi',
            'singkat' => 'TIK',
            'warna' => '#0284c7',
            'bg' => 'rgba(2, 132, 199, 0.12)',
            'icon' => 'bi-laptop'
        ],
        'SK' => [
            'kode' => 'SK',
            'nama' => 'Sistem Komputer',
            'singkat' => 'SK',
            'warna' => '#0d9488',
            'bg' => 'rgba(13, 148, 136, 0.12)',
            'icon' => 'bi-motherboard'
        ],
        'JKI' => [
            'kode' => 'JKI',
            'nama' => 'Jaringan Komputer & Internet',
            'singkat' => 'JKI',
            'warna' => '#16a34a',
            'bg' => 'rgba(22, 163, 74, 0.12)',
            'icon' => 'bi-wifi'
        ],
        'AD' => [
            'kode' => 'AD',
            'nama' => 'Analisis Data',
            'singkat' => 'AD',
            'warna' => '#d97706',
            'bg' => 'rgba(217, 119, 6, 0.12)',
            'icon' => 'bi-bar-chart'
        ],
        'AP' => [
            'kode' => 'AP',
            'nama' => 'Algoritma & Pemrograman',
            'singkat' => 'AP',
            'warna' => '#7c3aed',
            'bg' => 'rgba(124, 58, 237, 0.12)',
            'icon' => 'bi-code-slash'
        ],
        'DSI' => [
            'kode' => 'DSI',
            'nama' => 'Dampak Sosial Informatika',
            'singkat' => 'DSI',
            'warna' => '#db2777',
            'bg' => 'rgba(219, 39, 119, 0.12)',
            'icon' => 'bi-people'
        ],
        'PLB' => [
            'kode' => 'PLB',
            'nama' => 'Praktik Lintas Bidang',
            'singkat' => 'PLB',
            'warna' => '#059669',
            'bg' => 'rgba(5, 150, 105, 0.12)',
            'icon' => 'bi-puzzle'
        ]
    ];
}

/**
 * Mengambil detail styling dan label suatu elemen kurikulum
 */
function get_elemen_info($kode) {
    $list = get_elemen_list();
    $kode = strtoupper(trim($kode ?? ''));
    return $list[$kode] ?? [
        'kode' => $kode ?: 'UMUM',
        'nama' => 'Informatika Umum',
        'singkat' => $kode ?: 'UMUM',
        'warna' => '#64748b',
        'bg' => 'rgba(100, 116, 139, 0.12)',
        'icon' => 'bi-bookmark'
    ];
}

/**
 * Batas percobaan login gagal & durasi kunci (lockout) anti brute-force
 */
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_MINUTES = 15;

/**
 * Memvalidasi kekuatan kata sandi (minimal 8 karakter + huruf + angka)
 * @return array [valid => bool, message => string]
 */
function validate_password_strength(string $password): array {
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Kata sandi minimal 8 karakter demi keamanan akun.'];
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Kata sandi harus mengandung minimal satu huruf dan satu angka.'];
    }
    if (in_array(strtolower($password), ['12345678', 'password', 'qwerty123', 'admin123', 'guru123'], true)) {
        return ['valid' => false, 'message' => 'Kata sandi terlalu umum dan mudah ditebak. Gunakan kata sandi lain.'];
    }
    return ['valid' => true, 'message' => ''];
}

/**
 * Mendapatkan alamat IP klien (untuk pembatasan percobaan login).
 * Menghormati X-Forwarded-For hanya bila di belakang proxy tepercaya
 * (ditandai TRUSTED_PROXY env / config). Tanpa itu, REMOTE_ADDR dipakai
 * agar lockout tidak mengunci semua pengguna yang berbagi IP proxy.
 */
function get_client_ip(): string {
    if (defined('TRUSTED_PROXY') && TRUSTED_PROXY) {
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($xff !== '') {
            $parts = explode(',', $xff);
            $candidate = trim($parts[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return substr($candidate, 0, 45);
            }
        }
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return substr($ip, 0, 45);
}

/**
 * Mengecek apakah email/IP sedang dikunci akibat terlalu banyak percobaan login gagal.
 * @return int Sisa detik kunci (0 = tidak dikunci)
 */
function get_login_lockout_seconds(PDO $pdo, string $email): int {
    $ip = get_client_ip();
    $since = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_MINUTES * 60);
    try {
        $stmt = $pdo->prepare("SELECT MIN(attempted_at) FROM login_attempts WHERE attempted_at > ? AND (email = ? OR ip_address = ?)");
        $stmt->execute([$since, strtolower($email), $ip]);
        $oldest = $stmt->fetchColumn();
        if (!$oldest) return 0;
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE attempted_at > ? AND (email = ? OR ip_address = ?)");
        $stmtCount->execute([$since, strtolower($email), $ip]);
        if ((int)$stmtCount->fetchColumn() < LOGIN_MAX_ATTEMPTS) return 0;
        $unlock_at = strtotime((string)$oldest) + LOGIN_LOCKOUT_MINUTES * 60;
        return max(1, $unlock_at - time());
    } catch (Exception $e) {
        return 0; // Tabel belum dibuat (migrasi belum dijalankan)
    }
}

/**
 * Mencatat satu percobaan login gagal
 */
function record_login_attempt(PDO $pdo, string $email): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (?, ?, ?)");
        $stmt->execute([strtolower($email), get_client_ip(), date('Y-m-d H:i:s')]);
    } catch (Exception $e) {
        // Tabel belum dibuat — lewati agar login tidak rusak
    }
}

/**
 * Menghapus catatan percobaan gagal (setelah login berhasil)
 * Menghapus juga baris IP yang sama agar kunci-berbasis-IP ikut terbuka
 */
function clear_login_attempts(PDO $pdo, string $email): void {
    try {
        $ip = get_client_ip();
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = ? OR ip_address = ?");
        $stmt->execute([strtolower($email), $ip]);
        // Bersihkan baris lama agar tabel tidak tumbuh tanpa batas
        $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 2 DAY)");
    } catch (Exception $e) {
        // Abaikan bila tabel belum ada
    }
}

/**
 * Memeriksa apakah alamat email berasal dari layanan email sekali pakai
 * (temporary / disposable email) yang sering dipakai untuk mendaftar
 * secara asal-asalan. Daftar domain dilengkapi seperlunya.
 */
function is_disposable_email($email) {
    $email = strtolower(trim((string)$email));
    $at = strrpos($email, '@');
    if ($at === false) {
        return true; // tanpa @ dianggap tidak valid
    }
    $domain = substr($email, $at + 1);

    $blocklist = [
        'mailinator.com', 'tempmail.com', 'temp-mail.org', 'temp-mail.io',
        '10minutemail.com', '10minutemail.net', 'guerrillamail.com',
        'guerrillamailblock.com', 'grr.la', 'yopmail.com', 'yopmail.fr',
        'trashmail.com', 'trashmail.net', 'trashmail.de', 'sharklasers.com',
        'getnada.com', 'maildrop.cc', 'discard.email', 'discardmail.com',
        'fakeinbox.com', 'throwawaymail.com', 'mailnesia.com', 'tempail.com',
        'tempr.email', 'dispostable.com', 'mailcatch.com', 'mytemp.email',
        'moakt.com', 'moakt.cc', 'emailondeck.com', '33mail.com',
        'mailsac.com', 'spam4.me', 'bccto.me', 'chacuo.net', 'linshiyouxiang.net',
        '20minutemail.com', 'mohmal.com', 'kurzepost.de', 'etempmail.com',
        'tempmailo.com', 'mailforspam.com', 'spambox.us', 'trashymail.com',
        'harakirimail.com', 'tmail.ws', 'tmpmail.net', 'tmpmail.org',
    ];

    return in_array($domain, $blocklist, true);
}

