# Aplikasi Web Bahan Ajar Informatika SMA

Aplikasi web **Bahan Ajar Informatika SMA** dirancang khusus untuk **guru SMA yang mengajar mata pelajaran Informatika tanpa latar belakang pendidikan IT**. Seluruh antarmuka dirancang sederhana, bersih, intuitif, dan tidak menggunakan istilah teknis yang rumit.

Aplikasi ini dibangun menggunakan **PHP Native (PDO) + MySQL + Bootstrap 5.3 + XAMPP**.

---

## 🌟 Fitur Utama

1. **Struktur Kurikulum Terpadu (Kelas 10, 11, 12)**:
   - Terstruktur rapi berdasarkan jenjang kelas dan bab materi Kurikulum Merdeka Informatika SMA.
   - Dilengkapi deskripsi materi yang ramah pemula dan tips mengajar di kelas.
2. **Berkas Bahan Ajar Siap Pakai**:
   - Guru dapat mengunduh **Slide Presentasi (PPT/PPTX)** untuk ditayangkan di kelas.
   - Guru dapat mengunduh atau membaca langsung **Modul Pembelajaran (PDF)** di browser.
3. **Tautan Referensi Eksternal**:
   - Tautan terpercaya (Buku Guru Kemdikbudristek, Bebras Indonesia, CS Unplugged, Khan Academy, Dicoding) untuk memperdalam materi.
4. **Pencarian Materi Cepat**:
   - Search bar di beranda dan filter instan bab materi.
5. **Panel Administrator MGMP**:
   - Dashboard statistik ringkas (jumlah bab, berkas terunggah, link web, guru terdaftar).
   - CRUD Kelas, Topik, Berkas Materi (validasi tipe & batas ukuran 20MB), dan Link Referensi.
   - Manajemen & persetujuan aktivasi akun guru.

---

## 🔑 Akun Bawaan (Default Login)

Aplikasi telah dilengkapi akun percobaan siap pakai:

| Peran (Role) | Alamat Email | Kata Sandi | Kegunaan |
|---|---|---|---|
| **Admin MGMP** | `admin@sma5.sch.id` | `admin123` | Mengelola kelas, bab topik, unggah modul/slide, kelola akun guru |
| **Guru Pengajar** | `guru@sma5.sch.id` | `guru123` | Menjelajahi materi, mengunduh berkas, membuka tautan referensi |

> *Catatan: Pada halaman login tersedia tombol klik otomatis untuk mengisi akun demo di atas tanpa perlu mengetik manual.*

---

## 🚀 Panduan Instalasi di XAMPP

### Langkah 1: Pindahkan Folder Proyek
Pastikan folder proyek ini berada di dalam direktori `htdocs` XAMPP Anda, misalnya:
```text
C:\xampp\htdocs\bahan-ajar-informatika
```
*(Atau Anda juga dapat menjalankan langsung dari lokasi folder saat ini)*.

### Langkah 2: Nyalakan Apache & MySQL
1. Buka aplikasi **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache**.
3. Klik tombol **Start** pada modul **MySQL** (pastikan keduanya berwarna hijau).

### Langkah 3: Impor Basis Data (MySQL)
1. Buka browser dan kunjungi: **[http://localhost/phpmyadmin](http://localhost/phpmyadmin)**
2. Klik tab **Databases / Basis data**, buat database baru bernama:
   ```text
   db_bahan_ajar_informatika
   ```
3. Pilih database tersebut, lalu klik tab **Import**.
4. Klik **Choose File / Telusuri**, pilih file `database.sql` yang ada di dalam folder proyek ini.
5. Gulir ke bawah dan klik tombol **Import / Kirim**.

### Langkah 4: Buka Aplikasi di Browser
Buka browser dan akses alamat berikut:
```text
http://localhost/bahan-ajar-informatika/
```
*(Jika folder bernama lain di htdocs, sesuaikan nama foldernya di URL)*.

---

## ⚡ Alternatif: Menjalankan Tanpa Apache (PHP Built-in Server)

Jika Anda ingin menjalankan aplikasi secara langsung dari folder ini:
1. Pastikan modul **MySQL di XAMPP sudah di-Start** dan database `database.sql` sudah diimpor.
2. Buka Terminal / PowerShell di folder ini, lalu jalankan perintah:
   ```bash
   php -S localhost:8000
   ```
3. Buka browser ke alamat: **[http://localhost:8000](http://localhost:8000)**

---

## 📁 Struktur Direktori Proyek

```text
/bahan-ajar-informatika
 ├── assets/
 │   ├── css/
 │   │   └── style.css            # Gaya tampilan modern Plus Jakarta Sans
 │   └── js/
 │       └── main.js              # Validasi berkas 20MB, filter cepat, alert dismiss
 ├── uploads/
 │   ├── .htaccess                # Proteksi keamanan eksekusi skrip
 │   └── materi/                  # Tempat berkas PDF, PPT, PPTX tersimpan
 ├── includes/
 │   ├── config.php               # Konfigurasi database & deteksi Base URL otomatis
 │   ├── db.php                   # Koneksi PDO aman & panduan ramah XAMPP
 │   ├── functions.php            # Helper sanitasi, upload validator, auth guard
 │   ├── header.php               # Navigasi utama guru
 │   ├── footer.php               # Footer aplikasi
 │   ├── admin_header.php         # Navigasi sidebar admin
 │   └── admin_footer.php         # Footer dashboard admin
 ├── auth/
 │   ├── login.php                # Halaman login dengan autofill demo
 │   ├── register.php             # Registrasi guru baru
 │   └── logout.php               # Keluar dari sesi
 ├── admin/
 │   ├── index.php                # Dashboard statistik materi
 │   ├── kelas.php                # Manajemen jenjang kelas (10, 11, 12)
 │   ├── topik.php                # Manajemen bab / topik pembelajaran
 │   ├── topik_detail.php         # Pengelolaan berkas PPT/PDF & tautan referensi
 │   └── users.php                # Kelola & verifikasi akun guru
 ├── guru/
 │   ├── index.php                # Katalog jenjang kelas 10, 11, 12
 │   ├── kelas.php                # Daftar bab/topik dalam jenjang kelas
 │   ├── topik.php                # Detail materi, unduh modul & buka tautan
 │   └── search.php               # Pencarian topik materi lintas kelas
 ├── database.sql                 # Skrip SQL siap import ke phpMyAdmin
 ├── index.php                    # Gerbang landing page utama
 └── README.md                    # Dokumentasi ini
```

---

## 🛡️ Keamanan & Kualitas Kode
- **Prepared Statements (PDO)** pada seluruh kueri basis data untuk mencegah celah *SQL Injection*.
- **Password Hashing** menggunakan algoritma `PASSWORD_BCRYPT` bawaan PHP.
- **Validasi Ketat Berkas**: Memeriksa format ekstensi (`pdf`, `ppt`, `pptx`), memeriksa batas maksimal 20 MB, men-sanitasi nama berkas, dan memblokir eksekusi skrip di folder `uploads`.
- **Sanitasi Output**: Seluruh teks dinamis difilter menggunakan `htmlspecialchars()` untuk mencegah *Cross-Site Scripting (XSS)*.
