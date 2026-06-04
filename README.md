# PadelBook - Sistem Reservasi Lapangan Padel

PadelBook adalah platform aplikasi berbasis web untuk penyewaan dan reservasi lapangan Padel secara online. Sistem ini mencakup fitur untuk pelanggan (member) maupun halaman panel admin untuk pengelolaan data.

## Persyaratan Sistem (Dependencies)

Sebelum memulai instalasi, pastikan sistem Anda memiliki lingkungan berikut:
1. **PHP** (Minimal versi 8.1 atau yang lebih baru)
2. **Composer** (Dependency Manager untuk PHP)
3. **Node.js & npm** (Minimal versi 16.x untuk kompilasi aset frontend)
4. **Database** (SQLite bawaan, MySQL, atau PostgreSQL)
5. **Web Server** (Apache, Nginx, atau bawaan artisan server)

## Panduan Instalasi (Langkah demi Langkah)

Ikuti langkah-langkah di bawah ini untuk menjalankan aplikasi secara lokal di komputer Anda:

### 1. Ekstrak File / Clone Repository
Buka terminal/CMD, arahkan ke folder (direktori) proyek PadelBook.
```bash
cd padelbook
```

### 2. Install Dependensi PHP (Vendor)
Jalankan perintah composer untuk mengunduh semua *package* Laravel yang dibutuhkan:
```bash
composer install
```

### 3. Install & Build Aset Frontend (Node Modules)
Jalankan perintah npm untuk mengunduh dependensi frontend (seperti Bootstrap, AlpineJS, dll) dan melakukan proses kompilasi CSS/JS:
```bash
npm install
npm run build
```

### 4. Konfigurasi Environment (File .env)
Duplikat file `.env.example` dan ubah namanya menjadi `.env`:
- **Windows / CMD**: `copy .env.example .env`
- **Mac / Linux**: `cp .env.example .env`

Secara bawaan (*default*), PadelBook menggunakan **SQLite** agar lebih mudah. Anda tidak perlu mengubah konfigurasi database jika ingin menggunakan SQLite. Jika menggunakan MySQL, silakan sesuaikan bagian `DB_CONNECTION=mysql` dan nama database Anda.

### 5. Generate Application Key
Buat kunci aplikasi Laravel untuk keamanan sesi dan enkripsi:
```bash
php artisan key:generate
```

### 6. Migrasi Database dan Seeding (Data Awal)
Jalankan perintah migrasi untuk membuat tabel database, sekaligus melakukan *seeding* untuk memasukkan data master seperti Akun Admin dan Data Lapangan awal:
```bash
php artisan migrate:fresh --seed
```

### 7. Buat Symbolic Link (Storage Link)
Aplikasi PadelBook menggunakan local storage untuk menyimpan foto lapangan dan profil pengguna. Anda wajib membuat tautan ke folder public agar gambar bisa dimuat di browser:
```bash
php artisan storage:link
```

### 8. Jalankan Server Lokal
Nyalakan server *development* Laravel:
```bash
php artisan serve
```
Aplikasi sekarang dapat diakses melalui browser pada alamat:
👉 **http://localhost:8000**

---

## Akses Bawaan (Default Login)

Untuk mencoba panel Admin, Anda dapat menggunakan kredensial bawaan berikut yang secara otomatis dibuat saat proses migrasi (*seeding*):

- **Halaman Login**: `http://localhost:8000/login`
- **Email**: `admin@padelbook.com`
- **Password**: `password`

Sedangkan untuk membuat akun Member/Pengguna biasa, Anda dapat langsung melakukan registrasi pada halaman pendaftaran (Register) di *frontend*.

---

### Fitur Tambahan (Simulasi Gateway DOKU)
Jika ingin mengaktifkan fungsi pembayaran DOKU secara penuh (bukan *mock/dummy*), silakan edit file `.env` Anda dan masukkan API Key Sandbox dari DOKU:
```env
DOKU_CLIENT_ID="ISI_CLIENT_ID_ANDA"
DOKU_SECRET_KEY="ISI_SECRET_KEY_ANDA"
DOKU_IS_PRODUCTION=false
```
