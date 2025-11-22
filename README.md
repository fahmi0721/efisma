# 📊 E‑FISMA — Sistem Informasi Keuangan & Manajemen Akuntansi

E‑FISMA adalah aplikasi berbasis web yang dirancang untuk membantu perusahaan dalam mengelola proses akuntansi, pencatatan transaksi, laporan keuangan, dan manajemen periode akuntansi secara efisien.

---

## 🚀 Fitur Utama

### 📌 Manajemen Akun & Saldo Awal
- Pengaturan COA (Chart of Accounts)
- Saldo awal per akun & per entitas
- Otomatis bergulir ke periode berikutnya saat closing

### 🧾 Transaksi Jurnal  
- Pencatatan jurnal harian  
- Jurnal otomatis laba rugi bulanan  
- Jurnal penutup akhir tahun  
- Kontrol status (draft, posted, void)

### 📘 Buku Besar  
- Rekap mutasi debit/kredit  
- Filter per akun, entitas, dan periode  
- Export ke Excel

### 📈 Laporan Keuangan  
- Neraca  
- Laba Rugi  
- Arus Kas  
- Komposisi Aset & Liabilitas (Pie/Donut Chart)  
- Dashboard analitik real‑time  

### 🗓️ Manajemen Periode Akuntansi  
- Open/Close Periode  
- Validasi jurnal sebelum closing  
- Generate saldo awal otomatis  

### 👥 Manajemen Partner  
- Customer & Vendor  
- Integrasi dengan transaksi piutang & hutang  

---

## 📸 Screenshot Aplikasi (Placeholder)
![Dashboard](https://raw.githubusercontent.com/fahmi0721/efisma/main/image.png)

---

## 📚 Panduan Penggunaan (Tutorial Singkat)

### 1️⃣ Setup Awal  
1. Tambahkan entitas  
2. Konfigurasi akun COA  
3. Input saldo awal awal tahun  

### 2️⃣ Input Transaksi  
1. Masuk menu **Jurnal Umum**  
2. Pilih entitas  
3. Isi debit/kredit  
4. Posting jurnal  

### 3️⃣ Closing Bulanan  
1. Pastikan semua jurnal sudah *posted*  
2. Buka menu **Periode Akuntansi**  
3. Klik **Close**  
4. Sistem akan:  
   - Menghitung saldo akhir  
   - Membuat jurnal laba rugi bulanan  
   - Meneruskan saldo awal ke bulan berikutnya  

### 4️⃣ Closing Akhir Tahun  
- Sistem otomatis membuat jurnal penutup (CLS)  
- Laba rugi tahun berjalan dipindahkan ke akun laba ditahan  

---

## 🛠️ Teknologi yang Digunakan
- Laravel  
- Blade Template + Admintle UI  
- jQuery + DataTables  
- Chart.js  
- MySQL  
- Excel Export (.xlsx)  

---

## 👨‍💻 Developer  
**Fahmi Idrus** — Fullstack Web Developer (Laravel)

---

## 📄 Lisensi  
Aplikasi ini dirilis menggunakan **MIT License** — Anda bebas menggunakan, memodifikasi, dan mendistribusikan.
