BANK SAMPAH DIGITAL (PHP + MySQL)
=================================

Kebutuhan: PHP 8.0+ (ekstensi pdo_mysql dan gd aktif), MySQL/MariaDB.
Contoh: XAMPP versi terbaru.

Cara pasang di XAMPP
1. Salin folder "bank-sampah" ke C:\xampp\htdocs\
2. Jalankan Apache dan MySQL di XAMPP Control Panel.
3. Buka http://localhost/phpmyadmin, pilih tab "Import", pilih file database.sql, lalu klik Import.
   (Database "bank_sampah" dibuat otomatis.)
4. Jika user/password MySQL bukan root/kosong, ubah di config.php.
5. Buka http://localhost/bank-sampah/

Akun demo
  petugas   / petugas123   (petugas, kelurahan Air Tawar Barat)
  budi      / budi123      (masyarakat, Air Tawar Barat, ID BS-0002)
  petugas2  / petugas123   (petugas, kelurahan Purus)
  siti      / siti123      (masyarakat, Purus, ID BS-0004)

Catatan
- Kamera untuk scan QR hanya jalan di localhost atau HTTPS.
- Pustaka QR dan scanner dimuat dari CDN, jadi butuh internet.
- Foto profil disimpan di uploads/foto/. Gambar iklan ditaruh di uploads/iklan/
  lalu nama filenya diisi di kolom "gambar" tabel iklan.
- Data wilayah: contoh saja (38 provinsi, Sumatera Barat, sebagian Kota Padang).
  Daerah lain memakai kolom ketik manual. Impor data lengkap ke tabel
  provinsi, kabupaten_kota, dan kelurahan agar semua pilihan tersedia.
- Sebelum dipakai sungguhan: pakai HTTPS, ganti password akun demo, dan
  nonaktifkan tampilan error PHP (display_errors = Off).

Struktur
  index.php        halaman perkenalan
  login.php, register.php, logout.php
  beranda.php      (masyarakat)  setor.php, anggota.php (petugas)
  harga.php, jadwal.php, riwayat.php, profil.php
  inc/lib.php      fungsi bersama, keamanan, tata letak
  api/             data wilayah dan penanda notifikasi dibaca
  assets/          style.css, app.js
  database.sql     skema dan data awal
