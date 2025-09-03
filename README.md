<h1>🍽️ Aplikasi Kasir Restoran - Proyek RPL Semester Ganjil</h1>

Aplikasi ini dikembangkan sebagai bagian dari proyek semester ganjil pada mata pelajaran **Rekayasa Perangkat Lunak (RPL)**.  
🎯 Tujuan dari aplikasi ini adalah untuk mempermudah proses transaksi di sebuah restoran melalui sistem kasir berbasis web.  

🛠️ Aplikasi ini dibangun menggunakan **PHP Native** dan **MySQL** sebagai penyimpanan data.  

---

## ✨ Fitur Utama Aplikasi

### 👨‍💼 Untuk Admin / Manajer
- 👤 Pengelolaan data pengguna (menambahkan & mengelola akun kasir serta kitchen)  
- 📋 Pengelolaan menu restoran (tambah/ubah/hapus makanan & minuman)  
- 📊 Melihat laporan penjualan  

### 💰 Untuk Kasir
- 📝 Mencatat pesanan pelanggan  
- 💵 Menghitung total pembelian  
- 🖨️ Mencetak struk pembayaran  
- 📂 Melihat riwayat transaksi  

### 👨‍🍳 Untuk Dapur (Kitchen)
- 🍲 Melihat daftar pesanan yang masuk  
- ✅ Mengupdate status pesanan (diproses / selesai)  

---

## 🎯 Kesimpulan
Aplikasi ini diharapkan dapat menjadi solusi sederhana namun efektif dalam **digitalisasi proses kasir di restoran kecil hingga menengah**, sekaligus menjadi sarana pembelajaran dalam pengembangan aplikasi web berbasis framework.

## 🖼️ Desain Antarmuka (UI)

Berikut adalah tampilan antarmuka aplikasi kasir restoran yang telah dikembangkan:

### 💰 Halaman Kasir
Halaman kasir digunakan untuk mencatat pesanan pelanggan, menghitung total pembayaran, serta mencetak struk transaksi.
![UI Kasir](./assets/ui-kasir.png)

---

## 🔄 Diagram / Flowchart Sistem

Untuk memperjelas alur kerja aplikasi, berikut adalah flowchart sistem kasir restoran:

### 📌 activity
berikut adalah diagram activity dari aplikasi KasirKita
![diagram activty] (./diagram/actvity.png)


### 📌 Alur Transaksi
Setelah pesanan dibuat, kasir menghitung total pembayaran → menerima uang → mencetak struk → menyimpan data ke database.
![Flowchart Transaksi](./assets/flowchart-transaksi.png)

### 📌 Alur Pengelolaan Admin
Admin/manajer dapat mengelola data pengguna, menu, serta melihat laporan penjualan secara langsung.
![Flowchart Admin](./assets/flowchart-admin.png)

