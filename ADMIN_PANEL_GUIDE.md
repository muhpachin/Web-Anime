# 🎛️ Panduan Lengkap Panel Admin - nipnime

## 📋 Daftar Isi
1. [Akses Panel Admin](#akses-panel-admin)
2. [Dashboard Overview](#dashboard-overview)
3. [Manajemen Anime](#manajemen-anime)
4. [Manajemen Episode](#manajemen-episode)
5. [Manajemen Genre](#manajemen-genre)
6. [Manajemen User](#manajemen-user)
7. [Scraping System](#scraping-system)
8. [Jadwal Tayang](#jadwal-tayang)
9. [Tips & Troubleshooting](#tips--troubleshooting)

---

## 🔐 Akses Panel Admin

### Cara Login ke Admin Panel

1. **Buka URL Admin:**
   ```
   http://localhost:8000/admin
   atau
   http://127.0.0.1:8000/admin
   ```

2. **Login dengan Akun Admin:**
   - **Email:** `admin@example.com`
   - **Password:** `password`
   
   > ⚠️ **PENTING:** Segera ganti password default setelah login pertama kali!

3. **Jika Belum Punya Akun Admin:**
   ```bash
   # Di terminal/command prompt:
   cd "c:\xampp\htdocs\Web Anime"
   php create_admin.php
   ```
   Script akan membuat user admin baru dengan kredensial default.

### Membuat Admin dari User yang Sudah Ada

```bash
# Jalankan script untuk upgrade user biasa menjadi admin:
php quick_make_admin.php

# Pilih user yang ingin dijadikan admin dari daftar
```

### Cek Status Admin User

```bash
# Lihat semua user dan status adminnya:
php list_users.php

# Debug info user tertentu:
php debug_admin.php
```

---

## 🏠 Dashboard Overview

Setelah login, Anda akan melihat Dashboard dengan:

### Sidebar Menu (Kiri)
- **Dashboard** - Halaman utama
- **Anime** - Kelola daftar anime
- **Episodes** - Kelola episode anime
- **Genres** - Kelola kategori genre
- **Users** - Kelola user dan admin
- **Video Servers** - Kelola server streaming
- **Scrape Configs** - Konfigurasi scraping
- **Scrape Logs** - Log history scraping
- **Schedules** - Jadwal tayang anime

### Top Bar (Atas)
- **Search** - Cari data dengan cepat
- **Notifications** - Notifikasi sistem
- **Profile** - Menu profil admin
- **Dark/Light Mode** - Toggle tema

---

## 🎬 Manajemen Anime

### Melihat Daftar Anime

1. Klik **"Anime"** di sidebar
2. Anda akan melihat tabel dengan kolom:
   - **Poster** - Gambar poster anime
   - **Title** - Judul anime
   - **Type** - TV/Movie/OVA/Special
   - **Status** - Ongoing/Completed
   - **Rating** - Rating 0-10
   - **Episodes** - Jumlah episode
   - **Created At** - Tanggal ditambahkan

### Fitur Tabel Anime

**Pencarian:**
- Kotak search di kanan atas
- Cari berdasarkan judul, synopsis, atau slug

**Filter:**
- **Type:** TV, Movie, OVA, Special, ONA
- **Status:** Ongoing, Completed, Upcoming
- **Rating:** Filter berdasarkan range rating

**Sorting:**
- Klik header kolom untuk sort ascending/descending
- Sort by: Title, Rating, Created Date, dll

**Pagination:**
- Pilih items per page: 10, 25, 50, 100
- Navigasi halaman dengan tombol prev/next

### Menambah Anime Baru

1. **Klik tombol "New Anime"** (hijau, kanan atas)

2. **Isi Form:**
   
   **Tab: Details**
   - **Title** *(Required)* - Judul anime
     - Contoh: "Naruto Shippuden"
   
   - **Slug** *(Auto-generate)* - URL-friendly version
     - Auto diisi dari title
     - Contoh: "naruto-shippuden"
   
   - **Synopsis** *(Required)* - Deskripsi cerita
     - Min 10 karakter
     - Jelaskan plot anime
   
   - **Type** *(Required)* - Pilih jenis:
     - TV (Serial TV)
     - Movie (Film)
     - OVA (Original Video Animation)
     - Special (Episode spesial)
     - ONA (Original Net Animation)
   
   - **Status** *(Required)* - Status tayang:
     - Ongoing (Sedang tayang)
     - Completed (Sudah selesai)
     - Upcoming (Akan datang)
   
   - **Rating** *(Required)* - Rating 0-10
     - Gunakan slider atau ketik angka
     - Contoh: 8.5
   
   - **Release Year** *(Optional)* - Tahun rilis
     - Contoh: 2023

   **Tab: Media**
   - **Poster Image** - Upload gambar poster
     - Format: JPG, PNG, WebP
     - Ukuran max: 2MB
     - Rekomendasi: 300x450px (aspect ratio 2:3)
     - Klik "Browse" untuk pilih file
   
   - **MAL ID** *(Optional)* - MyAnimeList ID
     - Untuk sync dengan MAL API
     - Contoh: 1735 (Naruto Shippuden)

   **Tab: Relations**
   - **Genres** - Pilih genre anime
     - Multi-select, bisa pilih banyak
     - Contoh: Action, Adventure, Shounen

3. **Klik "Create"** untuk simpan

### Mengedit Anime

1. **Pilih Anime:**
   - Klik baris anime di tabel, atau
   - Klik icon pensil di kolom Actions

2. **Edit Form:**
   - Sama seperti form create
   - Semua field bisa diubah
   - Upload poster baru untuk ganti

3. **Klik "Save"** untuk simpan perubahan

### Menghapus Anime

1. **Pilih Anime** yang ingin dihapus
2. **Klik icon trash** di kolom Actions
3. **Konfirmasi** penghapusan
4. **Warning:** Semua episode dan video servers terkait akan ikut terhapus (cascade delete)

### Bulk Actions (Aksi Massal)

1. **Centang checkbox** di beberapa anime
2. **Pilih action** di dropdown "Bulk Actions":
   - **Delete selected** - Hapus semua yang dipilih
   - **Export** - Export data ke Excel/CSV
3. **Klik "Run"** untuk eksekusi

---

## 📺 Manajemen Episode

### Melihat Daftar Episode

1. Klik **"Episodes"** di sidebar
2. Tabel menampilkan:
   - **Anime** - Anime yang terkait
   - **Episode Number** - Nomor episode
   - **Title** - Judul episode
   - **Slug** - URL slug
   - **Servers** - Jumlah video server
   - **Created At** - Tanggal ditambahkan

### Menambah Episode Baru

1. **Klik "New Episode"**

2. **Isi Form:**
   
   - **Anime** *(Required)* - Pilih anime
     - Search box dengan autocomplete
     - Ketik nama anime untuk cari
   
   - **Episode Number** *(Required)* - Nomor episode
     - Integer, contoh: 1, 2, 3
     - Untuk season 2 ep 1, bisa: 25 (lanjutan)
   
   - **Title** *(Optional)* - Judul episode
     - Contoh: "The Boy in the Iceberg"
     - Jika kosong, auto jadi "Episode X"
   
   - **Slug** *(Auto-generate)* - URL slug
     - Format: anime-slug-episode-X
     - Contoh: naruto-shippuden-episode-1
   
   - **Description** *(Optional)* - Deskripsi episode
     - Sinopsis singkat episode

3. **Klik "Create"**

### Menambahkan Video Server ke Episode

Setelah episode dibuat, tambahkan video server:

1. **Buka Episode** yang baru dibuat
2. **Scroll ke bagian "Video Servers"**
3. **Klik "Add Video Server"**
4. **Isi data:**
   - **Server Name** - Nama server (Streamtape, Googledrive, dll)
   - **Embed URL** - URL embed video
     - Contoh: `https://streamtape.com/e/xxxxx`
   - **Server Type** - Pilih jenis server
   - **Quality** - 360p, 480p, 720p, 1080p
   - **Is Primary** - Centang jika server utama

5. **Klik "Create"**

### Tips Episode Management

**Nomor Episode:**
- Season 1: 1-24
- Season 2: 25-48 (atau 1-24 jika terpisah)
- Movie: 0 atau 999
- OVA: 0.5, 13.5 (between episodes)

**Multiple Servers:**
- Selalu tambahkan minimal 2-3 server backup
- Set server tercepat sebagai primary
- Gunakan quality berbeda untuk pilihan user

**Bulk Episode Creation:**
- Gunakan script scraping untuk import massal
- Atau gunakan CSV import (jika tersedia)

---

## 🎭 Manajemen Genre

### Melihat Daftar Genre

1. Klik **"Genres"** di sidebar
2. Tabel menampilkan:
   - **Name** - Nama genre
   - **Slug** - URL slug
   - **Animes Count** - Jumlah anime dengan genre ini

### Menambah Genre Baru

1. **Klik "New Genre"**

2. **Isi Form:**
   - **Name** *(Required)* - Nama genre
     - Contoh: "Action", "Romance", "Isekai"
   - **Slug** *(Auto-generate)* - URL slug
     - Auto dari name
     - Contoh: "action", "romance"

3. **Klik "Create"**

### Genre yang Umum Digunakan

```
Action          - Aksi, pertarungan
Adventure       - Petualangan
Comedy          - Komedi
Drama           - Drama
Fantasy         - Fantasi
Horror          - Horor
Isekai          - Dunia lain
Magic           - Sihir
Mecha           - Robot
Military        - Militer
Music           - Musik
Mystery         - Misteri
Psychological   - Psikologis
Romance         - Romansa
School          - Sekolah
Sci-Fi          - Fiksi ilmiah
Shounen         - Target remaja pria
Shoujo          - Target remaja wanita
Slice of Life   - Kehidupan sehari-hari
Sports          - Olahraga
Supernatural    - Supranatural
Thriller        - Thriller
```

### Mengedit/Menghapus Genre

- **Edit:** Klik baris genre → Edit nama/slug
- **Delete:** Klik trash icon → Konfirmasi
- **Warning:** Genre dengan anime terkait tidak bisa dihapus (referential integrity)

---

## 👥 Manajemen User

### Melihat Daftar User

1. Klik **"Users"** di sidebar
2. Tabel menampilkan:
   - **Name** - Nama user
   - **Email** - Email user
   - **Avatar** - Foto profil
   - **Is Admin** - Status admin
   - **Bio** - Deskripsi singkat
   - **Created At** - Tanggal registrasi

### Membuat User Baru

1. **Klik "New User"**

2. **Isi Form:**
   - **Name** *(Required)* - Nama lengkap
   - **Email** *(Required)* - Email unik
   - **Password** *(Required)* - Min 8 karakter
   - **Avatar URL** *(Optional)* - Link foto profil
   - **Bio** *(Optional)* - Deskripsi singkat
   - **Is Admin** - Centang untuk jadikan admin

3. **Klik "Create"**

### Mengubah User Menjadi Admin

**Cara 1: Via Admin Panel**
1. Klik user yang ingin diubah
2. Centang checkbox **"Is Admin"**
3. Klik "Save"

**Cara 2: Via Script (Terminal)**
```bash
php quick_make_admin.php
# Pilih user dari daftar
```

### Reset Password User

1. **Edit User**
2. **Isi field "Password"** dengan password baru
3. **Klik "Save"**

> Password otomatis di-hash dengan bcrypt

### Menonaktifkan User

Saat ini tidak ada soft delete, tapi bisa:
1. **Reset password** ke random string
2. **Hapus user** jika tidak diperlukan lagi

### Tips User Management

**Security:**
- Jangan buat terlalu banyak admin
- Gunakan password kuat (min 12 karakter)
- Review daftar admin secara berkala

**Data User:**
- Bio digunakan untuk profile page
- Avatar URL bisa dari Gravatar atau upload
- Email wajib unik (tidak boleh duplikat)

---

## 🕷️ Scraping System

### Apa itu Scraping System?

Sistem otomatis untuk mengambil data anime dari website sumber (seperti Kusonime, Otakudesu, dll) dan import ke database.

### Melihat Scrape Configs

1. Klik **"Scrape Configs"** di sidebar
2. Tabel menampilkan:
   - **Name** - Nama konfigurasi
   - **Source Type** - Jenis sumber (Kusonime, Otakudesu)
   - **Base URL** - URL website sumber
   - **Is Active** - Status aktif/nonaktif
   - **Last Scraped** - Terakhir dijalankan

### Menambah Scrape Config Baru

1. **Klik "New Scrape Config"**

2. **Isi Form:**
   
   **Basic Info:**
   - **Name** *(Required)* - Nama config
     - Contoh: "Kusonime Latest Episodes"
   
   - **Source Type** *(Required)* - Pilih sumber:
     - Kusonime
     - Otakudesu
     - Custom
   
   - **Base URL** *(Required)* - URL website
     - Contoh: "https://kusonime.com"
   
   - **Is Active** - Centang untuk aktifkan

   **Selectors (CSS):**
   - **List Selector** - Selector untuk list anime
     - Contoh: `.post`
   
   - **Title Selector** - Selector judul
     - Contoh: `.post-title a`
   
   - **Image Selector** - Selector gambar
     - Contoh: `.post-thumbnail img`
   
   - **Link Selector** - Selector link detail
     - Contoh: `.post-title a`

   **Advanced:**
   - **Auto Scrape** - Scrape otomatis setiap X menit
   - **Scrape Interval** - Interval waktu (menit)
   - **Max Pages** - Maksimal halaman per scrape

3. **Klik "Create"**

### Menjalankan Scraping Manual

1. **Pilih Config** yang ingin dijalankan
2. **Klik tombol "Run Scraping"** di detail page
3. **Tunggu proses** selesai (bisa 1-5 menit)
4. **Cek Scrape Logs** untuk hasil

### Melihat Scrape Logs

1. Klik **"Scrape Logs"** di sidebar
2. Tabel menampilkan:
   - **Config** - Konfigurasi yang digunakan
   - **Status** - Success/Failed/Running
   - **Items Found** - Jumlah item ditemukan
   - **Items Imported** - Jumlah berhasil import
   - **Errors** - Pesan error (jika ada)
   - **Started At** - Waktu mulai
   - **Completed At** - Waktu selesai

### Filter Logs

- **Status:** Success, Failed, Running
- **Date Range:** Tanggal tertentu
- **Config:** Berdasarkan config tertentu

### Troubleshooting Scraping

**Problem: Scraping Failed**
- Cek koneksi internet
- Cek apakah website sumber masih aktif
- Verify selector CSS masih valid
- Cek Scrape Logs untuk error detail

**Problem: Items Found tapi tidak Import**
- Cek apakah anime sudah ada (duplicate check)
- Cek validation rules (judul, synopsis required)
- Cek foreign key (genre, dll)

**Problem: Scraping Terlalu Lambat**
- Kurangi Max Pages
- Tambah delay antar request
- Gunakan queue untuk background processing

---

## 🔄 MAL Sync

Gunakan fitur MAL Sync untuk mengimpor data anime dari MyAnimeList (via Jikan API) ke database Anda.

### Prasyarat
- Internet aktif (Jikan API membutuhkan koneksi)
- `.env` disarankan:
   - `QUEUE_CONNECTION=database` (atau `sync` untuk eksekusi langsung)
   - `CACHE_DRIVER=file`
   - `FILESYSTEM_DISK=public`
- Buat symlink storage agar poster tampil:

```bash
php artisan storage:link
```

- Jalankan queue worker (jika menggunakan `QUEUE_CONNECTION=database`):

```bash
php artisan queue:work --queue=default --tries=3
```

Biarkan worker berjalan di terminal terpisah saat proses sync.

### Cara Pakai di Panel
1. Buka menu: Sidebar → **MAL Sync** atau langsung `http://localhost:8000/admin/my-anime-list-sync`
2. Pilih **Sync Type**:
    - **Top**: Ambil anime teratas dari MAL
    - **Seasonal**: Ambil anime per musim (Winter/Spring/Summer/Fall) dan tahun
    - **Search**: Cari anime berdasarkan judul
3. Atur parameter:
    - **Year/Season**: Kosongkan untuk tahun/musim saat ini, atau isi manual (misal 2020, Summer)
    - **Limit**: Jumlah anime (mulai dari 10 untuk testing, default hingga 25-300 tergantung endpoint)
    - **Download Poster Images**: ON untuk simpan poster lokal (lebih lambat), OFF untuk lebih cepat
4. Klik **Start Sync**.
5. Perhatikan **Activity Log** dan **progress bar**; akan berubah seiring proses (0% → 100%).
6. Setelah selesai, cek menu **Anime** untuk melihat hasil dan **Scrape Logs** bila tersedia.

### Tips Penggunaan
- Mulai dengan `Limit = 10` dulu untuk uji coba.
- Untuk seasonal, jika ingin semua musim dalam setahun, pilih Season = `All` dan isi Year.
- ON image download akan menyimpan file di `public/storage/posters/...`.

### Troubleshooting
- **Stuck di "Syncing... 0%"**:
   - Pastikan queue worker jalan: `php artisan queue:work`
   - Atau set `.env` ke `QUEUE_CONNECTION=sync` lalu reload panel untuk eksekusi langsung
   - Clear cache: `php artisan cache:clear`
- **Gagal ambil data (API error/timeout)**:
   - Turunkan `Limit`, coba lagi
   - Cek koneksi internet, Jikan rate limit (maks ~3 request/detik)
- **Poster tidak muncul**:
   - Jalankan `php artisan storage:link`
   - Pastikan `FILESYSTEM_DISK=public` dan folder `public/storage/posters/` terbentuk

---

## 📅 Jadwal Tayang (Schedules)

### Melihat Jadwal Tayang

1. Klik **"Schedules"** di sidebar
2. Tabel menampilkan:
   - **Anime** - Anime yang dijadwalkan
   - **Day of Week** - Hari tayang
   - **Broadcast Time** - Jam tayang
   - **Is Active** - Status aktif

### Menambah Jadwal Baru

1. **Klik "New Schedule"**

2. **Isi Form:**
   - **Anime** *(Required)* - Pilih anime
   - **Day of Week** *(Required)* - Hari tayang
     - Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday
   - **Broadcast Time** *(Required)* - Jam tayang
     - Format: HH:MM (24 jam)
     - Contoh: 19:00, 22:30
   - **Is Active** - Centang untuk aktifkan

3. **Klik "Create"**

### Menggunakan Time Picker

- **Hour Step:** Klik panah atau ketik jam
- **Minute Step:** Klik panah atau ketik menit
- **Format:** 24 jam (00:00 - 23:59)

### Tips Jadwal Tayang

**Timezone:**
- Gunakan timezone lokal (WIB/WITA/WIT)
- Atau gunakan JST (Japan Standard Time) untuk anime Jepang

**Update Otomatis:**
- Jadwal digunakan untuk notifikasi user
- Episode baru bisa auto-publish sesuai jadwal
- Tampil di homepage sebagai "Airing Today"

---

## 🎨 Customization & Settings

### Theme & Appearance

**Dark Mode:**
- Toggle di top bar (icon bulan/matahari)
- Otomatis save preference

**Sidebar:**
- Collapse/Expand dengan tombol hamburger
- Width bisa disesuaikan (drag)

**Table Density:**
- Comfortable (default)
- Compact (lebih banyak data)
- Ubah di table settings

### Notifications

**Email Notifications:**
- Scraping selesai
- Error critical
- New user registration

**In-App Notifications:**
- Icon bell di top bar
- Badge untuk unread count
- Click untuk lihat detail

### User Preferences

**Profile Settings:**
1. Click avatar di top bar
2. Select "Profile"
3. Edit:
   - Name
   - Email
   - Password
   - Avatar
   - Bio

**Logout:**
- Click avatar → Logout
- Atau `/admin/logout`

---

## 🔧 Tips & Troubleshooting

### Tips Umum

**Performance:**
- Gunakan pagination untuk table besar
- Filter data sebelum export
- Clear cache jika UI lambat

**Data Entry:**
- Gunakan keyboard shortcuts (Ctrl+S untuk save)
- Tab untuk navigasi antar field
- Enter untuk submit form

**Backup:**
- Export data secara berkala
- Backup database setiap minggu
- Simpan file export di cloud

### Common Issues

#### Issue 1: Cannot Access Admin Panel

**Symptoms:** 403 Forbidden saat akses `/admin`

**Solutions:**
```bash
# Cek apakah user adalah admin
php list_users.php

# Jadikan user sebagai admin
php quick_make_admin.php

# Atau buat admin baru
php create_admin.php
```

#### Issue 2: Upload Poster Failed

**Symptoms:** Error saat upload gambar

**Solutions:**
- Cek ukuran file (max 2MB)
- Cek format (JPG/PNG/WebP only)
- Cek permission folder `storage/app/public`
- Run: `php artisan storage:link`

#### Issue 3: Slug Conflict

**Symptoms:** "Slug already exists" error

**Solutions:**
- Edit slug manual agar unique
- Tambah suffix angka: `naruto-1`, `naruto-2`
- Delete anime lama jika duplicate

#### Issue 4: Relationship Error

**Symptoms:** "Foreign key constraint fails"

**Solutions:**
- Pastikan anime exist sebelum create episode
- Pastikan genre exist sebelum assign ke anime
- Jangan hapus parent jika masih ada child

#### Issue 5: Scraping Timeout

**Symptoms:** Scraping stuck atau timeout

**Solutions:**
- Kurangi max pages
- Tingkatkan PHP timeout: `set_time_limit(300)`
- Gunakan queue untuk long-running tasks
- Check website sumber masih accessible

### Performance Optimization

**Database:**
```bash
# Optimize tables
php artisan db:optimize

# Clear query cache
php artisan cache:clear
```

**Files:**
```bash
# Clear view cache
php artisan view:clear

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear
```

**Assets:**
```bash
# Rebuild assets
npm run build

# Or for development
npm run dev
```

---

## 📊 Best Practices

### Content Management

1. **Struktur Nama:**
   - Title: Sesuai official (English/Japanese)
   - Slug: lowercase-with-dashes
   - Episode: Konsisten (S01E01 atau Episode 1)

2. **Image Guidelines:**
   - Poster: 300x450px (2:3 ratio)
   - Cover: 1920x1080px (16:9 ratio)
   - Quality: High (80-90%)
   - Format: WebP (optimal) atau JPG

3. **Content Quality:**
   - Synopsis: 100-300 karakter
   - Description: Jelas dan menarik
   - Rating: Sesuai sumber (MAL/AniList)
   - Genre: Max 5-6 genre per anime

### Security

1. **Password Policy:**
   - Min 12 karakter
   - Kombinasi huruf, angka, simbol
   - Ganti password setiap 3 bulan
   - Jangan share akun admin

2. **Access Control:**
   - Minimal privilege principle
   - Review admin list regularly
   - Revoke akses yang tidak perlu

3. **Data Protection:**
   - Backup database weekly
   - Export critical data
   - Test restore procedure
   - Keep Laravel & packages updated

### Workflow

1. **Adding New Anime:**
   ```
   1. Create anime (basic info)
   2. Upload poster
   3. Assign genres
   4. Create episodes
   5. Add video servers
   6. Set schedule (if ongoing)
   7. Publish to homepage
   ```

2. **Weekly Routine:**
   ```
   Monday:
   - Check scraping logs
   - Review new episodes
   - Update ongoing anime
   
   Wednesday:
   - Backup database
   - Clear cache
   - Check error logs
   
   Friday:
   - Add new releases
   - Update schedules
   - Review user reports
   ```

3. **Monthly Tasks:**
   ```
   - Update completed anime status
   - Archive old scrape logs
   - Review analytics
   - Update documentation
   - Security audit
   ```

---

## 🚀 Keyboard Shortcuts

### Global
- `Ctrl + K` - Global search
- `Ctrl + /` - Toggle sidebar
- `Esc` - Close modal/dialog

### Table
- `Ctrl + F` - Focus search
- `Arrow Up/Down` - Navigate rows
- `Enter` - Open selected row

### Form
- `Ctrl + S` - Save form
- `Tab` - Next field
- `Shift + Tab` - Previous field
- `Ctrl + Enter` - Submit form

---

## 📚 Resources

### Documentation
- Laravel: https://laravel.com/docs
- Filament: https://filamentphp.com/docs
- Livewire: https://laravel-livewire.com

### Support
- GitHub Issues: [Link ke repo]
- Discord Community: [Link]
- Email: admin@nipnime.com

### Tools
- phpMyAdmin: http://localhost/phpmyadmin
- Laravel Telescope: http://localhost:8000/telescope (if installed)
- Laravel Horizon: http://localhost:8000/horizon (if installed)

---

## ✅ Quick Reference

### Must-Know URLs
```
Admin Panel:    http://localhost:8000/admin
Login:          http://localhost:8000/admin/login
Dashboard:      http://localhost:8000/admin
Logout:         http://localhost:8000/admin/logout
```

### Default Credentials
```
Email:          admin@example.com
Password:       password
```

### Important Commands
```bash
# Create admin
php create_admin.php

# Make existing user admin
php quick_make_admin.php

# List all users
php list_users.php

# Check admin status
php debug_admin.php

# Run migrations
php artisan migrate

# Clear all cache
php artisan optimize:clear

# Create storage link
php artisan storage:link
```

### Database Info
```
Host:           localhost
Port:           3306 (MySQL) / 5432 (PostgreSQL)
Database:       web_anime
Username:       root
Password:       (empty for XAMPP)
```

---

## 🎉 Conclusion

Panel admin Filament menyediakan interface yang powerful dan user-friendly untuk mengelola website anime Anda. Dengan mengikuti panduan ini, Anda dapat:

✅ Mengelola anime, episode, dan genre dengan mudah
✅ Mengatur user dan permission
✅ Menggunakan sistem scraping otomatis
✅ Monitor dan maintain website dengan efisien
✅ Troubleshoot masalah umum

**Happy Managing! 🚀**

---

*Dokumen ini terakhir diupdate: 27 Desember 2025*
*Versi Panel: Filament v3.x*
*Framework: Laravel 10.x*
