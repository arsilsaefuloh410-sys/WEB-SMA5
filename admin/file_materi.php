<?php
/**
 * Handler Khusus Berkas Materi Ajar
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

// Redirect ke halaman kelola topik utama jika diakses langsung
$topik_id = isset($_REQUEST['topik_id']) ? (int)$_REQUEST['topik_id'] : 0;

if ($topik_id > 0) {
    header('Location: ' . base_url('admin/topik_detail.php?id=' . $topik_id));
} else {
    header('Location: ' . base_url('admin/topik.php'));
}
exit;
