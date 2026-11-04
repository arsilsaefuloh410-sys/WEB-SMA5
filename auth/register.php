<?php
/**
 * Pendaftaran Akun Guru Baru (Verifikasi Email + Persetujuan Admin)
 *
 * Langkah 1 (halaman ini): formulir pendaftaran diisi. Data disimpan sementara
 * di tabel registration_verifications dan tautan verifikasi dikirim ke email.
 * Langkah 2 (verify_email.php): pendaftar membuka tautan dari inbox, akun dibuat
 * berstatus 'pending' menunggu persetujuan administrator.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

// Jika sudah login, alihkan
if (is_logged_in()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

/**
 * Mengirim ulang tautan verifikasi untuk email yang sudah pernah mengisi formulir.
 * Token lama dibatalkan dan diganti token baru.
 * @return array [status => bool, message => string, debug_url => string]
 */
function resend_registration_verification(PDO $pdo, string $email): array {
    $stmt = $pdo->prepare("SELECT * FROM registration_verifications WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['status' => false, 'message' => 'Data pendaftaran untuk email tersebut tidak ditemukan. Silakan isi formulir pendaftaran dari awal.', 'debug_url' => ''];
    }

    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $now = date('Y-m-d H:i:s');
    $expires_at = date('Y-m-d H:i:s', time() + REGISTRATION_VERIFY_EXPIRY_MINUTES * 60);

    $upd = $pdo->prepare("UPDATE registration_verifications SET token_hash = ?, expires_at = ?, used = 0, created_at = ? WHERE id = ?");
    $upd->execute([$token_hash, $expires_at, $now, $row['id']]);

    $verify_url = app_url('auth/verify_email.php?token=' . urlencode($token));
    $subject = 'Verifikasi Email Pendaftaran - ' . APP_NAME;
    $html = registration_verify_email_html($row['nama'], $verify_url, REGISTRATION_VERIFY_EXPIRY_MINUTES);
    $text = "Halo {$row['nama']},\n\n"
        . "Verifikasi email pendaftaran akun guru Anda melalui tautan berikut (berlaku " . REGISTRATION_VERIFY_EXPIRY_MINUTES . " menit):\n"
        . $verify_url . "\n\n"
        . "Jika Anda tidak mendaftar, abaikan email ini.\n"
        . APP_NAME;

    $result = send_mail($email, $subject, $html, $text);
    return [
        'status'    => $result['status'],
        'message'   => $result['status'] ? '' : ($result['message'] ?? ''),
        'debug_url' => $result['status'] ? '' : $verify_url,
    ];
}

$error_message = '';
$success_message = '';
$debug_verify_url = '';
$nama = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'register';

    // --- Kirim ulang tautan verifikasi (tanpa mengisi ulang formulir) ---
    if ($action === 'resend') {
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Format alamat email tidak valid.';
        } else {
            $resend = resend_registration_verification($pdo, $email);
            if ($resend['status']) {
                $success_message = 'Tautan verifikasi baru sudah dikirim ulang ke <strong>' . e($email) . '</strong>. '
                    . 'Silakan periksa kotak masuk (inbox) atau folder spam Anda.';
            } elseif (APP_DEBUG && !empty($resend['debug_url'])) {
                $debug_verify_url = $resend['debug_url'];
                $success_message = 'Pengiriman email gagal (konfigurasi SMTP belum diisi di berkas .env). '
                    . 'Karena mode debug aktif, tautan verifikasi ditampilkan di bawah ini.';
            } else {
                $error_message = 'Email verifikasi gagal dikirim. Silakan coba lagi nanti atau hubungi administrator.';
            }
        }
    } else {
        // --- Pendaftaran baru: validasi formulir ---
        $nama = trim($_POST['nama'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($nama) || empty($email) || empty($password)) {
            $error_message = 'Mohon lengkapi semua data formulir.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Format alamat email tidak valid.';
        } elseif (is_disposable_email($email)) {
            $error_message = 'Alamat email sekali pakai / email sementara tidak diizinkan. '
                . 'Gunakan email aktif milik Anda (misalnya Gmail) agar dapat menerima tautan verifikasi.';
        } elseif (strlen($nama) > 100) {
            $error_message = 'Nama lengkap maksimal 100 karakter.';
        } elseif (!validate_password_strength($password)['valid']) {
            $error_message = validate_password_strength($password)['message'];
        } elseif ($password !== $password_confirm) {
            $error_message = 'Konfirmasi kata sandi tidak cocok.';
        } else {
            // Pastikan email belum menjadi akun (login langsung)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error_message = 'Alamat email ini sudah terdaftar di sistem. Silakan masuk menggunakan email tersebut.';
            } else {
                // Simpan data pendaftar sementara + token verifikasi baru
                $token = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $token);
                $now = date('Y-m-d H:i:s');
                $expires_at = date('Y-m-d H:i:s', time() + REGISTRATION_VERIFY_EXPIRY_MINUTES * 60);
                $hashed = password_hash($password, PASSWORD_BCRYPT);

                // Satu pendaftaran aktif per email: ganti data lama bila ada
                $del = $pdo->prepare("DELETE FROM registration_verifications WHERE email = ?");
                $del->execute([$email]);

                $ins = $pdo->prepare("
                    INSERT INTO registration_verifications (nama, email, password_hash, token_hash, expires_at, used, created_at)
                    VALUES (?, ?, ?, ?, ?, 0, ?)
                ");
                $ins->execute([$nama, $email, $hashed, $token_hash, $expires_at, $now]);

                // Kirim tautan verifikasi ke alamat email pendaftar
                $verify_url = app_url('auth/verify_email.php?token=' . urlencode($token));
                $subject = 'Verifikasi Email Pendaftaran - ' . APP_NAME;
                $html = registration_verify_email_html($nama, $verify_url, REGISTRATION_VERIFY_EXPIRY_MINUTES);
                $text = "Halo {$nama},\n\n"
                    . "Verifikasi email pendaftaran akun guru Anda melalui tautan berikut (berlaku " . REGISTRATION_VERIFY_EXPIRY_MINUTES . " menit):\n"
                    . $verify_url . "\n\n"
                    . "Setelah verifikasi, akun menunggu persetujuan administrator.\n"
                    . "Jika Anda tidak mendaftar, abaikan email ini.\n"
                    . APP_NAME;

                $result = send_mail($email, $subject, $html, $text);

                if ($result['status']) {
                    $success_message = 'Tautan verifikasi sudah dikirim ke <strong>' . e($email) . '</strong>. '
                        . 'Silakan buka inbox (atau folder spam) email Anda lalu tekan tombol '
                        . '<strong>Verifikasi Email Sekarang</strong> untuk menyelesaikan pendaftaran. '
                        . 'Setelah itu akun Anda menunggu persetujuan administrator.';
                } elseif (APP_DEBUG) {
                    // Pengembangan lokal: tampilkan tautan langsung bila email gagal dikirim
                    $debug_verify_url = $verify_url;
                    $success_message = 'Pengiriman email gagal (konfigurasi SMTP belum diisi di berkas .env). '
                        . 'Karena mode debug aktif, tautan verifikasi ditampilkan di bawah ini.';
                } else {
                    $error_message = 'Email verifikasi gagal dikirim karena kesalahan server. Silakan coba lagi nanti atau hubungi administrator.';
                }
            }
        }
    }
}

