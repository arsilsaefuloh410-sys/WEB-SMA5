<?php
/**
 * Halaman Pencarian Materi Informatika
 * UI minimal: satu baris pencarian + filter dropdown, hasil langsung di bawah.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib login terlebih dahulu
require_login();

$query = trim($_GET['q'] ?? '');
$filter_elemen = trim($_GET['elemen'] ?? '');
$filter_tingkat = trim($_GET['tingkat'] ?? '');
$hasil = [];

if (!empty($query) || !empty($filter_elemen) || !empty($filter_tingkat)) {
    $sql = "
        SELECT t.*, k.nama_kelas, k.tingkat,
               (SELECT COUNT(*) FROM file_materi WHERE topik_id = t.id) as total_file,
               (SELECT COUNT(*) FROM referensi WHERE topik_id = t.id) as total_referensi
        FROM topik t
        JOIN kelas k ON t.kelas_id = k.id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($query)) {
        $searchTerm = '%' . $query . '%';
        $sql .= " AND (t.judul_topik LIKE ? OR t.deskripsi LIKE ? OR k.nama_kelas LIKE ?) ";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if (!empty($filter_elemen) && $filter_elemen !== 'ALL') {
        $sql .= " AND t.elemen = ? ";
        $params[] = $filter_elemen;
    }

    if (!empty($filter_tingkat) && $filter_tingkat !== 'ALL') {
        $sql .= " AND k.tingkat = ? ";
        $params[] = (int) $filter_tingkat;
    }

    $sql .= " ORDER BY k.tingkat ASC, t.urutan ASC ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $hasil = $stmt->fetchAll();
}

$active_menu = 'pencarian';
$page_title = !empty($query) ? 'Pencarian: ' . $query : 'Cari Bahan Ajar Informatika';
$sudah_cari = !empty($query) || !empty($filter_elemen) || !empty($filter_tingkat);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.pencarian-bar {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 1rem;
}
.pencarian-input {
    position: relative;
}
.pencarian-input i.bi-search {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-primary);
    font-size: 1.1rem;
    pointer-events: none;
}
.pencarian-input input {
    width: 100%;
    border: 1.5px solid var(--border-subtle);
    border-radius: 12px;
    padding: 0.75rem 2.75rem 0.75rem 2.75rem;
    font-size: 1rem;
    color: var(--text-main);
    background: var(--bg-input);
    outline: none;
    transition: border-color 0.2s ease, background-color 0.2s ease;
    -webkit-appearance: none;
    appearance: none;
}
.pencarian-input input:focus {
    border-color: var(--color-primary);
    background: var(--bg-input);
}
.pencarian-input .btn-clear {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: var(--text-light);
    width: 1.9rem;
    height: 1.9rem;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}
.pencarian-input .btn-clear:hover {
    background: var(--color-ice);
    color: var(--color-primary);
}
.pencarian-select {
    border: 1.5px solid var(--border-subtle);
    border-radius: 12px;
    padding: 0.65rem 2.1rem 0.65rem 0.85rem;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--text-main);
    background-color: #fafbf8;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%232A4D88'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.65rem center;
    background-size: 0.7rem;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    width: 100%;
    transition: border-color 0.2s ease;
}
.pencarian-select:focus {
    border-color: var(--color-primary);
    outline: none;
}
.hasil-ringkas {
    font-size: 0.88rem;
    color: var(--text-light);
}
.hasil-ringkas strong {
    color: var(--color-primary);
}
.kartu-hasil {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 1.1rem 1.25rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.kartu-hasil:hover {
    border-color: var(--color-accent);
    box-shadow: var(--shadow-sm);
}
.kartu-hasil h6 {
    margin: 0;
    font-weight: 700;
    font-size: 1.02rem;
}
.kartu-hasil h6 a {
    color: var(--color-primary);
    text-decoration: none;
}
.kartu-hasil h6 a:hover {
    color: var(--color-primary-light);
}
.kartu-hasil p {
    margin: 0.3rem 0 0;
    font-size: 0.875rem;
    color: var(--text-light);
    line-height: 1.55;
}
.badge-kecil {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
}
.kosong {
    text-align: center;
    padding: 3.5rem 1rem;
    color: var(--text-light);
}
.kosong i {
    font-size: 2rem;
    color: var(--color-accent);
    display: block;
    margin-bottom: 0.75rem;
}
</style>

<div class="container pt-3 pb-4">

    <h4 class="fw-bold mb-1" style="color: var(--color-primary);">Cari Bahan Ajar</h4>
    <p class="hasil-ringkas mb-3">Ketik kata kunci â€” hasil tampil otomatis.</p>

    <!-- Bar Pencarian + Filter -->
    <form action="<?= base_url('guru/search.php') ?>" method="GET" id="formCari" class="pencarian-bar mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-lg-5">
                <div class="pencarian-input">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" id="inputCari" value="<?= e($query) ?>" placeholder="Cari judul topik atau materi..." autocomplete="off" autofocus data-sudah="<?= $sudah_cari ? '1' : '0' ?>">
                    <button type="button" class="btn-clear" id="btnClear" title="Hapus" aria-label="Hapus kata kunci" style="<?= empty($query) ? 'display:none;' : '' ?>">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <select class="pencarian-select" id="filterTingkat" aria-label="Filter jenjang kelas">
                    <option value="">Semua Kelas</option>
                    <option value="10" <?= $filter_tingkat === '10' ? 'selected' : '' ?>>Kelas 10</option>
                    <option value="11" <?= $filter_tingkat === '11' ? 'selected' : '' ?>>Kelas 11</option>
                    <option value="12" <?= $filter_tingkat === '12' ? 'selected' : '' ?>>Kelas 12</option>
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <select class="pencarian-select" id="filterElemen" aria-label="Filter elemen">
                    <option value="">Semua Elemen</option>
                    <?php foreach (get_elemen_list() as $kode => $el): ?>
                        <option value="<?= $kode ?>" <?= $filter_elemen === $kode ? 'selected' : '' ?>><?= e($el['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-1">
                <a href="<?= base_url('guru/search.php') ?>" class="btn w-100 py-2" style="border-radius: 12px; border: 1.5px solid var(--border-subtle); color: var(--text-light);" title="Reset pencarian">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </div>
    </form>

    <?php if ($sudah_cari): ?>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="hasil-ringkas">
                <strong><?= count($hasil) ?></strong> materi ditemukan
                <?php if (!empty($query)): ?> untuk "<strong><?= e($query) ?></strong>"<?php endif; ?>
            </span>
        </div>

        <?php if (empty($hasil)): ?>
            <div class="kartu-hasil kosong">
                <i class="bi bi-emoji-frown"></i>
                <div class="fw-bold mb-1" style="color: var(--color-primary);">Tidak ada hasil</div>
                <div>Coba kata kunci lain atau hapus filter.</div>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($hasil as $topik):
                    $el_info = get_elemen_info($topik['elemen'] ?? 'BK');
                ?>
                <div class="col-12">
                    <div class="kartu-hasil">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="badge-kecil" style="background: var(--color-primary); color: #fff;"><?= e($topik['nama_kelas']) ?></span>
                            <span class="badge-kecil" style="background: <?= $el_info['bg'] ?>; color: <?= $el_info['warna'] ?>;">
                                <i class="bi <?= $el_info['icon'] ?>"></i> <?= e($el_info['nama']) ?>
                            </span>
                            <span class="badge-kecil" style="background: var(--color-ice); color: var(--color-primary);">Bab <?= (int) $topik['urutan'] ?></span>
                        </div>
                        <h6>
                            <a href="<?= base_url('guru/topik.php?id=' . $topik['id']) ?>"><?= e($topik['judul_topik']) ?></a>
                        </h6>
                        <p><?= e(mb_substr($topik['deskripsi'] ?? '', 0, 140)) ?><?= mb_strlen($topik['deskripsi'] ?? '') > 140 ? 'â€¦' : '' ?></p>
                        <div class="d-flex align-items-center gap-3 mt-2 small" style="color: var(--text-light);">
                            <span><i class="bi bi-file-earmark me-1" style="color: #ea580c;"></i><?= (int) $topik['total_file'] ?> berkas</span>
                            <span><i class="bi bi-link-45deg me-1" style="color: var(--color-primary);"></i><?= (int) $topik['total_referensi'] ?> referensi</span>
                            <a href="<?= base_url('guru/topik.php?id=' . $topik['id']) ?>" class="ms-auto fw-semibold text-decoration-none" style="color: var(--color-primary);">
                                Buka <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="kartu-hasil kosong">
            <i class="bi bi-search"></i>
            <div class="fw-bold mb-1" style="color: var(--color-primary);">Mulai mencari</div>
            <div>Ketik kata kunci di kolom pencarian, misalnya <em>algoritma</em> atau <em>jaringan</em>.</div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    const form = document.getElementById('formCari');
    const input = document.getElementById('inputCari');
    const btnClear = document.getElementById('btnClear');
    let debounceTimer = null;

    function setFilter(name, value) {
        let el = form.querySelector('input[name="' + name + '"]');
        if (!value) {
            if (el) el.remove();
            return;
        }
        if (!el) {
            el = document.createElement('input');
            el.type = 'hidden';
            el.name = name;
            form.appendChild(el);
        }
        el.value = value;
    }

    if (input.value) {
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
    }

    input.addEventListener('input', function () {
        btnClear.style.display = input.value ? 'inline-flex' : 'none';
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            if (input.value.trim() !== '' || input.dataset.sudah === '1') {
                form.submit();
            }
        }, 500);
    });

    btnClear.addEventListener('click', function () {
        input.value = '';
        input.dataset.sudah = '1';
        form.submit();
    });

    document.getElementById('filterTingkat').addEventListener('change', function () {
        setFilter('tingkat', this.value);
        form.submit();
    });
    document.getElementById('filterElemen').addEventListener('change', function () {
        setFilter('elemen', this.value);
        form.submit();
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
