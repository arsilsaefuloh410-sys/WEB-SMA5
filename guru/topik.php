<?php
/**
 * Halaman Detail Topik / Materi Ajar Lengkap
 * Berisi Deskripsi, Berkas PPT/PDF, dan Tautan Referensi
 * Menggunakan Palet Warna Referensi: #2A4D88, #B1BBC8, #D9D9D8
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib login terlebih dahulu
require_login();

$topik_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($topik_id <= 0) {
    set_flash('danger', 'Topik materi tidak valid.');
    header('Location: ' . base_url('guru/index.php'));
    exit;
}

// Ambil detail topik dan data kelasnya
$stmtTopik = $pdo->prepare("
    SELECT t.*, k.nama_kelas, k.tingkat, u.nama as nama_pembuat
    FROM topik t
    JOIN kelas k ON t.kelas_id = k.id
    LEFT JOIN users u ON t.created_by = u.id
    WHERE t.id = ?
    LIMIT 1
");
$stmtTopik->execute([$topik_id]);
$topik = $stmtTopik->fetch();

if (!$topik) {
    set_flash('danger', 'Materi topik tidak ditemukan atau telah dihapus.');
    header('Location: ' . base_url('guru/index.php'));
    exit;
}

// Ambil berkas bahan ajar
$stmtFile = $pdo->prepare("SELECT * FROM file_materi WHERE topik_id = ? ORDER BY id ASC");
$stmtFile->execute([$topik_id]);
$daftarFile = $stmtFile->fetchAll();

// Ambil link referensi
$stmtRef = $pdo->prepare("SELECT * FROM referensi WHERE topik_id = ? ORDER BY id ASC");
$stmtRef->execute([$topik_id]);
$daftarReferensi = $stmtRef->fetchAll();

// Ambil daftar bab lain di kelas yang sama untuk sidebar
$stmtBabLain = $pdo->prepare("
    SELECT id, judul_topik, urutan 
    FROM topik 
    WHERE kelas_id = ? AND id != ? 
    ORDER BY urutan ASC, id ASC
");
$stmtBabLain->execute([$topik['kelas_id'], $topik_id]);
$daftarBabLain = $stmtBabLain->fetchAll();

$active_menu = 'kelas';
$page_title = e($topik['judul_topik']) . ' - ' . e($topik['nama_kelas']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb Navigasi -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none" style="color: var(--color-primary);">Beranda</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('guru/index.php') ?>" class="text-decoration-none" style="color: var(--color-primary);">Katalog Kelas</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('guru/kelas.php?tingkat=' . $topik['tingkat']) ?>" class="text-decoration-none" style="color: var(--color-primary);"><?= e($topik['nama_kelas']) ?></a></li>
            <li class="breadcrumb-item active text-truncate text-muted" aria-current="page" style="max-width: 300px;"><?= e($topik['judul_topik']) ?></li>
        </ol>
    </nav>

    <!-- Header Topik -->
    <?php $elemen_info = get_elemen_info($topik['elemen'] ?? 'BK'); ?>
    <div class="card card-modern p-4 mb-4 bg-white" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill px-3 py-1 font-monospace" style="background-color: var(--color-primary); color: #ffffff;">
                        <?= e($topik['nama_kelas']) ?>
                    </span>
                    <span class="badge rounded-pill px-3 py-1" style="background: var(--color-ice); color: var(--color-primary); border: 1px solid var(--border-subtle);">
                        Bab <?= $topik['urutan'] ?? '-' ?>
                    </span>
                    <span class="badge rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" style="background: <?= $elemen_info['bg'] ?>; color: <?= $elemen_info['warna'] ?>; border: 1px solid <?= $elemen_info['warna'] ?>35;">
                        <i class="bi <?= $elemen_info['icon'] ?>"></i> <?= e($elemen_info['kode']) ?> - <?= e($elemen_info['nama']) ?>
                    </span>
                </div>
                <h2 class="fw-bold mb-2" style="color: var(--color-primary);"><?= e($topik['judul_topik']) ?></h2>
                <div class="text-muted small">
                    <i class="bi bi-calendar3 me-1"></i> Ditambahkan pada <?= date('d M Y', strtotime($topik['created_at'])) ?>
                    <?php if (!empty($topik['nama_pembuat'])): ?>
                        • Oleh <span class="fw-medium text-dark"><?= e($topik['nama_pembuat']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <a href="<?= base_url('guru/kelas.php?tingkat=' . $topik['tingkat']) ?>" class="btn-pill btn-pill-outline btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Bab
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Kolom Konten Utama Materi -->
        <div class="col-lg-8">
            
            <!-- 1. Deskripsi & Ringkasan Konsep -->
            <div class="card card-modern p-4 mb-4 bg-white" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom" style="border-color: var(--border-subtle) !important;">
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: var(--color-ice); color: var(--color-primary);">
                        <i class="bi bi-card-text fs-5"></i>
                    </div>
                    <h5 class="fw-bold mb-0" style="color: var(--color-primary);">Ringkasan Materi & Panduan Pembelajaran</h5>
                </div>
                
                <div class="text-dark lh-lg mb-4" style="font-size: 1.02rem;">
                    <?= nl2br(e($topik['deskripsi'] ?? 'Belum ada penjelasan detail untuk topik ini.')) ?>
                </div>

                <!-- Tips Mengajar Ramah Guru Non-IT -->
                <div class="p-3 rounded-3 d-flex gap-3 align-items-start" style="background: var(--color-ice); border: 1px solid var(--border-subtle);">
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: var(--color-primary); color: #ffffff;">
                        <i class="bi bi-lightbulb-fill"></i>
                    </div>
                    <div class="small">
                        <strong class="d-block mb-1" style="color: var(--color-primary);">Tips Mengajar di Kelas:</strong>
                        <span class="text-muted">
                            Untuk topik ini, Bapak/Ibu dapat memulai pembelajaran dengan mengaitkannya ke contoh sederhana sehari-hari terlebih dahulu, kemudian gunakan slide presentasi di bawah sebagai media visual interaktif di kelas.
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. Berkas Bahan Ajar (Slide PPT & Modul PDF) -->
            <div class="card card-modern p-4 mb-4 bg-white" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: var(--border-subtle) !important;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(234, 88, 12, 0.1); color: #ea580c;">
                            <i class="bi bi-cloud-arrow-down fs-5"></i>
                        </div>
                        <h5 class="fw-bold mb-0" style="color: var(--color-primary);">Berkas Bahan Ajar Siap Pakai</h5>
                    </div>
                    <span class="badge rounded-pill px-3 py-1 font-monospace" style="background-color: var(--color-accent-soft); color: var(--color-primary);">
                        <?= count($daftarFile) ?> Berkas Tersedia
                    </span>
                </div>

                <p class="text-muted small mb-3">
                    Unduh modul PDF untuk pegangan belajar atau slide PPT/PPTX untuk langsung ditampilkan melalui proyektor:
                </p>

                <?php if (empty($daftarFile)): ?>
                    <div class="card card-modern text-center py-4" style="background: var(--color-ice); border: 1px dashed var(--border-subtle);">
                        <i class="bi bi-file-earmark-x text-muted display-6 d-block mb-2"></i>
                        <p class="text-muted mb-0 small">Belum ada berkas modul atau slide yang diunggah untuk bab ini.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($daftarFile as $file): 
                            $ext = strtolower($file['tipe_file']);
                            $is_pdf = ($ext === 'pdf');
                            $badge = get_file_badge($ext);
                            $file_url = base_url($file['path_file']);
                            $unduh_url = base_url('guru/unduh.php?id=' . $file['id']);
                        ?>
                        <div class="file-card-item">
                            <div class="d-flex align-items-center gap-3 overflow-hidden">
                                <div class="file-icon-box <?= $is_pdf ? 'file-icon-pdf' : 'file-icon-ppt' ?>">
                                    <i class="bi <?= $badge['icon'] ?>"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= e($file['nama_file']) ?>">
                                        <?= e($file['nama_file']) ?>
                                    </h6>
                                    <div class="d-flex flex-wrap gap-2 align-items-center small text-muted">
                                        <span class="badge rounded-pill <?= $badge['bg'] ?>"><?= $badge['label'] ?></span>
                                        <span><i class="bi bi-hdd me-1"></i><?= format_bytes($file['ukuran_file']) ?></span>
                                        <span><i class="bi bi-download me-1"></i><?= (int)($file['download_count'] ?? 0) ?>x diunduh</span>
                                        <span>• Diunggah <?= date('d/m/Y', strtotime($file['uploaded_at'])) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 ms-3 flex-shrink-0">
                                <?php if ($is_pdf): ?>
                                    <button type="button" class="btn-pill btn-pill-outline btn-sm py-1 px-3 btn-preview-pdf" data-url="<?= $file_url ?>" data-title="<?= e($file['nama_file']) ?>" data-download="<?= $unduh_url ?>" title="Baca langsung di browser">
                                        <i class="bi bi-eye"></i> <span class="d-none d-sm-inline">Baca Modul</span>
                                    </button>
                                <?php endif; ?>
                                <a href="<?= $unduh_url ?>" class="btn-pill btn-pill-primary btn-sm py-1 px-3" title="Unduh berkas ke komputer">
                                    <i class="bi bi-download"></i> Unduh
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 3. Tautan Belajar & Referensi Eksternal -->
            <div class="card card-modern p-4 mb-4 bg-white" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: var(--border-subtle) !important;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: var(--color-ice); color: var(--color-primary);">
                            <i class="bi bi-globe2 fs-5"></i>
                        </div>
                        <h5 class="fw-bold mb-0" style="color: var(--color-primary);">Tautan Referensi & Sumber Belajar</h5>
                    </div>
                    <span class="badge rounded-pill px-3 py-1 font-monospace" style="background-color: var(--color-accent-soft); color: var(--color-primary);">
                        <?= count($daftarReferensi) ?> Tautan
                    </span>
                </div>

                <p class="text-muted small mb-3">
                    Buka tautan website di bawah ini untuk memperdalam wawasan materi, contoh soal, dan modul resmi:
                </p>

                <?php if (empty($daftarReferensi)): ?>
                    <div class="card card-modern text-center py-4" style="background: var(--color-ice); border: 1px dashed var(--border-subtle);">
                        <i class="bi bi-link-45deg text-muted display-6 d-block mb-2"></i>
                        <p class="text-muted mb-0 small">Belum ada link referensi yang ditautkan untuk topik ini.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($daftarReferensi as $ref): ?>
                        <div class="reference-card-item">
                            <div class="overflow-hidden">
                                <h6 class="fw-bold text-dark mb-1">
                                    <a href="<?= e($ref['url']) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none" style="color: var(--color-primary);">
                                        <?= e($ref['judul_link']) ?>
                                    </a>
                                </h6>
                                <div class="text-muted small text-truncate" style="max-width: 450px;">
                                    <i class="bi bi-link me-1" style="color: var(--color-primary);"></i> <?= e($ref['url']) ?>
                                </div>
                            </div>

                            <a href="<?= e($ref['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-pill btn-pill-outline btn-sm py-1 px-3 flex-shrink-0">
                                Buka Tautan <i class="bi bi-box-arrow-up-right ms-1"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Kolom Samping (Bab Lain & Bantuan) -->
        <div class="col-lg-4">
            
            <!-- Widget Bab Lain dalam Kelas yang Sama -->
            <div class="card card-modern p-4 mb-4 bg-white" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
                <h6 class="fw-bold mb-3 pb-2 border-bottom" style="color: var(--color-primary); border-color: var(--border-subtle) !important;">
                    <i class="bi bi-journals me-1"></i> Bab Lain di <?= e($topik['nama_kelas']) ?>
                </h6>

                <?php if (empty($daftarBabLain)): ?>
                    <p class="text-muted small mb-0">Hanya terdapat 1 bab ini di <?= e($topik['nama_kelas']) ?>.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($daftarBabLain as $bl): ?>
                            <a href="<?= base_url('guru/topik.php?id=' . $bl['id']) ?>" class="list-group-item list-group-item-action px-2 py-2 border-0 rounded-3 mb-1 d-flex justify-content-between align-items-center">
                                <span class="small fw-medium text-dark text-truncate pe-2">
                                    <span class="text-muted me-1">Bab <?= $bl['urutan'] ?>:</span> <?= e($bl['judul_topik']) ?>
                                </span>
                                <i class="bi bi-chevron-right text-muted small"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Kartu Bantuan Pengajar -->
            <div class="card card-modern p-4 text-center" style="background: var(--color-ice); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg);">
                <div class="rounded-circle p-2 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: var(--color-primary); color: #ffffff;">
                    <i class="bi bi-question-circle-fill fs-5"></i>
                </div>
                <h6 class="fw-bold" style="color: var(--color-primary);">Butuh Modul Tambahan?</h6>
                <p class="small text-muted mb-3" style="line-height: 1.6;">
                    Jika Bapak/Ibu memerlukan tambahan materi presentasi atau modul praktikum untuk bab ini, silakan ajukan ke pengelola MGMP.
                </p>
                <a href="<?= base_url('guru/kelas.php?tingkat=' . $topik['tingkat']) ?>" class="btn-pill btn-pill-primary btn-sm w-100 justify-content-center">
                    Lihat Semua Bab <?= e($topik['nama_kelas']) ?>
                </a>
            </div>

        </div>
    </div>
</div>

<!-- Modal In-Browser PDF Viewer -->
<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-labelledby="pdfViewerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-lg); overflow: hidden;">
            <div class="modal-header text-white" style="background: var(--color-primary);">
                <div class="d-flex align-items-center gap-2 overflow-hidden pe-3">
                    <i class="bi bi-file-earmark-pdf fs-4 text-warning"></i>
                    <h5 class="modal-title text-truncate fw-bold" id="pdfViewerModalLabel">Pratinjau Modul PDF</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" id="modalDownloadBtn" class="btn btn-sm btn-light text-primary fw-semibold">
                        <i class="bi bi-download me-1"></i> Unduh Modul
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>
            <div class="modal-body p-0 bg-dark" style="height: 75vh;">
                <iframe id="pdfViewerFrame" src="" class="w-100 h-100 border-0" title="PDF Viewer"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pdfButtons = document.querySelectorAll('.btn-preview-pdf');
    const pdfModalEl = document.getElementById('pdfViewerModal');
    const pdfFrame = document.getElementById('pdfViewerFrame');
    const modalTitle = document.getElementById('pdfViewerModalLabel');
    const modalDownloadBtn = document.getElementById('modalDownloadBtn');
    
    if (pdfModalEl) {
        const pdfModal = new bootstrap.Modal(pdfModalEl);
        pdfButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                const title = this.getAttribute('data-title');
                const downloadUrl = this.getAttribute('data-download');
                
                modalTitle.textContent = title;
                modalDownloadBtn.setAttribute('href', downloadUrl);
                pdfFrame.src = url;
                pdfModal.show();
            });
        });
        
        pdfModalEl.addEventListener('hidden.bs.modal', function() {
            pdfFrame.src = '';
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