$page_title = 'Pendaftaran Akun Guru';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card card-modern shadow-sm">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <div class="brand-icon-box mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.5rem; background: #2A4D88; color: #ffffff;">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">Daftar Akun Guru</h4>
                        <p class="text-muted small mb-0">Email Anda akan diverifikasi lebih dulu, lalu akun menunggu persetujuan admin</p>
                    </div>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div class="small"><?= e($error_message) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <!-- Langkah 1 selesai: menunggu verifikasi email -->
                        <div class="alert alert-success d-flex align-items-start mb-4" role="alert">
                            <i class="bi bi-envelope-check-fill me-2 fs-5 mt-1"></i>
                            <div class="small"><?= $success_message ?></div>
                        </div>

                        <?php if (!empty($debug_verify_url)): ?>
                            <div class="alert alert-light border small mb-4 py-2">
                                <strong>Tautan Verifikasi (Debug):</strong><br>
                                <a href="<?= e($debug_verify_url) ?>" style="word-break:break-all;"><?= e($debug_verify_url) ?></a>
                            </div>
                        <?php endif; ?>

                        <div class="text-center">
                            <p class="small text-muted mb-2">Tautan kedaluwarsa atau email belum diterima?</p>
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="action" value="resend">
                                <input type="hidden" name="email" value="<?= e($email) ?>">
                                <button type="submit" class="btn btn-outline-primary btn-sm fw-semibold">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Kirim Ulang Tautan Verifikasi
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form action="" method="POST" autocomplete="off">
                            <input type="hidden" name="action" value="register">

                            <div class="mb-3">
                                <label for="nama" class="form-label fw-semibold small text-dark">Nama Lengkap & Gelar</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="nama" name="nama" value="<?= e($nama) ?>" placeholder="Contoh: Rahmawati, S.Pd." maxlength="100" required autofocus>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold small text-dark">Alamat Email Aktif</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="nama@gmail.com" maxlength="100" required>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">
                                    <i class="bi bi-info-circle me-1"></i>Tautan verifikasi akan dikirim ke email ini. Email sementara/palsu tidak dapat digunakan.
                                </small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label fw-semibold small text-dark">Kata Sandi</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Min. 8 karakter, huruf & angka" minlength="8" maxlength="72" autocomplete="new-password" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="password_confirm" class="form-label fw-semibold small text-dark">Ulangi Kata Sandi</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Ketik ulang sandi" minlength="8" maxlength="72" autocomplete="new-password" required>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">Minimal 8 karakter dengan huruf & angka.</small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                                <i class="bi bi-envelope-check me-1"></i> Kirim Tautan Verifikasi
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="small text-muted mb-0">Sudah memiliki akun terdaftar?
                            <a href="<?= base_url('auth/login.php') ?>" class="fw-semibold text-primary text-decoration-none">Masuk di sini</a>
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
