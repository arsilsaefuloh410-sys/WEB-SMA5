<?php
/**
 * GET  /api/admin/users.php?filter=&q=  → daftar akun + ringkasan
 * POST /api/admin/users.php             → action: add | edit | toggle_status |
 *                                               reset_password | delete
 * (mirror logika admin/users.php)
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $admin = require_api_admin($pdo);

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

    if ($search !== '') {
        $conditions[] = "(nama LIKE ? OR email LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $sql = "SELECT id, nama, email, role, status, created_at FROM users";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    // Pending lebih dulu agar admin mudah memverifikasi pendaftar baru
    $sql .= " ORDER BY (status = 'pending') DESC, role ASC, id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $daftar = [];
    foreach ($stmt->fetchAll() as $row) {
        $daftar[] = [
            'id'         => (int)$row['id'],
            'nama'       => $row['nama'],
            'email'      => $row['email'],
            'role'       => $row['role'],
            'status'     => $row['status'],
            'created_at' => $row['created_at'],
            'is_self'    => ((int)$row['id'] === (int)$admin['id']),
        ];
    }

    json_out(['ok' => true, 'data' => [
        'users'         => $daftar,
        'total_semua'   => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_guru'    => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guru'")->fetchColumn(),
        'total_admin'   => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
        'total_pending' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn(),
    ]]);
}

// --- POST ---
$admin = require_api_admin($pdo);
require_method('POST');

$input = api_input();
$action = $input['action'] ?? '';
$current_admin_id = (int)$admin['id'];

if ($action === 'add') {
    $nama = trim($input['nama'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $role = ($input['role'] ?? '') === 'admin' ? 'admin' : 'guru';
    $status = ($input['status'] ?? '') === 'pending' ? 'pending' : 'aktif';

    if ($nama === '' || $email === '' || $password === '') {
        json_err('Mohon lengkapi semua kolom yang wajib diisi, termasuk kata sandi awal.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_err('Format alamat email tidak valid.');
    }
    if (!validate_password_strength($password)['valid']) {
        json_err(validate_password_strength($password)['message']);
    }

    $stmt_cek = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt_cek->execute([$email]);
    if ($stmt_cek->fetch()) {
        json_err('Alamat email "' . $email . '" sudah terdaftar. Gunakan email lain.');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$nama, $email, $hash, $role, $status]);

    json_out(['ok' => true, 'message' => 'Akun baru untuk "' . $nama . '" berhasil dibuat.', 'data' => [
        'id' => (int)$pdo->lastInsertId(),
    ]]);
}

if ($action === 'edit') {
    $user_id = (int)($input['user_id'] ?? 0);
    $nama = trim($input['nama'] ?? '');
    $email = trim($input['email'] ?? '');
    $role = ($input['role'] ?? '') === 'admin' ? 'admin' : 'guru';
    $status = ($input['status'] ?? '') === 'pending' ? 'pending' : 'aktif';

    // Proteksi: admin tidak boleh mengubah peran/menonaktifkan akun sendiri
    if ($user_id === $current_admin_id) {
        $role = 'admin';
        $status = 'aktif';
    }

    if ($nama === '' || $email === '' || $user_id <= 0) {
        json_err('Nama dan email tidak boleh kosong.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_err('Format alamat email tidak valid.');
    }

    $stmt_cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $stmt_cek->execute([$email, $user_id]);
    if ($stmt_cek->fetch()) {
        json_err('Alamat email tersebut sudah dipakai oleh akun lain.');
    }

    $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, role = ?, status = ? WHERE id = ?");
    $stmt->execute([$nama, $email, $role, $status, $user_id]);

    json_out(['ok' => true, 'message' => 'Data akun "' . $nama . '" berhasil diperbarui.']);
}

if ($action === 'toggle_status') {
    $user_id = (int)($input['user_id'] ?? 0);

    if ($user_id === $current_admin_id) {
        json_err('Anda tidak dapat menonaktifkan akun Anda sendiri.');
    }

    $stmt_user = $pdo->prepare("SELECT nama, status FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $target = $stmt_user->fetch();

    if (!$target) {
        json_err('Akun pengguna tidak ditemukan.', 404);
    }

    $new_status = $target['status'] === 'aktif' ? 'pending' : 'aktif';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);

    $msg = $new_status === 'aktif'
        ? 'Akun "' . $target['nama'] . '" berhasil diverifikasi & diaktifkan.'
        : 'Akun "' . $target['nama'] . '" dinonaktifkan.';

    json_out(['ok' => true, 'message' => $msg, 'data' => ['status' => $new_status]]);
}

if ($action === 'reset_password') {
    $user_id = (int)($input['user_id'] ?? 0);
    $new_pass = trim($input['new_password'] ?? '');

    $strength = validate_password_strength($new_pass);
    if (!$strength['valid']) {
        json_err($strength['message']);
    }

    $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$new_hash, $user_id]);

    if ($stmt->rowCount() === 0) {
        json_err('Akun tidak ditemukan sehingga kata sandi tidak dapat direset.', 404);
    }

    // Bersihkan lockout login untuk akun tersebut
    $stmt_email = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt_email->execute([$user_id]);
    $reset_email = $stmt_email->fetchColumn();
    if ($reset_email) {
        clear_login_attempts($pdo, $reset_email);
    }

    json_out(['ok' => true, 'message' => 'Kata sandi berhasil di-reset. Beritahukan kata sandi baru kepada pemilik akun.']);
}

if ($action === 'delete') {
    $user_id = (int)($input['user_id'] ?? 0);

    if ($user_id === $current_admin_id) {
        json_err('Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        json_out(['ok' => true, 'message' => 'Akun pengguna berhasil dihapus dari sistem.']);
    }
    json_err('Akun tidak ditemukan — mungkin sudah dihapus sebelumnya.', 404);
}

json_err('Aksi tidak dikenali.');
