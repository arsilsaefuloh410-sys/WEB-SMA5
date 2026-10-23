/**
 * ==========================================================
 * JavaScript Interaktivitas - Bahan Ajar Informatika SMA
 * Ramah Pengguna & Membantu Pengalaman Guru Non-IT
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Auto-dismiss flash alerts setelah 5 detik
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            try {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            } catch (e) {
                alert.style.display = 'none';
            }
        }, 5000);
    });

    // 2. Validasi Ukuran & Ekstensi File Saat Memilih Berkas di Form Upload
    const fileInputs = document.querySelectorAll('input[type="file"].validate-file-upload');
    const MAX_SIZE_BYTES = 20 * 1024 * 1024; // 20MB
    const ALLOWED_EXTS = ['pdf', 'ppt', 'pptx'];

    fileInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const fileName = file.name;
                const fileSize = file.size;
                const fileExt = fileName.split('.').pop().toLowerCase();

                // Cek ekstensi
                if (!ALLOWED_EXTS.includes(fileExt)) {
                    alert('Format berkas tidak didukung!\nSilakan pilih berkas berekstensi PDF, PPT, atau PPTX.');
                    this.value = '';
                    return;
                }

                // Cek ukuran
                if (fileSize > MAX_SIZE_BYTES) {
                    const sizeMB = (fileSize / (1024 * 1024)).toFixed(1);
                    alert('Ukuran berkas terlalu besar (' + sizeMB + ' MB)!\nMaksimal ukuran berkas yang diperbolehkan adalah 20 MB.');
                    this.value = '';
                    return;
                }
            }
        });
    });

    // 3. Konfirmasi Ramah saat Menghapus Data
    const deleteButtons = document.querySelectorAll('.confirm-delete');
    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const itemLabel = this.getAttribute('data-item-name') || 'item ini';
            const confirmMsg = 'Apakah Bapak/Ibu yakin ingin menghapus ' + itemLabel + '?\nData yang dihapus tidak dapat dikembalikan.';
            if (!confirm(confirmMsg)) {
                e.preventDefault();
            }
        });
    });

    // 4. Filter / Pencarian Instan di Daftar Topik (Live Filter + Elemen Filter)
    const quickSearchInput = document.getElementById('quickTopicSearch');
    const elemenButtons = document.querySelectorAll('.btn-filter-elemen');
    const topicItems = document.querySelectorAll('.topic-item-card');
    const noResultEl = document.getElementById('noTopicFoundMsg');
    let currentSelectedElemen = 'ALL';

    function applyTopicFilter() {
        const textFilter = quickSearchInput ? quickSearchInput.value.toLowerCase().trim() : '';
        let visibleCount = 0;

        topicItems.forEach(function (item) {
            const text = item.innerText.toLowerCase();
            const itemElemen = (item.getAttribute('data-elemen') || '').toUpperCase();

            const matchesText = !textFilter || text.includes(textFilter);
            const matchesElemen = (currentSelectedElemen === 'ALL') || (itemElemen === currentSelectedElemen);

            if (matchesText && matchesElemen) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (noResultEl) {
            noResultEl.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    }

    if (quickSearchInput) {
        quickSearchInput.addEventListener('input', applyTopicFilter);
    }

    if (elemenButtons.length > 0) {
        elemenButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                elemenButtons.forEach(b => {
                    b.classList.remove('active', 'btn-primary');
                    b.classList.add('btn-outline-secondary');
                });
                this.classList.add('active', 'btn-primary');
                this.classList.remove('btn-outline-secondary');

                currentSelectedElemen = (this.getAttribute('data-elemen') || 'ALL').toUpperCase();
                applyTopicFilter();
            });
        });
    }

    // 5. Sidebar Permanen: tombol garis tiga bundar
    //    Layar besar: menampilkan/menyembunyikan sidebar (konten bergeser).
    //    Layar kecil: sidebar tampil sebagai overlay dengan backdrop.
    const sidebarToggleBtn = document.querySelector('.sidebar-toggle');
    const sidebarBackdrop = document.querySelector('.sidebar-backdrop');
    if (sidebarToggleBtn) {
        const desktopQuery = window.matchMedia('(min-width: 992px)');

        sidebarToggleBtn.addEventListener('click', function () {
            if (desktopQuery.matches) {
                document.body.classList.toggle('sidebar-collapsed');
            } else {
                document.body.classList.toggle('sidebar-show');
            }
        });

        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', function () {
                document.body.classList.remove('sidebar-show');
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.body.classList.remove('sidebar-show');
            }
        });
    }

    // Slideshow hero: ganti slide aktif tiap 6 detik (hanya opacity, sangat ringan)
    const heroSlideshow = document.getElementById('heroSlideshow');
    if (heroSlideshow) {
        const slides = heroSlideshow.querySelectorAll('.hero-slide');
        if (slides.length > 1) {
            let current = 0;
            setInterval(function () {
                slides[current].classList.remove('is-active');
                current = (current + 1) % slides.length;
                slides[current].classList.add('is-active');
            }, 6000);
        }
    }

    initRevealAnimations();
    initCountUpAnimations();
});

/**
 * ==========================================================
 * MESIN ANIMASI INTERAKTIF (Reveal Halus & Angka Berhitung)
 * ==========================================================
 */
