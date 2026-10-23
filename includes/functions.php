<?php
/**
 * ==========================================================
 * Fungsi Pembantu (Helper Functions)
 * ==========================================================
 * Kumpulan fungsi pendukung keamanan, sesi, dan pengelolaan berkas.
 */

require_once __DIR__ . '/config.php';

/**
 * Mencegah XSS (Cross Site Scripting) pada output HTML
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
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
 * Cek apakah pengguna adalah Guru
 */
function is_guru() {
    return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'guru';
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

