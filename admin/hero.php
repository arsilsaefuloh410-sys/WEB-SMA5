<?php
/**
 * Kelola Foto Beranda (Admin)
 * 1. Foto hero: 3 slot latar belakang slideshow yang bergantian (fade in/out).
 * 2. Foto & Keterangan: 3 foto yang tampil di beranda, masing-masing
 *    disertai teks keterangan di bawahnya. Keduanya disimpan di tabel `pengaturan`.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$active_admin_menu = 'hero';
$page_title = 'Kelola Foto Beranda';

define('HERO_UPLOAD_DIR', __DIR__ . '/../uploads/hero/');
define('HERO_UPLOAD_URL_REL', 'uploads/hero/');
define('HERO_MAX_SIZE', 3 * 1024 * 1024); // 3 MB
define('HERO_ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('HERO_MAX_WIDTH', 1400); // Lebar maksimal hasil resize (hemat memori & bandwidth)

// Ambil data pengaturan hero dari database
function get_hero_slides(PDO $pdo): array {
    $slides = [];
    try {
        $stmt = $pdo->query("SELECT kunci, nilai FROM pengaturan WHERE kunci LIKE 'hero_slide_%'");
        foreach ($stmt->fetchAll() as $row) {
            $slides[$row['kunci']] = $row['nilai'];
        }
        ksort($slides);
    } catch (Exception $e) {
        // Tabel pengaturan belum dimigrasi — kembalikan kosong agar halaman tetap tampil
        $slides = [];
    }
    return $slides;
}

function save_hero_slide(PDO $pdo, string $kunci, string $nilai): void {
    $stmt = $pdo->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
    $stmt->execute([$kunci, $nilai]);
}

const HERO_SLOTS = ['hero_slide_1', 'hero_slide_2', 'hero_slide_3'];
const GALLERY_SLOTS = ['gallery_1', 'gallery_2', 'gallery_3'];

// Ambil foto + keterangan galeri beranda dari database
function get_gallery_items(PDO $pdo): array {
    $items = [];
    foreach (GALLERY_SLOTS as $slot) {
        $items[$slot] = ['image' => '', 'caption' => ''];
    }
    try {
        $stmt = $pdo->query("SELECT kunci, nilai FROM pengaturan WHERE kunci LIKE 'gallery_%'");
        foreach ($stmt->fetchAll() as $row) {
            foreach (GALLERY_SLOTS as $slot) {
                if ($row['kunci'] === $slot . '_image') {
                    $items[$slot]['image'] = (string) $row['nilai'];
                } elseif ($row['kunci'] === $slot . '_caption') {
                    $items[$slot]['caption'] = (string) $row['nilai'];
                }
            }
        }
    } catch (Exception $e) {
        // Tabel pengaturan belum ada — biarkan kosong
    }
    return $items;
}

// Handle POST: upload, hapus, geser, simpan_caption
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');
    $action = $_POST['action'] ?? '';
    $slot = $_POST['slot'] ?? '';
    $isGallery = in_array($slot, GALLERY_SLOTS, true);

    if (!in_array($slot, HERO_SLOTS, true) && !$isGallery) {
        set_flash('danger', 'Slot foto tidak valid.');
        header('Location: ' . base_url('admin/hero.php'));
        exit;
    }

    if ($action === 'simpan_caption') {
        // Simpan teks keterangan foto galeri (di bawah foto)
        if (!$isGallery) {
            set_flash('danger', 'Teks keterangan hanya untuk foto & keterangan beranda.');
        } else {
            $caption = trim((string) ($_POST['caption'] ?? ''));
            $caption = preg_replace('/\s+/u', ' ', $caption);
            $caption = strip_tags($caption);
            if (mb_strlen($caption) > 160) {
                $caption = mb_substr($caption, 0, 160);
                set_flash('warning', 'Teks keterangan dipotong menjadi 160 karakter.');
            } else {
                set_flash('success', 'Teks keterangan berhasil disimpan.');
            }
            save_hero_slide($pdo, $slot . '_caption', $caption);
        }
        header('Location: ' . base_url('admin/hero.php'));
        exit;
    }

    if ($action === 'upload') {
        if (!isset($_FILES['hero_image']) || $_FILES['hero_image']['error'] === UPLOAD_ERR_NO_FILE) {
            set_flash('danger', 'Pilih foto terlebih dahulu.');
        } elseif ($_FILES['hero_image']['error'] !== UPLOAD_ERR_OK) {
            set_flash('danger', 'Terjadi kesalahan saat mengunggah (Kode: ' . $_FILES['hero_image']['error'] . ').');
        } elseif ($_FILES['hero_image']['size'] > HERO_MAX_SIZE) {
            set_flash('danger', 'Ukuran foto maksimal 3 MB.');
        } else {
            $ext = strtolower(pathinfo($_FILES['hero_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, HERO_ALLOWED_EXT, true)) {
                set_flash('danger', 'Format foto harus JPG, PNG, atau WebP.');
            } else {
                if (!is_dir(HERO_UPLOAD_DIR)) {
                    @mkdir(HERO_UPLOAD_DIR, 0777, true);
                }

                $srcTmp = $_FILES['hero_image']['tmp_name'];
                $imgInfo = @getimagesize($srcTmp);
                if ($imgInfo === false) {
                    set_flash('danger', 'Berkas yang diunggah bukan gambar yang valid.');
                } else {
                    if ($isGallery) {
                        $old = get_gallery_items($pdo)[$slot]['image'] ?? '';
                    } else {
                        $old = get_hero_slides($pdo)[$slot] ?? '';
                    }
                    $gdReady = function_exists('imagecreatefromjpeg') && function_exists('imagewebp');
                    $saved = false;

                    if ($gdReady) {
                        // Jalur ringan: resize + konversi WebP (hanya jika ekstensi GD aktif)
                        [$imgW, $imgH] = $imgInfo;
                        $imgMime = $imgInfo['mime'];
                        $srcImg = null;
                        if ($imgMime === 'image/jpeg') {
                            $srcImg = @imagecreatefromjpeg($srcTmp);
                        } elseif ($imgMime === 'image/png') {
                            $srcImg = @imagecreatefrompng($srcTmp);
                        } elseif ($imgMime === 'image/webp') {
                            $srcImg = @imagecreatefromwebp($srcTmp);
                        }
                        if ($srcImg) {
                            $newW = min($imgW, HERO_MAX_WIDTH);
                            $newH = $imgH > 0 ? (int) round($imgH * $newW / $imgW) : (int) round($newW * 9 / 16);
                            $dstImg = imagecreatetruecolor($newW, $newH);
                            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $imgW, $imgH);
                            imagedestroy($srcImg);

                            $newName = $slot . '_' . time() . '.webp';
                            $target = HERO_UPLOAD_DIR . $newName;
                            imagewebp($dstImg, $target, 72);
                            imagedestroy($dstImg);
                            $saved = is_file($target) && filesize($target) > 0;
                        }
                    }

                    if (!$saved) {
                        // Cadangan: simpan berkas apa adanya (jika GD belum aktif atau konversi gagal)
                        $newName = $slot . '_' . time() . '.' . $ext;
                        $target = HERO_UPLOAD_DIR . $newName;
                        $saved = move_uploaded_file($srcTmp, $target);
                    }

                    if ($saved) {
                        // Simpan ke DB dulu — hanya hapus file lama bila DB berhasil
                        $dbKey = $isGallery ? $slot . '_image' : $slot;
                        save_hero_slide($pdo, $dbKey, HERO_UPLOAD_URL_REL . $newName);
                        if (!empty($old)) {
                            $oldPath = HERO_UPLOAD_DIR . basename($old);
                            if (is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                        $msg = $gdReady
                            ? 'Foto berhasil diunggah (otomatis dikompres agar ringan).'
                            : 'Foto berhasil diunggah.';
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['ok' => true, 'message' => $msg]);
                            exit;
                        }
                        set_flash('success', $msg);
                    } else {
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['ok' => false, 'message' => 'Gagal menyimpan berkas. Periksa izin folder uploads/hero.']);
                            exit;
                        }
                        set_flash('danger', 'Gagal menyimpan berkas. Periksa izin folder uploads/hero.');
                    }
                }
            }
        }
    } elseif ($action === 'hapus') {
        if ($isGallery) {
            $old = get_gallery_items($pdo)[$slot]['image'] ?? '';
        } else {
            $old = get_hero_slides($pdo)[$slot] ?? '';
        }
        // Kosongkan DB dulu — hanya hapus file fisik bila DB berhasil
        save_hero_slide($pdo, $isGallery ? $slot . '_image' : $slot, '');
        if ($isGallery) {
            // Hapus foto sekaligus kosongkan teks keterangan
            save_hero_slide($pdo, $slot . '_caption', '');
            set_flash('success', 'Foto dan teks keterangan berhasil dihapus.');
        } else {
            set_flash('success', 'Foto hero berhasil dihapus.');
        }
        if (!empty($old)) {
            $oldPath = HERO_UPLOAD_DIR . basename($old);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
    } elseif ($action === 'geser') {
        // Tukar isi slot dengan slot tetangga (naik/turun) untuk mengatur urutan tampil
        if ($isGallery) {
            set_flash('danger', 'Foto & keterangan berada pada urutan tetap.');
            header('Location: ' . base_url('admin/hero.php'));
            exit;
        }
        $arah = $_POST['arah'] ?? '';
        $slotOrder = HERO_SLOTS;
        $idx = array_search($slot, $slotOrder, true);
        if ($idx === false || !in_array($arah, ['naik', 'turun'], true)) {
            set_flash('danger', 'Slot atau arah geser tidak valid.');
        } else {
            $targetIdx = $arah === 'naik' ? $idx - 1 : $idx + 1;
            if ($targetIdx < 0 || $targetIdx > 2) {
                set_flash('danger', 'Sudah di urutan paling ' . ($arah === 'naik' ? 'atas' : 'bawah') . '.');
            } else {
                $current = get_hero_slides($pdo);
                $a = $current[$slot] ?? '';
                $b = $current[$slotOrder[$targetIdx]] ?? '';
                save_hero_slide($pdo, $slot, $b);
                save_hero_slide($pdo, $slotOrder[$targetIdx], $a);
                set_flash('success', 'Urutan foto berhasil diubah.');
            }
        }
    }

    header('Location: ' . base_url('admin/hero.php'));
    exit;
}

$slides = get_hero_slides($pdo);
$gallery = get_gallery_items($pdo);

require_once __DIR__ . '/../includes/admin_header.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        var msg = sessionStorage.getItem('heroUploadMsg');
        if (msg) {
            sessionStorage.removeItem('heroUploadMsg');
            var banner = document.createElement('div');
            banner.className = 'alert alert-success alert-dismissible fade show';
            banner.setAttribute('role', 'alert');
            banner.textContent = msg;
            var main = document.querySelector('main') || document.querySelector('.container') || document.body;
            main.insertBefore(banner, main.firstChild);
        }
    } catch (e) {}
});
</script>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h3 class="fw-bold text-dark mb-0">Kelola Foto Beranda</h3>
        <p class="text-muted small mb-0">Kelola <strong>slideshow hero</strong> (bergantian fade) dan <strong>foto + teks keterangan</strong> yang tampil di bawahnya pada halaman beranda.</p>
    </div>
    <a href="<?= base_url('index.php') ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
        <i class="bi bi-eye me-1"></i> Lihat Beranda
    </a>
</div>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h6 class="fw-bold mb-1" style="color: var(--color-primary);"><i class="bi bi-images me-1"></i>Slideshow Hero (Banner Atas)</h6>
        <p class="text-muted small mb-0">Unggah maksimal 3 foto. Atur urutan tampil dengan tombol <strong>Naik</strong>/<strong>Turun</strong>. Foto akan bergantian tampil dengan animasi fade di halaman beranda.</p>
    </div>
</div>

<div class="row g-4">
    <?php
    $slotLabels = [
        'hero_slide_1' => 'Urutan 1 (Tampil Pertama)',
        'hero_slide_2' => 'Urutan 2',
        'hero_slide_3' => 'Urutan 3',
    ];
    foreach ($slotLabels as $slot => $label):
        $file = $slides[$slot] ?? '';
        $url = !empty($file) ? base_url($file) : '';
    ?>
    <div class="col-12 col-md-4">
        <div class="card card-modern bg-white h-100" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0" style="color: var(--color-primary);"><?= e($label) ?></h6>
                    <?php if (!empty($file)): ?>
                        <span class="badge rounded-pill" style="background: var(--color-lime-soft); color: var(--color-primary-dark);">Terisi</span>
                    <?php else: ?>
                        <span class="badge rounded-pill bg-secondary">Kosong</span>
                    <?php endif; ?>
                </div>

                <!-- Pratinjau -->
                <div class="rounded-3 mb-3 d-flex align-items-center justify-content-center overflow-hidden" style="height: 160px; background: var(--color-ice); border: 1px dashed var(--border-subtle);">
                    <?php if (!empty($file)): ?>
                        <img src="<?= e($url) ?>" alt="<?= e($label) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="bi bi-image fs-2 d-block mb-1"></i>
                            <small>Belum ada foto</small>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($file)): ?>
                <button type="button" class="btn btn-sm w-100 fw-semibold mb-2 hero-recrop-btn" data-slot="<?= $slot ?>" data-src="<?= e($url) ?>" style="border: 1px solid var(--color-primary); color: var(--color-primary); background: transparent; border-radius: 8px;">
                    <i class="bi bi-crop me-1"></i> Crop Foto
                </button>
                <?php endif; ?>

                <!-- Form Unggah -->
                <form action="" method="POST" enctype="multipart/form-data" class="mb-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload">
                    <input type="hidden" name="slot" value="<?= $slot ?>">
                    <div class="mb-2">
                        <input type="file" name="hero_image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" required style="border-radius: 8px;">
                    </div>
                    <button type="submit" class="btn btn-sm w-100 fw-semibold" style="background: var(--color-primary); color: #fff; border-radius: 8px;">
                        <i class="bi bi-upload me-1"></i> <?= !empty($file) ? 'Unggah Foto Baru' : 'Unggah Foto' ?>
                    </button>
                </form>

                <?php if (!empty($file)): ?>
                <div class="d-flex gap-2 mb-2">
                    <?php if ($slot !== 'hero_slide_1'): ?>
                    <form action="" method="POST" class="flex-fill">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="geser">
                        <input type="hidden" name="slot" value="<?= $slot ?>">
                        <input type="hidden" name="arah" value="naik">
                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100" style="border-radius: 8px;" title="Pindah ke urutan sebelumnya">
                            <i class="bi bi-arrow-up"></i> Naik
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($slot !== 'hero_slide_3'): ?>
                    <form action="" method="POST" class="flex-fill">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="geser">
                        <input type="hidden" name="slot" value="<?= $slot ?>">
                        <input type="hidden" name="arah" value="turun">
                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100" style="border-radius: 8px;" title="Pindah ke urutan berikutnya">
                            <i class="bi bi-arrow-down"></i> Turun
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <form action="" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="slot" value="<?= $slot ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100" style="border-radius: 8px;" onclick="return confirm('Hapus foto ini?');">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ==========================================================
 * FOTO & KETERANGAN BERANDA — 3 foto + teks di bawahnya
 * ========================================================== -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-4 pb-3 mb-3 mt-2 border-bottom">
    <div>
        <h6 class="fw-bold mb-1" style="color: var(--color-primary);"><i class="bi bi-card-image me-1"></i>Foto &amp; Keterangan Beranda</h6>
        <p class="text-muted small mb-0">Maksimal 3 foto yang tampil berdampingan di beranda, masing-masing dengan <strong>teks keterangan</strong> di bawahnya. Isi foto dan teks secara terpisah — keduanya bisa diubah kapan saja.</p>
    </div>
</div>

<div class="row g-4">
    <?php
    $galleryLabels = [
        'gallery_1' => 'Foto 1 (Kiri)',
        'gallery_2' => 'Foto 2 (Tengah)',
        'gallery_3' => 'Foto 3 (Kanan)',
    ];
    foreach ($galleryLabels as $slot => $label):
        $item = $gallery[$slot] ?? ['image' => '', 'caption' => ''];
        $file = $item['image'];
        $caption = $item['caption'];
        $url = !empty($file) ? base_url($file) : '';
    ?>
    <div class="col-12 col-md-4">
        <div class="card card-modern bg-white h-100" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0" style="color: var(--color-primary);"><?= e($label) ?></h6>
                    <?php if (!empty($file)): ?>
                        <span class="badge rounded-pill" style="background: var(--color-lime-soft); color: var(--color-primary-dark);">Terisi</span>
                    <?php else: ?>
                        <span class="badge rounded-pill bg-secondary">Kosong</span>
                    <?php endif; ?>
                </div>

                <!-- Pratinjau -->
                <div class="rounded-3 mb-3 d-flex align-items-center justify-content-center overflow-hidden" style="height: 160px; background: var(--color-ice); border: 1px dashed var(--border-subtle);">
                    <?php if (!empty($file)): ?>
                        <img src="<?= e($url) ?>" alt="<?= e($label) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="bi bi-image fs-2 d-block mb-1"></i>
                            <small>Belum ada foto</small>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($file)): ?>
                <button type="button" class="btn btn-sm w-100 fw-semibold mb-2 hero-recrop-btn" data-slot="<?= $slot ?>" data-src="<?= e($url) ?>" data-aspect="1.3333333333333333" style="border: 1px solid var(--color-primary); color: var(--color-primary); background: transparent; border-radius: 8px;">
                    <i class="bi bi-crop me-1"></i> Crop Foto
                </button>
                <?php endif; ?>

                <!-- Form Unggah Foto -->
                <form action="" method="POST" enctype="multipart/form-data" class="mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload">
                    <input type="hidden" name="slot" value="<?= $slot ?>">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Foto</label>
                        <input type="file" name="hero_image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" required style="border-radius: 8px;">
                    </div>
                    <button type="submit" class="btn btn-sm w-100 fw-semibold" style="background: var(--color-primary); color: #fff; border-radius: 8px;">
                        <i class="bi bi-upload me-1"></i> <?= !empty($file) ? 'Unggah Foto Baru' : 'Unggah Foto' ?>
                    </button>
                </form>

                <!-- Form Teks Keterangan -->
                <form action="" method="POST" class="mb-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="simpan_caption">
                    <input type="hidden" name="slot" value="<?= $slot ?>">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1" for="caption_<?= $slot ?>">Teks Keterangan</label>
                        <textarea name="caption" id="caption_<?= $slot ?>" class="form-control form-control-sm" rows="2" maxlength="160" placeholder="Contoh: Benteng Kalamata, Ternate — kota tempat sekolah ini berdiri" style="border-radius: 8px;"><?= e($caption) ?></textarea>
                        <div class="form-text small">Maksimal 160 karakter. Kosongkan bila tidak ingin menampilkan teks.</div>
                    </div>
                    <button type="submit" class="btn btn-sm w-100 fw-semibold" style="border: 1px solid var(--color-primary); color: var(--color-primary); background: transparent; border-radius: 8px;">
                        <i class="bi bi-save me-1"></i> Simpan Teks Keterangan
                    </button>
                </form>

                <?php if (!empty($file)): ?>
                <form action="" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="slot" value="<?= $slot ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100" style="border-radius: 8px;" onclick="return confirm('Hapus foto dan teks keterangan ini?');">
                        <i class="bi bi-trash me-1"></i> Hapus Foto &amp; Teks
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card card-modern bg-white mt-4" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-2" style="color: var(--color-primary);"><i class="bi bi-info-circle me-1"></i>Catatan</h6>
        <ul class="mb-0 small text-muted">
            <li>Format yang diizinkan: JPG, PNG, WebP. Maksimal 3 MB per foto.</li>
            <li><strong>Unggah Foto Baru</strong>: pilih berkas lalu klik Unggah — foto langsung disimpan (otomatis dikompres agar ringan).</li>
            <li><strong>Crop Foto</strong>: hanya muncul jika slot sudah berisi foto. Klik untuk membuka kotak crop, geser/perbesar area sesuai keinginan, lalu klik <strong>Gunakan Hasil Crop &amp; Unggah</strong> — hasil crop otomatis menggantikan foto lama.</li>
            <li>Rasio crop slideshow hero mengikuti tampilan banner (16:6), sedangkan foto &amp; keterangan memakai rasio 4:3.</li>
            <li><strong>Teks Keterangan</strong> disimpan terpisah dari foto — Anda bisa mengubah teks tanpa mengunggah ulang fotonya.</li>
            <li>Jika semua slot hero kosong, banner beranda kembali memakai tampilan gradien bawaan.</li>
            <li>Jika semua foto &amp; keterangan kosong, bagian beranda menampilkan video bawaan (Benteng Kalamata).</li>
        </ul>
    </div>
</div>

<!-- Modal Crop Foto Hero -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pb-0">
                <h6 class="modal-title fw-bold" id="cropModalLabel" style="color: var(--color-primary);"><i class="bi bi-crop me-1"></i>Crop Foto</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="bg-light rounded-3 d-flex align-items-center justify-content-center overflow-hidden p-2" style="min-height: 320px;">
                    <img id="cropImage" alt="Pratinjau crop" style="max-width: 100%; display: block;">
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="cropZoomOut"><i class="bi bi-zoom-out"></i> Perkecil</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="cropReset"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="cropZoomIn"><i class="bi bi-zoom-in"></i> Perbesar</button>
                </div>
                <p class="small text-muted text-center mt-3 mb-0">Geser foto atau tarik sudut bingkai crop, lalu klik tombol <strong>Gunakan Hasil Crop &amp; Unggah</strong>.</p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm fw-semibold rounded-pill px-4" id="cropSaveBtn" style="background: #2A4D88; color: #fff;">
                    <i class="bi bi-check2-circle me-1"></i> Gunakan Hasil Crop &amp; Unggah
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
<script>
/* Inisialisasi ditunda sampai DOM siap (Bootstrap bundle dimuat setelah skrip ini) */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
        console.error('Bootstrap JS belum termuat — fitur crop tidak aktif.');
        return;
    }
    if (typeof Cropper === 'undefined') {
        console.error('Cropper.js belum termuat — fitur crop tidak aktif.');
        return;
    }

    var DEFAULT_ASPECT = 16 / 6; /* rasio banner hero */
    var cropper = null;
    var activeSlot = null;
    var activeAspect = DEFAULT_ASPECT;

    var modalEl = document.getElementById('cropModal');
    var cropModal = new bootstrap.Modal(modalEl);
    var cropImage = document.getElementById('cropImage');
    var saveBtn = document.getElementById('cropSaveBtn');
    var saveBtnHtml = saveBtn.innerHTML;

    function resetSaveBtn() {
        saveBtn.disabled = false;
        saveBtn.innerHTML = saveBtnHtml;
    }

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (cropper) { cropper.destroy(); cropper = null; }
        cropImage.removeAttribute('src');
        activeSlot = null;
        activeAspect = DEFAULT_ASPECT;
        resetSaveBtn();
    });

    /* Tombol Crop Foto: buka modal crop dengan foto yang sudah terunggah */
    document.querySelectorAll('.hero-recrop-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeSlot = btn.getAttribute('data-slot');
            var aspectAttr = btn.getAttribute('data-aspect');
            activeAspect = aspectAttr ? parseFloat(aspectAttr) : DEFAULT_ASPECT;
            if (!activeAspect || isNaN(activeAspect)) activeAspect = DEFAULT_ASPECT;
            cropImage.src = btn.getAttribute('data-src');
            cropModal.show();
        });
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        if (!cropImage.getAttribute('src')) return;
        if (cropper) { cropper.destroy(); cropper = null; }

        function initCropper() {
            cropper = new Cropper(cropImage, {
                aspectRatio: activeAspect,
                viewMode: 1,
                autoCropArea: 1,
                responsive: true,
                background: false,
                guides: true
            });
        }

        if (cropImage.complete && cropImage.naturalWidth > 0) {
            initCropper();
        } else {
            cropImage.addEventListener('load', initCropper, { once: true });
        }
    });

    document.getElementById('cropZoomIn').addEventListener('click', function () {
        if (cropper) cropper.zoom(0.1);
    });
    document.getElementById('cropZoomOut').addEventListener('click', function () {
        if (cropper) cropper.zoom(-0.1);
    });
    document.getElementById('cropReset').addEventListener('click', function () {
        if (cropper) cropper.reset();
    });

    saveBtn.addEventListener('click', function () {
        if (!cropper || !activeSlot) return;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

        cropper.getCroppedCanvas({ width: 1600, imageSmoothingEnabled: true, imageSmoothingQuality: 'high' })
            .toBlob(function (blob) {
                if (!blob) {
                    resetSaveBtn();
                    alert('Gagal memproses hasil crop.');
                    return;
                }

                /* Kirim hasil crop ke server tanpa reload form (AJAX) */
                var fd = new FormData();
                fd.append('action', 'upload');
                fd.append('slot', activeSlot);
                fd.append('csrf_token', <?= json_encode(csrf_token()) ?>);
                fd.append('hero_image', blob, 'hero-crop.jpg');
                fetch(window.location.href, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.ok) throw new Error(data.message || 'upload gagal');
                        try { sessionStorage.setItem('heroUploadMsg', data.message); } catch (e) {}
                        location.reload();
                    })
                    .catch(function (err) {
                        resetSaveBtn();
                        alert(err.message || 'Gagal mengunggah hasil crop. Periksa koneksi/lalu ulangi.');
                    });
            }, 'image/jpeg', 0.88);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
