<?php
/**
 * Kelola Berkas Bahan Ajar & Link Referensi per Topik
 * Pengelolaan terpusat yang praktis dan tidak berbelit-belit untuk Admin
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib Administrator yang sudah login
require_admin();

$topik_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($topik_id <= 0) {
    set_flash('danger', 'ID Topik tidak valid.');
    header('Location: ' . base_url('admin/topik.php'));
    exit;
}

// Ambil data topik
$stmtTopik = $pdo->prepare("
    SELECT t.*, k.nama_kelas, k.tingkat 
    FROM topik t 
    JOIN kelas k ON t.kelas_id = k.id 
    WHERE t.id = ? 
    LIMIT 1
");
$stmtTopik->execute([$topik_id]);
$topik = $stmtTopik->fetch();

if (!$topik) {
    set_flash('danger', 'Topik materi tidak ditemukan.');
    header('Location: ' . base_url('admin/topik.php'));
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    // 1. Upload Berkas Bahan Ajar (PPT / PDF)
    if ($action === 'upload_file') {
        if (!isset($_FILES['file_berkas']) || $_FILES['file_berkas']['error'] === UPLOAD_ERR_NO_FILE) {
            set_flash('danger', 'Silakan pilih berkas PPT atau PDF yang ingin diunggah.');
        } else {
            $upload_result = upload_materi_file($_FILES['file_berkas']);
            
            if ($upload_result['status']) {
                $fileData = $upload_result['data'];
                $userId = current_user()['id'] ?? 1;

                $stmtInsertFile = $pdo->prepare("
                    INSERT INTO file_materi (topik_id, nama_file, path_file, tipe_file, ukuran_file, uploaded_by, uploaded_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmtInsertFile->execute([
                    $topik_id,
                    $fileData['nama_asli'],
                    $fileData['path_file'],
                    $fileData['tipe_file'],
                    $fileData['ukuran_file'],
                    $userId
                ]);

                set_flash('success', 'Berkas "<strong>' . e($fileData['nama_asli']) . '</strong>" berhasil diunggah dan ditambahkan ke topik ini.');
            } else {
                set_flash('danger', $upload_result['message']);
            }
        }
        header('Location: ' . base_url('admin/topik_detail.php?id=' . $topik_id));
        exit;
    }

    // 2. Hapus Berkas Bahan Ajar
    if ($action === 'delete_file') {
        $file_id = (int)($_POST['file_id'] ?? 0);
        $stmtF = $pdo->prepare("SELECT * FROM file_materi WHERE id = ? AND topik_id = ?");
        $stmtF->execute([$file_id, $topik_id]);
        $targetFile = $stmtF->fetch();

        if ($targetFile) {
            // Hapus file dari penyimpanan fisik server
            delete_materi_file($targetFile['path_file']);
            
            // Hapus dari database
            $stmtDel = $pdo->prepare("DELETE FROM file_materi WHERE id = ?");
            $stmtDel->execute([$file_id]);

            set_flash('success', 'Berkas berhasil dihapus.');
        } else {
            set_flash('danger', 'Berkas tidak ditemukan.');
        }
        header('Location: ' . base_url('admin/topik_detail.php?id=' . $topik_id));
        exit;
    }

    // 3. Tambah Link Referensi
    if ($action === 'add_referensi') {
        $judul_link = trim($_POST['judul_link'] ?? '');
        $url = trim($_POST['url'] ?? '');

        if (empty($judul_link) || empty($url)) {
            set_flash('danger', 'Mohon isi judul link dan alamat URL referensi.');
        } else {
            // Pastikan diawali http/https
            if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                $url = "https://" . $url;
            }

            $stmtRef = $pdo->prepare("INSERT INTO referensi (topik_id, judul_link, url) VALUES (?, ?, ?)");
            $stmtRef->execute([$topik_id, $judul_link, $url]);

            set_flash('success', 'Tautan referensi berhasil ditambahkan.');
        }
        header('Location: ' . base_url('admin/topik_detail.php?id=' . $topik_id));
        exit;
    }

    // 4. Hapus Link Referensi
    if ($action === 'delete_referensi') {
        $ref_id = (int)($_POST['ref_id'] ?? 0);
        $stmtDelRef = $pdo->prepare("DELETE FROM referensi WHERE id = ? AND topik_id = ?");
        $stmtDelRef->execute([$ref_id, $topik_id]);

        set_flash('success', 'Tautan referensi berhasil dihapus.');
        header('Location: ' . base_url('admin/topik_detail.php?id=' . $topik_id));
        exit;
    }
}

// Ambil seluruh berkas yang ada pada topik ini
$stmtFiles = $pdo->prepare("SELECT * FROM file_materi WHERE topik_id = ? ORDER BY id DESC");
$stmtFiles->execute([$topik_id]);
$daftarFiles = $stmtFiles->fetchAll();

// Ambil seluruh link referensi pada topik ini
$stmtRefs = $pdo->prepare("SELECT * FROM referensi WHERE topik_id = ? ORDER BY id ASC");
$stmtRefs->execute([$topik_id]);
$daftarRefs = $stmtRefs->fetchAll();

$active_admin_menu = 'topik';
$page_title = 'Kelola Materi: ' . $topik['judul_topik'];

require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Breadcrumb & Header Navigasi -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('admin/topik.php') ?>" class="text-decoration-none">Kelola Topik</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($topik['judul_topik']) ?></li>
            </ol>
        </nav>
        <h3 class="fw-bold text-dark mb-0">
            <span class="badge bg-primary me-2"><?= e($topik['nama_kelas']) ?></span>
            Bab <?= $topik['urutan'] ?>: <?= e($topik['judul_topik']) ?>
        </h3>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="<?= base_url('guru/topik.php?id=' . $topik_id) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-eye me-1"></i> Tampilan Guru
        </a>
        <a href="<?= base_url('admin/topik.php?kelas_id=' . $topik['kelas_id']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<!-- Informasi Ringkas Topik -->
<div class="card card-modern p-3 mb-4 bg-white">
    <div class="row align-items-center">
        <div class="col-md-9">
            <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Deskripsi Ringkas Bab:</span>
            <p class="text-dark mb-0 small">
                <?= nl2br(e($topik['deskripsi'] ?? 'Belum ada deskripsi topik.')) ?>
            </p>
        </div>
        <div class="col-md-3 text-md-end mt-2 mt-md-0">
            <a href="<?= base_url('admin/topik.php') ?>" class="btn btn-sm btn-light border">
                <i class="bi bi-pencil me-1"></i> Ubah Topik Ini
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ========================================================= -->
    <!-- BAGIAN 1: KELOLA BERKAS BAHAN AJAR (PPT & PDF)            -->
    <!-- ========================================================= -->
    <div class="col-lg-6">
        <div class="card card-modern bg-white h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-file-earmark-arrow-up text-danger me-2"></i>Berkas Bahan Ajar
                </h5>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">
                    <?= count($daftarFiles) ?> Berkas
                </span>
            </div>

            <div class="card-body p-4">
                <!-- Form Unggah Berkas Baru -->
                <form action="" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload_file">
                    <input type="file" name="file_berkas" id="file_berkas" class="d-none validate-file-upload" accept=".pdf,.ppt,.pptx">
                    <div class="input-group">
                        <label for="file_berkas" class="btn btn-outline-primary d-inline-flex align-items-center mb-0" style="cursor: pointer;">
                            <i class="bi bi-folder2-open me-1"></i> Pilih Berkas
                        </label>
                        <span class="form-control text-muted text-truncate" data-file-name-for="file_berkas" style="cursor: default;">Belum ada berkas dipilih</span>
                        <button type="submit" class="btn btn-primary fw-semibold">
                            <i class="bi bi-upload me-1"></i> Unggah
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2" style="font-size: 0.78rem;">
                        Format <strong>PDF / PPT / PPTX</strong> &middot; Maks. <strong>20 MB</strong> per file.
                    </small>
                </form>

                <!-- Daftar Berkas Terunggah -->
                <hr class="my-4">
                <?php if (empty($daftarFiles)): ?>
                    <p class="text-muted small text-center py-3 mb-0">
                        <i class="bi bi-inbox me-1"></i>Belum ada berkas untuk bab ini.
                    </p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($daftarFiles as $f): 
                            $ext = strtolower($f['tipe_file']);
                            $is_pdf = ($ext === 'pdf');
                            $badge = get_file_badge($ext);
                            $f_url = base_url('guru/preview.php?id=' . $f['id']);
                            $f_download = base_url('guru/unduh.php?id=' . $f['id']);
                        ?>
                        <div class="p-3 border rounded d-flex align-items-center justify-content-between bg-white hover-shadow">
                            <div class="d-flex align-items-center gap-2 overflow-hidden pe-2">
                                <i class="bi <?= $badge['icon'] ?> fs-4 <?= $is_pdf ? 'text-danger' : 'text-warning' ?>"></i>
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-dark small text-truncate" title="<?= e($f['nama_file']) ?>">
                                        <?= e($f['nama_file']) ?>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        <?= format_bytes($f['ukuran_file']) ?> • <?= date('d/m/Y', strtotime($f['uploaded_at'])) ?>
                                    </small>
                                </div>
                            </div>

                            <div class="d-flex gap-1 flex-shrink-0">
                                <?php if ($is_pdf): ?>
                                    <a href="<?= $f_url ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Pratinjau PDF">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= $f_download ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="Unduh Berkas">
                                    <i class="bi bi-download"></i>
                                </a>
                                <form action="" method="POST" class="d-inline" onsubmit="return confirm('Hapus berkas ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_file">
                                    <input type="hidden" name="file_id" value="<?= $f['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Hapus Berkas">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- BAGIAN 2: KELOLA LINK REFERENSI EKSTERNAL                 -->
    <!-- ========================================================= -->
    <div class="col-lg-6">
        <div class="card card-modern bg-white h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-link-45deg text-info me-2"></i>Tautan Referensi Eksternal
                </h5>
                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-10">
                    <?= count($daftarRefs) ?> Tautan
                </span>
            </div>

            <div class="card-body p-4">
                <!-- Form Tambah Link Referensi Baru -->
                <form action="" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_referensi">

                    <div class="mb-2">
                        <input type="text" name="judul_link" class="form-control" placeholder="Judul tautan (misal: Buku Panduan Kemdikbud)" required>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-globe2"></i></span>
                        <input type="url" name="url" class="form-control" placeholder="https://contoh-link-belajar.com" required>
                        <button type="submit" class="btn btn-primary fw-semibold px-3">
                            <i class="bi bi-plus-lg me-1"></i> Tambah
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2" style="font-size: 0.78rem;">
                        Gunakan link yang valid agar guru dapat membukanya di tab baru.
                    </small>
                </form>

                <!-- Daftar Link Referensi Terpasang -->
                <hr class="my-4">
                <?php if (empty($daftarRefs)): ?>
                    <p class="text-muted small text-center py-3 mb-0">
                        <i class="bi bi-inbox me-1"></i>Belum ada tautan pada bab ini.
                    </p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($daftarRefs as $ref): ?>
                        <div class="p-3 border rounded d-flex align-items-center justify-content-between bg-white hover-shadow">
                            <div class="overflow-hidden pe-2">
                                <div class="fw-bold text-dark small text-truncate">
                                    <?= e($ref['judul_link']) ?>
                                </div>
                                <div class="text-muted small text-truncate" style="font-size: 0.75rem; max-width: 320px;">
                                    <?= e($ref['url']) ?>
                                </div>
                            </div>

                            <div class="d-flex gap-1 flex-shrink-0">
                                <a href="<?= e($ref['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info py-1 px-2" title="Buka dan tes tautan">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                <form action="" method="POST" class="d-inline" onsubmit="return confirm('Hapus tautan ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_referensi">
                                    <input type="hidden" name="ref_id" value="<?= $ref['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Hapus Tautan">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[type="file"][id]').forEach(function (input) {
    var label = document.querySelector('[data-file-name-for="' + input.id + '"]');
    if (!label) return;
    var apply = function () {
        var hasFile = input.files && input.files.length > 0;
        label.textContent = hasFile ? input.files[0].name : 'Belum ada berkas dipilih';
        label.classList.toggle('text-muted', !hasFile);
        label.classList.toggle('fw-semibold', hasFile);
        label.classList.toggle('text-dark', hasFile);
    };
    input.addEventListener('change', function () {
        apply();
        setTimeout(apply, 0);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
