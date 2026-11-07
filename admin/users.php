<?php
/**
 * Kelola Akun Pengguna (Administrator & Guru)
 * Admin memiliki hak akses penuh membuat akun baru, memverifikasi akun guru, dan menentukan peran.
 * Guru hanya memiliki hak akses untuk melihat dan mengunduh bahan ajar (tanpa CRUD).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib Administrator yang sudah login
require_admin();

$active_admin_menu = 'users';
$page_title = 'Kelola Akun Pengguna';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $current_admin_id = current_user()['id'] ?? 0;

    // 1. TAMBAH AKUN BARU OLEH ADMIN
    if ($action === 'add') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = (($_POST['role'] ?? '') === 'admin') ? 'admin' : 'guru';
        $status = (($_POST['status'] ?? '') === 'pending') ? 'pending' : 'aktif';

        if (empty($nama) || empty($email) || empty($password)) {
            set_flash('danger', 'Mohon lengkapi semua kolom yang wajib diisi, termasuk kata sandi awal.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('danger', 'Format alamat email tidak valid.');
        } elseif (!validate_password_strength($password)['valid']) {
            set_flash('danger', e(validate_password_strength($password)['message']));
        } else {
            // Cek duplikasi email
            $stmtCek = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmtCek->execute([$email]);
            if ($stmtCek->fetch()) {
                set_flash('danger', 'Alamat email "<strong>' . e($email) . '</strong>" sudah terdaftar. Gunakan email lain.');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmtAdd = $pdo->prepare("
                    INSERT INTO users (nama, email, password, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmtAdd->execute([$nama, $email, $hash, $role, $status]);

                $roleText = ($role === 'admin') ? 'Administrator (Akses Penuh CRUD)' : 'Guru Pengajar (Akses Materi)';
                set_flash('success', 'Akun baru untuk <strong>' . e($nama) . '</strong> dengan peran <strong>' . $roleText . '</strong> berhasil dibuat!');
            }
        }
        header('Location: ' . base_url('admin/users.php'));
        exit;
    }

    // 2. EDIT DATA AKUN & PERAN (ROLE)
    if ($action === 'edit') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = (($_POST['role'] ?? '') === 'admin') ? 'admin' : 'guru';
        $status = (($_POST['status'] ?? '') === 'pending') ? 'pending' : 'aktif';

        // Proteksi: Admin tidak boleh mengubah peran atau menonaktifkan akun sendiri
        if ($user_id === $current_admin_id) {
            $role = 'admin';
            $status = 'aktif';
        }

        if (empty($nama) || empty($email) || $user_id <= 0) {
            set_flash('danger', 'Nama dan email tidak boleh kosong.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('danger', 'Format alamat email tidak valid.');
        } else {
            // Cek duplikasi email pada user lain
            $stmtCek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $stmtCek->execute([$email, $user_id]);
            if ($stmtCek->fetch()) {
                set_flash('danger', 'Alamat email tersebut sudah dipakai oleh akun lain.');
            } else {
                $stmtEdit = $pdo->prepare("
                    UPDATE users 
                    SET nama = ?, email = ?, role = ?, status = ? 
                    WHERE id = ?
                ");
                $stmtEdit->execute([$nama, $email, $role, $status, $user_id]);
                set_flash('success', 'Data akun <strong>' . e($nama) . '</strong> berhasil diperbarui.');
            }
        }
        header('Location: ' . base_url('admin/users.php'));
        exit;
    }

    // 3. TOGGLE STATUS CEPAT (AKTIFKAN / NONAKTIFKAN / VERIFIKASI)
    if ($action === 'toggle_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);

        if ($user_id === $current_admin_id) {
            set_flash('danger', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        } else {
            $stmtUser = $pdo->prepare("SELECT nama, status FROM users WHERE id = ?");
            $stmtUser->execute([$user_id]);
            $targetUser = $stmtUser->fetch();

            if ($targetUser) {
                $newStatus = ($targetUser['status'] === 'aktif') ? 'pending' : 'aktif';
                $stmtToggle = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmtToggle->execute([$newStatus, $user_id]);

                if ($newStatus === 'aktif') {
                    set_flash('success', 'Akun <strong>' . e($targetUser['nama']) . '</strong> berhasil diverifikasi & diaktifkan.');
                } else {
                    set_flash('warning', 'Akun <strong>' . e($targetUser['nama']) . '</strong> dinonaktifkan.');
                }
            } else {
                set_flash('danger', 'Akun pengguna tidak ditemukan.');
            }
        }
        header('Location: ' . base_url('admin/users.php'));
        exit;
    }

    // 4. RESET KATA SANDI
    if ($action === 'reset_password') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $new_pass = trim($_POST['new_password'] ?? '');

        $strength = validate_password_strength($new_pass);
        if (!$strength['valid']) {
            set_flash('danger', e($strength['message']));
        } else {
            $newHash = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmtReset = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtReset->execute([$newHash, $user_id]);

            if ($stmtReset->rowCount() === 0) {
                set_flash('danger', 'Akun tidak ditemukan sehingga kata sandi tidak dapat direset.');
            } else {
                // Catatan: setelah reset, semua percobaan login gagal untuk akun ini dihapus
                $stmtEmail = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $stmtEmail->execute([$user_id]);
                $reset_email = $stmtEmail->fetchColumn();
                if ($reset_email) {
                    clear_login_attempts($pdo, $reset_email);
                }
                set_flash('success', 'Kata sandi berhasil di-reset. Beritahukan kata sandi baru tersebut kepada pemilik akun secara langsung.');
            }
        }

        header('Location: ' . base_url('admin/users.php'));
        exit;
    }

    // 5. HAPUS AKUN PENGGUNA
    if ($action === 'delete') {
        $user_id = (int)($_POST['user_id'] ?? 0);

        if ($user_id === $current_admin_id) {
            set_flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        } else {
            $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmtDel->execute([$user_id]);
            if ($stmtDel->rowCount() > 0) {
                set_flash('success', 'Akun pengguna berhasil dihapus dari sistem.');
            } else {
                set_flash('danger', 'Akun tidak ditemukan — mungkin sudah dihapus sebelumnya.');
            }
        }
        header('Location: ' . base_url('admin/users.php'));
        exit;
    }
}

// Filter Role & Status Pengguna & Pencarian
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$conditions = [];
$params = [];

if ($filter === 'guru') {
    $conditions[] = "role = 'guru'";
} elseif ($filter === 'admin') {
    $conditions[] = "role = 'admin'";
} elseif ($filter === 'pending') {
    $conditions[] = "status = 'pending'";
} elseif ($filter === 'aktif') {
    $conditions[] = "status = 'aktif'";
}

if (!empty($search)) {
    $conditions[] = "(nama LIKE ? OR email LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql = "SELECT * FROM users";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}
// Tampilkan pending lebih dulu agar admin mudah memverifikasi pendaftar baru
$sql .= " ORDER BY (status = 'pending') DESC, role ASC, id DESC";

$stmtUsers = $pdo->prepare($sql);
$stmtUsers->execute($params);
$daftarPengguna = $stmtUsers->fetchAll();

// Hitung metrik keseluruhan
$totalSemua   = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalGuru    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guru'")->fetchColumn();
$totalAdmin   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$totalPending = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h3 class="fw-bold text-dark mb-0">Kelola Akun Pengguna</h3>
        <p class="text-muted small mb-0">Buat, verifikasi, dan atur peran akun Guru (akses materi) maupun Administrator (akses penuh CRUD).</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary btn-sm fw-semibold d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill"></i> Buat Akun Baru
        </button>
    </div>
</div>

<?php if ($totalPending > 0 && $filter !== 'pending'): ?>
    <!-- Pemberitahuan Verifikasi Pendaftar Baru -->
    <div class="alert alert-warning border-warning d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-xs">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-exclamation fs-4 text-warning-emphasis"></i>
            <div>
                <strong class="text-dark">Terdapat <?= $totalPending ?> akun menunggu persetujuan / nonaktif!</strong>
                <div class="small text-muted">Akun guru dari pendaftaran mandiri (email sudah diverifikasi) maupun akun yang dinonaktifkan menunggu aktivasi Anda agar dapat masuk ke sistem.</div>
            </div>
        </div>
        <a href="<?= base_url('admin/users.php?filter=pending') ?>" class="btn btn-warning btn-sm fw-semibold text-nowrap">
            <i class="bi bi-shield-check me-1"></i> Tinjau & Aktifkan
        </a>
    </div>
<?php endif; ?>

<!-- Informasi Hak Akses Peran -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-3 bg-white border rounded shadow-xs h-100 border-start border-4 border-primary">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-shield-check text-primary fs-5"></i>
                <h6 class="fw-bold text-dark mb-0">Peran: Administrator (Admin)</h6>
            </div>
            <p class="text-muted small mb-0">
                Memiliki seluruh hak akses <strong>CRUD</strong>: Mengelola jenjang kelas, topik bab, mengunggah/menghapus modul PPT/PDF, link referensi, serta membuat & memverifikasi akun guru.
            </p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3 bg-white border rounded shadow-xs h-100 border-start border-4 border-success">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-person-workspace text-success fs-5"></i>
                <h6 class="fw-bold text-dark mb-0">Peran: Guru Pengajar</h6>
            </div>
            <p class="text-muted small mb-0">
                Hanya memiliki hak akses untuk <strong>melihat dan mengunduh bahan ajar</strong> (slide PPT & modul PDF) serta membuka tautan referensi belajar. Tidak memiliki izin mengubah data sistem.
            </p>
        </div>
    </div>
</div>

<!-- Filter Tab & Pencarian Pengguna -->
<div class="card card-modern p-3 mb-4 bg-white">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <!-- Tab Filter -->
        <div class="btn-group btn-group-sm flex-wrap" role="group">
            <a href="<?= base_url('admin/users.php?filter=all' . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Semua (<?= $totalSemua ?>)
            </a>
            <a href="<?= base_url('admin/users.php?filter=guru' . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" class="btn <?= $filter === 'guru' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-person-workspace me-1"></i> Guru (<?= $totalGuru ?>)
            </a>
            <a href="<?= base_url('admin/users.php?filter=admin' . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" class="btn <?= $filter === 'admin' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi bi-shield-lock me-1"></i> Admin (<?= $totalAdmin ?>)
            </a>
            <a href="<?= base_url('admin/users.php?filter=pending' . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" class="btn <?= $filter === 'pending' ? 'btn-warning text-dark fw-semibold' : ($totalPending > 0 ? 'btn-outline-warning text-dark' : 'btn-outline-secondary') ?>">
                <i class="bi bi-hourglass-split me-1"></i> Pending / Verifikasi
                <?php if ($totalPending > 0): ?>
                    <span class="badge bg-danger ms-1 text-white"><?= $totalPending ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Form Pencarian Cepat -->
        <form action="" method="GET" class="d-flex gap-2">
            <?php if (!empty($filter) && $filter !== 'all'): ?>
                <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <?php endif; ?>
            <div class="input-group input-group-sm">
                <input type="text" name="q" class="form-control" placeholder="Cari nama atau email..." value="<?= e($search) ?>" style="min-width: 200px;">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <?php if (!empty($search)): ?>
                <a href="<?= base_url('admin/users.php?filter=' . urlencode($filter)) ?>" class="btn btn-sm btn-outline-secondary" title="Reset Pencarian">
                    <i class="bi bi-x-circle"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
        <span class="text-muted small">
            Menampilkan <strong><?= count($daftarPengguna) ?></strong> akun pengguna
            <?php if (!empty($search)): ?>
                untuk kata kunci "<strong><?= e($search) ?></strong>"
            <?php endif; ?>
        </span>
        <?php if (!empty($search) || $filter !== 'all'): ?>
            <a href="<?= base_url('admin/users.php') ?>" class="small text-decoration-none">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Saringan
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Tabel Pengguna -->
<div class="card card-modern bg-white shadow-sm border-0">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th>Nama Pengguna</th>
                        <th>Alamat Email</th>
                        <th style="width: 220px;" class="text-center">Peran & Hak Akses</th>
                        <th style="width: 130px;" class="text-center">Status</th>
                        <th style="width: 130px;">Dibuat</th>
                        <th style="width: 240px;" class="text-end">Aksi Pengelolaan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarPengguna)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-people display-5 d-block mb-2 text-secondary"></i>
                                Tidak ada akun pengguna ditemukan pada saringan ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarPengguna as $u): 
                            $is_adm = ($u['role'] === 'admin');
                            $is_act = ($u['status'] === 'aktif');
                            $my_id  = current_user()['id'] ?? 0;
                            $is_self = ($u['id'] == $my_id);
                        ?>
                        <tr class="<?= !$is_act ? 'table-warning bg-opacity-10' : '' ?>">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="<?= $is_adm ? 'bg-primary text-white' : 'bg-success text-white' ?> rounded-circle p-2 d-flex align-items-center justify-content-center shadow-xs" style="width: 38px; height: 38px;">
                                        <i class="bi <?= $is_adm ? 'bi-shield-fill' : 'bi-person-workspace' ?> fs-6"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">
                                            <?= e($u['nama']) ?>
                                            <?php if ($is_self): ?>
                                                <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Akun Anda</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?= $is_adm ? 'Pengelola Materi & Sistem' : 'Guru Mata Pelajaran' ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-dark small font-monospace"><?= e($u['email']) ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($is_adm): ?>
                                    <span class="badge bg-primary px-2 py-1">
                                        <i class="bi bi-shield-check me-1"></i> Admin (CRUD Penuh)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                        <i class="bi bi-person-workspace me-1"></i> Guru (Akses Materi)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($is_act): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                        <i class="bi bi-check-circle me-1"></i> Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-2 py-1 fw-semibold">
                                        <i class="bi bi-hourglass-split me-1"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-muted small"><?= date('d/m/Y', strtotime($u['created_at'])) ?></span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <!-- Tombol Verifikasi / Toggle Status Cepat -->
                                    <?php if (!$is_self): ?>
                                        <form action="" method="POST" class="d-inline" data-confirm="<?= e($is_act ? 'Nonaktifkan akun ' . $u['nama'] . '?' : 'Verifikasi dan aktifkan akun ' . $u['nama'] . '?') ?>" onsubmit="return confirm(this.dataset.confirm)">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <?php if ($is_act): ?>
                                                <button type="submit" class="btn btn-outline-warning" title="Nonaktifkan Akun">
                                                    <i class="bi bi-pause-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-success fw-semibold" title="Verifikasi & Aktifkan Akun">
                                                    <i class="bi bi-check2-circle me-1"></i> Aktifkan
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Edit Akun & Role -->
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>" title="Edit Data & Peran">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </button>

                                    <!-- Reset Sandi -->
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#resetPassModal<?= $u['id'] ?>" title="Reset Sandi">
                                        <i class="bi bi-key"></i>
                                    </button>

                                    <!-- Hapus Akun (Disabled jika akun sendiri) -->
                                    <?php if (!$is_self): ?>
                                        <form action="" method="POST" class="d-inline" data-confirm="<?= e('Hapus permanen akun ' . $u['nama'] . ' dari sistem?') ?>" onsubmit="return confirm(this.dataset.confirm)">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus Akun">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Edit Akun & Peran Pengguna -->
                        <div class="modal fade text-start" id="editUserModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="" method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit Akun: <?= e($u['nama']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php if ($is_self): ?>
                                                <div class="alert alert-info py-2 small mb-3">
                                                    <i class="bi bi-info-circle me-1"></i> Anda sedang menyunting profil Anda sendiri. Pilihan peran dan status dikunci agar akses Anda tetap aman.
                                                </div>
                                            <?php endif; ?>

                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Nama Lengkap & Gelar</label>
                                                <input type="text" name="nama" class="form-control" value="<?= e($u['nama']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Alamat Email</label>
                                                <input type="email" name="email" class="form-control" value="<?= e($u['email']) ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold text-primary">Pilihan Peran (Role Pengguna)</label>
                                                <select name="role" class="form-select border-primary" <?= $is_self ? 'disabled' : '' ?> required>
                                                    <option value="guru" <?= $u['role'] === 'guru' ? 'selected' : '' ?>>
                                                        👨‍👫 Guru Pengajar (Hanya Mengakses & Mengunduh Materi)
                                                    </option>
                                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>
                                                        🛡️ Administrator (Akses Penuh CRUD Materi & Akun)
                                                    </option>
                                                </select>
                                                <?php if ($is_self): ?>
                                                    <input type="hidden" name="role" value="admin">
                                                <?php endif; ?>
                                                <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">
                                                    Tentukan apakah pengguna ini hanya membaca materi atau dapat mengelola konten.
                                                </small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Status Akun</label>
                                                <select name="status" class="form-select" <?= $is_self ? 'disabled' : '' ?>>
                                                    <option value="aktif" <?= $u['status'] === 'aktif' ? 'selected' : '' ?>>Aktif (Dapat Masuk)</option>
                                                    <option value="pending" <?= $u['status'] === 'pending' ? 'selected' : '' ?>>Pending / Nonaktif</option>
                                                </select>
                                                <?php if ($is_self): ?>
                                                    <input type="hidden" name="status" value="aktif">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary fw-semibold">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Reset Sandi Pengguna -->
                        <div class="modal fade text-start" id="resetPassModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <form action="" method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

                                        <div class="modal-header">
                                            <h6 class="modal-title fw-bold">Reset Sandi: <?= e($u['nama']) ?></h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="form-label small fw-semibold">Kata Sandi Baru</label>
                                            <input type="password" name="new_password" class="form-control" minlength="8" maxlength="72" autocomplete="new-password" placeholder="Minimal 8 karakter + huruf & angka" required>
                                            <small class="text-muted d-block mt-2" style="font-size: 0.78rem;">
                                                Kata sandi wajib minimal 8 karakter dan mengandung huruf serta angka.
                                            </small>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-warning btn-sm fw-semibold">Reset Sandi</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Akun Baru (Role Guru atau Admin) -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addUserModalLabel">
                        <i class="bi bi-person-plus-fill text-primary me-2"></i>Buat Akun Pengguna Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap & Gelar</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Rahmawati, S.Pd. atau Ir. Bambang, M.T." required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat Email Pengguna</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@sekolah.sch.id" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kata Sandi Awal</label>
                        <input type="password" name="password" class="form-control" minlength="8" maxlength="72" autocomplete="new-password" placeholder="Minimal 8 karakter + huruf & angka" required>
                        <small class="text-muted" style="font-size: 0.78rem;">Kata sandi awal wajib minimal 8 karakter dengan huruf & angka, nanti dapat diganti sendiri oleh pengguna.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-primary">Pilihan Peran (Role Akun)</label>
                        <select name="role" class="form-select border-primary fw-semibold" required>
                            <option value="guru" selected>
                                👨‍👫 Guru Pengajar (Hanya Dapat Mengakses & Mengunduh Materi)
                            </option>
                            <option value="admin">
                                🛡️ Administrator (Akses Penuh CRUD Materi & Akun)
                            </option>
                        </select>
                        <div class="alert alert-light border small mt-2 py-2 mb-0">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            <strong>Catatan:</strong> Akun Guru tidak memiliki akses CRUD apa pun dan hanya dapat menjelajahi materi pembelajaran.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Status Akun</label>
                        <select name="status" class="form-select">
                            <option value="aktif" selected>Langsung Aktif (Siap Masuk)</option>
                            <option value="pending">Pending / Menunggu Verifikasi</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Buat Akun Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
