# ✅ Database Sudah Disatukan!

## Status File Database

Sekarang hanya ada **1 file database** yang lengkap:

### ✅ File yang Digunakan
- **`database.sql`** - File database lengkap untuk aplikasi

### ❌ File yang Dihapus
- ~~`database_web.sql`~~ - Versi lama (tidak lengkap)
- ~~`database_cuti_bersama.sql`~~ - Duplikat dari database.sql

---

## Isi Database.sql

File `database.sql` berisi:

### 1. Database
- Nama: `uts_web`
- Charset: `utf8mb4`
- Collation: `utf8mb4_general_ci`

### 2. Tabel (4 tabel)

#### a. `users` - Pengguna Sistem
- `id` - Primary key
- `username` - Username unik
- `password` - Password (hashed dengan bcrypt)
- `namalengkap` - Nama lengkap
- `role` - Role (admin/user)
- `created_at` - Waktu dibuat

#### b. `mahasiswa` - Data Mahasiswa
- `nim` - Primary key (NIM mahasiswa)
- `nama` - Nama lengkap
- `jurusan` - Jurusan
- `angkatan` - Tahun angkatan
- `email` - Email (opsional)
- `telepon` - Nomor telepon (opsional)
- `created_at` - Waktu dibuat
- `updated_at` - Waktu diupdate

#### c. `pengajuan` - Pengajuan Cuti
- `id` - Primary key
- `user_id` - Foreign key ke users
- `nim` - Foreign key ke mahasiswa
- `tanggal_mulai` - Tanggal mulai cuti
- `tanggal_selesai` - Tanggal selesai cuti
- `jenis_cuti` - Jenis (Sakit/Izin/Cuti Bersama/Lainnya)
- `alasan` - Alasan cuti
- `status` - Status (pending/approved/rejected)
- `approved_by` - User yang approve (opsional)
- `approved_at` - Waktu approve (opsional)
- `catatan_approval` - Catatan approval (opsional)
- `created_at` - Waktu dibuat
- `updated_at` - Waktu diupdate

#### d. `registrations` - Registrasi (Legacy)
- `id` - Primary key
- `user_id` - Foreign key ke users
- `nim` - NIM
- `nama_mk` - Nama mata kuliah
- `registered_at` - Waktu registrasi

### 3. Data Default

#### Admin User
- Username: `admin`
- Password: `admin123` (hashed)
- Role: `admin`

#### Sample Mahasiswa (3 data)
1. **Budi Santoso** - NIM: 2021001, Teknik Informatika
2. **Siti Nurhaliza** - NIM: 2021002, Sistem Informasi
3. **Ahmad Fauzi** - NIM: 2022001, Teknik Informatika

---

## Cara Import Database

### Metode 1: phpMyAdmin (Recommended)

1. Buka http://localhost/phpmyadmin
2. Klik tab "Import"
3. Pilih file **`database.sql`**
4. Klik "Go"

### Metode 2: Command Line

```cmd
cd C:\xampp\mysql\bin
mysql -u root -p < C:\xampp\htdocs\aps_pendftran\database.sql
```

---

## Setelah Import

1. Buka aplikasi: http://localhost/aps_pendftran/
2. Login dengan:
   - Username: `admin`
   - Password: `admin123`
3. Mulai gunakan aplikasi!

---

## Catatan Penting

✅ **Hanya gunakan file `database.sql`**  
❌ **Jangan import file lain** (sudah dihapus)  
✅ **File sudah lengkap** dengan semua tabel dan data sample  
✅ **Siap digunakan** tanpa konfigurasi tambahan
