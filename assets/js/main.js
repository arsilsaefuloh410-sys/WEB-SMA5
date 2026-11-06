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
            // Hanya tampilkan pesan "tidak ditemukan" bila memang ada item yang bisa difilter
            // (bila daftar awal kosong, kartu "Belum Ada Topik" sudah menangani)
            noResultEl.style.display = (topicItems.length > 0 && visibleCount === 0) ? 'block' : 'none';
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
    function initFadeSlideshow(id, slideSelector) {
        const box = document.getElementById(id);
        if (!box) return;
        const slides = box.querySelectorAll(slideSelector);
        if (slides.length < 2) return;
        let current = 0;
        setInterval(function () {
            slides[current].classList.remove('is-active');
            current = (current + 1) % slides.length;
            slides[current].classList.add('is-active');
        }, 6000);
    }
    initFadeSlideshow('heroSlideshow', '.hero-slide');
    initFadeSlideshow('galSlideshow', '.ed-gal-slide');

    // Tab jenjang kelas di beranda (semua panel tetap terlihat tanpa JS)
    const tabButtons = document.querySelectorAll('.ed-tab[data-tab]');
    if (tabButtons.length) {
        tabButtons.forEach(function (tab) {
            tab.addEventListener('click', function () {
                const tingkat = this.getAttribute('data-tab');
                document.querySelectorAll('.ed-tab[data-tab]').forEach(function (b) {
                    const active = b === tab;
                    b.classList.toggle('is-active', active);
                    b.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                document.querySelectorAll('.ed-panel').forEach(function (panel) {
                    panel.classList.toggle('is-active', panel.id === 'panel-' + tingkat);
                });
            });
        });
    }

    // Pengguna meminta gerakan minimal: hentikan video yang sedang berjalan
    if (REDUCE_MOTION) {
        document.querySelectorAll('video').forEach(function (v) {
            try { v.pause(); v.removeAttribute('autoplay'); } catch (e) {}
        });
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

/**
 * 7. Tinggi navbar lengket -> variabel CSS --nav-h
 *    Dipakai elemen sticky (judul "Yang Tersedia", tab jenjang kelas)
 *    agar berhenti di bawah navbar, bukan tertutup navbar.
 */
(function () {
    var nav = document.querySelector('.navbar-custom.sticky-top');
    if (!nav) return;

    function syncNavHeight() {
        var h = Math.round(nav.getBoundingClientRect().height);
        if (h > 0) {
            document.documentElement.style.setProperty('--nav-h', h + 'px');
        }
    }

    syncNavHeight();
    window.addEventListener('resize', syncNavHeight, { passive: true });
    window.addEventListener('orientationchange', syncNavHeight);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(syncNavHeight);
    }
    if ('ResizeObserver' in window) {
        new ResizeObserver(syncNavHeight).observe(nav);
    }
})();