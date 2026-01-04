# CARA IMPORT DATABASE

## Langkah-langkah Import Database

### Metode 1: Menggunakan phpMyAdmin (Recommended)

1. **Buka phpMyAdmin**
   - Buka browser dan akses: `http://localhost/phpmyadmin`

2. **Import Database**
   - Klik tab "Import" di menu atas
   - Klik tombol "Choose File" atau "Pilih File"
   - Pilih file: `database.sql` dari folder `c:\xampp\htdocs\aps_pendftran\`
   - Scroll ke bawah dan klik tombol "Go" atau "Kirim"

3. **Verifikasi**
   - Klik database `uts_web` di sidebar kiri
   - Pastikan tabel berikut sudah ada:
     - `users`
     - `mahasiswa`
     - `pengajuan`
     - `registrations`

### Metode 2: Menggunakan Command Line

1. **Buka Command Prompt**
   - Tekan `Win + R`
   - Ketik `cmd` dan tekan Enter

2. **Masuk ke folder MySQL**
   ```cmd
   cd C:\xampp\mysql\bin
   ```

3. **Import Database**
   ```cmd
   mysql -u root -p < C:\xampp\htdocs\aps_pendftran\database.sql
   ```
   - Jika diminta password, tekan Enter (default tidak ada password)

4. **Verifikasi**
   ```cmd
   mysql -u root -p
   USE uts_web;
   SHOW TABLES;
   ```

## Akun Login Default

Setelah import database, gunakan akun berikut untuk login:

- **Username:** admin
- **Password:** admin123
- **Role:** admin

## Troubleshooting

### Error: "Access denied"
- Pastikan MySQL sudah running di XAMPP Control Panel
- Klik "Start" pada MySQL jika belum running

### Error: "Database not found"
- Database akan otomatis dibuat saat import
- Pastikan file `database.sql` ada di folder yang benar

### Error: "Table already exists"
- Database sudah pernah di-import sebelumnya
- Anda bisa langsung menggunakan aplikasi

## Setelah Import

1. Buka browser dan akses: `http://localhost/aps_pendftran/`
2. Login dengan akun admin (lihat di atas)
3. Mulai menggunakan aplikasi!
