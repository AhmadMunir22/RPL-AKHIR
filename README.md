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

### Fitur Tambahan (Gateway Pembayaran Midtrans)
Jika ingin mengaktifkan fungsi pembayaran Midtrans secara penuh (bukan *mock/dummy*), silakan edit file `.env` Anda dan masukkan Server Key dan Client Key dari Midtrans (Production/Sandbox):
```env
MIDTRANS_SERVER_KEY="Mid-server-xxxxxxxxx"
MIDTRANS_CLIENT_KEY="Mid-client-xxxxxxxxx"
MIDTRANS_IS_PRODUCTION=true
```

---

## Arsitektur Aplikasi & Clean Code

Aplikasi PadelBook telah direfaktorisasi mengikuti standar **Clean Code** untuk mempermudah pemeliharaan sistem (*maintenance*). Berikut adalah poin-poin arsitektur kode yang diterapkan:

### 1. Separation of Concerns (Pemisahan Tanggung Jawab)
Kode program dibagi ke dalam 3 lapisan utama:
- **Controllers** (`app/Http/Controllers`): Bertanggung jawab menangani permintaan HTTP/API, melakukan validasi awal request, merender berkas View, atau membalikkan respon JSON.
- **Actions** (`app/Actions`): Pola desain *Single Responsibility Principle* (SRP). Setiap kelas Action hanya melakukan satu tugas transaksi spesifik yang krusial (misalnya: pembuatan booking utama, pemrosesan status lunas pasca respon gateway).
- **Services** (`app/Services`): Lapisan logika bisnis penunjang seperti perhitungan tarif & diskon voucher, agregasi slot jam, pengelolaan cache ketersediaan (*availability cache*), serta antarmuka pihak ketiga (Kata AI WA API & Midtrans Payment Gateway).

### 2. Type-Hinting yang Ketat (Strict Typing)
Setiap properti, parameter fungsi, dan nilai kembalian (*return values*) dideklarasikan tipe datanya secara eksplisit sesuai fitur modern PHP 8.x. Hal ini mencegah error runtime dan membuat kontrak fungsi menjadi sangat jelas bagi pengembang lain.

### 3. Dokumentasi & Komentar Logika (Maintainability)
- **PHPDoc / DocBlocks**: Setiap kelas dan method dilengkapi blok dokumentasi terstandarisasi yang menerangkan input parameter, tipe respon, exception yang dilempar, serta fungsionalitasnya.
- **Komentar Inline (Bahasa Indonesia)**: Langkah-langkah logika kompleks di dalam method (seperti perhitungan tanda tangan SHA512 Midtrans, pencegahan *double-booking* dengan unique constraint database, serta penanganan validasi sesi OTP) telah diberi penjelasan baris demi baris menggunakan Bahasa Indonesia agar mudah dicari ketika ada pemeliharaan sistem (*maintenance*).

