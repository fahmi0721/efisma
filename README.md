# EFISMA  
Sistem Informasi Akuntansi & Keuangan Berbasis Web – Laravel 11

EFISMA adalah aplikasi sistem informasi akuntansi (E-Accounting) yang dibangun menggunakan **Laravel**, digunakan untuk mengelola transaksi jurnal, buku besar, laporan keuangan, neraca, laba rugi, dan manajemen periode akuntansi secara otomatis.

Aplikasi ini dirancang untuk perusahaan yang membutuhkan proses pembukuan yang cepat, akurat, dan terintegrasi.

---

## ✨ Fitur Utama

### 📌 1. Manajemen Jurnal
- Input jurnal umum (general journal)
- Jurnal otomatis laba rugi bulanan (LRB)
- Jurnal penutup (closing entries)
- Posting jurnal ke buku besar

### 📌 2. Buku Besar (General Ledger)
- Ringkasan transaksi berdasarkan akun
- Filter berdasarkan periode & entitas
- Export Excel

### 📌 3. Saldo Awal
- Import & input saldo awal akun neraca
- Perhitungan otomatis saldo awal periode berikut

### 📌 4. Periode Akuntansi
- Open & close periode
- Validasi draft/void journal sebelum closing
- Generate saldo awal otomatis
- Closing akhir tahun (laba rugi ditutup → laba ditahan)

### 📌 5. Laporan Keuangan
- Laporan Laba Rugi
- Neraca
- Trail Balance (neraca saldo)
- Export Excel / PDF

### 📌 6. Master Data
- Akun Perkiraan (Chart of Accounts)
- Entitas / Unit Bisnis
- Partner / Relasi Transaksi

---

## 🛠️ Teknologi yang Digunakan

- **Laravel 11**
- **MySQL / MariaDB**
- **Blade View**
- **Yajra Datatables (Server-side)**
- **Maatwebsite/Laravel-Excel**
- **Bootstrap 5 / Admintle Template**
- **jQuery & Ajax**

---

## 📦 Instalasi

### 1. Clone repository
git clone https://github.com/fahmi0721/efisma.git

cd efisma
### 2. Install dependency
composer install
npm install
npm run build
### 3. Copy environment file
cp .env.example .env
### 4. Generate key
php artisan key:generate
### 5. Migrasi database
php artisan migrate
### 6. Jalankan server
php artisan serve
---

## 📁 Struktur Direktori Penting
app/
Http/Controllers/ → Logika aplikasi
Models/ → Model database
resources/views/ → Blade template (frontend)
routes/web.php → Route aplikasi
database/migrations/ → Struktur tabel database
public/ → Assets frontend