const REDUCE_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * 1. Reveal Halus Saat Masuk Layar
 * Hanya fade + geser kecil sekali saat elemen terlihat.
 */
function initRevealAnimations() {
    const targets = document.querySelectorAll('.anim-reveal');
    if (!targets.length || REDUCE_MOTION) return;

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    targets.forEach(function (el) {
        observer.observe(el);
    });
}

/**
 * 2. Angka Berhitung (Count Up) Saat Masuk Layar
 * Nilai akhir diambil dari data-count (hasil query database).
 * Durasi ~2s agar terasa "menghitung", bukan melompat.
 */
function initCountUpAnimations() {
    const counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    if (REDUCE_MOTION) return;

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateCount(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });

    counters.forEach(function (el) {
        observer.observe(el);
    });
}

function animateCount(el) {
    const target = parseFloat(el.getAttribute('data-count'));
    if (isNaN(target)) return;

    const prefix = el.getAttribute('data-prefix') || '';
    const suffix = el.getAttribute('data-suffix') || '';
    const duration = 1200;
    const startTime = performance.now();

    function render(value) {
        el.textContent = prefix + Math.round(value).toLocaleString('id-ID') + suffix;
    }

    function frame(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 2);
        render(target * eased);

        if (progress < 1) {
            requestAnimationFrame(frame);
        } else {
            render(target);
        }
    }

    requestAnimationFrame(frame);
}

/**
 * 6. Mode Gelap / Terang (preferensi per-pengguna)
 *    Preferensi disimpan di localStorage('theme') dan diterapkan
 *    ke atribut <html data-theme="dark"> oleh script anti-kedip di <head>.
 */
(function () {
    var root = document.documentElement;

    function syncThemeIcons() {
        var isDark = root.getAttribute('data-theme') === 'dark';
        document.querySelectorAll('.theme-toggle').forEach(function (btn) {
            var icon = btn.querySelector('i');
            if (icon) {
                icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
            }
            var label = isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap';
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncThemeIcons);
    } else {
        syncThemeIcons();
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.theme-toggle') : null;
        if (!btn) return;

        var isDark = root.getAttribute('data-theme') === 'dark';
        if (isDark) {
            root.removeAttribute('data-theme');
            try { localStorage.setItem('theme', 'light'); } catch (err) {}
        } else {
            root.setAttribute('data-theme', 'dark');
            try { localStorage.setItem('theme', 'dark'); } catch (err) {}
        }
        syncThemeIcons();
    });
})();