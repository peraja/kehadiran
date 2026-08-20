# Rancangan Modul Manajemen Rapat

## 1. Konsep Utama

Modul **Manajemen Rapat & Tindak Lanjut** mengelola seluruh siklus rapat:

**Rencanakan → Laksanakan → Dokumentasikan → AI membuat notulen → Tindak lanjut → Arsip**

Setiap rapat menjadi satu record/ruang kerja yang berisi seluruh informasi dan dokumen terkait.

## 2. Struktur Modul

```text
RAPAT
│
├── Detail Rapat
│   ├── Judul
│   ├── Agenda
│   ├── Tanggal & waktu
│   ├── Tempat / Meeting Online
│   ├── Pimpinan
│   └── Penyelenggara
│
├── Peserta
│   ├── Daftar undangan
│   ├── Absensi
│   └── Status kehadiran
│
├── Dokumentasi
│   ├── Foto
│   ├── Video
│   └── Dokumen pendukung
│
├── Notulen
│   ├── Catatan manual
│   ├── Transkrip
│   └── Notulen AI
│
└── Tindak Lanjut
    ├── Keputusan
    ├── Action item
    ├── PIC
    ├── Deadline
    └── Status
```

## 3. Dashboard Manajemen Rapat

Dashboard menampilkan:

- Rapat hari ini
- Rapat minggu ini
- Rapat yang belum memiliki notulen
- Tindak lanjut yang belum selesai
- Rapat terakhir
- Kalender rapat

Contoh informasi:

```text
Rapat Hari Ini       : 3
Rapat Minggu Ini     : 12
Menunggu Notulen     : 4
Tindak Lanjut Belum Selesai : 17
```

## 4. Pembuatan Rapat

### Informasi Rapat

Field yang disediakan:

- Judul rapat
- Nomor rapat
- Agenda
- Tanggal
- Waktu mulai
- Waktu selesai
- Tempat
- Link meeting online
- Pimpinan rapat
- Moderator
- Penyelenggara/unit kerja
- Keterangan

### Peserta

Peserta dipilih dari master pegawai/user aplikasi.

Fitur:

- Tambah peserta
- Hapus peserta
- Tambah berdasarkan unit kerja
- Tambah semua anggota unit kerja
- Status undangan

## 5. Halaman Detail Rapat

Halaman detail menjadi pusat seluruh informasi rapat.

Contoh:

```text
Rapat Koordinasi Pengembangan SPBE
20 Agustus 2026 | 09:00 - 11:30
Ruang Rapat Diskominfo

[PUBLIKASI] [EDIT] [MULAI RAPAT]
```

Tab:

```text
Overview | Peserta | Absensi | Dokumentasi | Notulen | Tindak Lanjut
```

## 6. Absensi Digital

Saat rapat dimulai, sistem menyediakan QR Code absensi.

Alur:

```text
Peserta Scan QR
       ↓
Validasi Peserta
       ↓
Catat Waktu Kehadiran
       ↓
Kehadiran Berhasil
```

Contoh:

```text
SCAN UNTUK HADIR

[ QR CODE ]

Rapat Koordinasi SPBE
20 Agustus 2026
09:00

✓ Kehadiran berhasil dicatat
```

Data kehadiran:

- Nama
- Waktu masuk
- Waktu keluar
- Durasi
- Status
- Metode absensi
- Perangkat

Status:

- Hadir
- Terlambat
- Izin
- Sakit
- Tidak hadir

Admin juga dapat melakukan absensi manual.

## 7. Dokumentasi Foto

Dokumentasi dibuat sebagai album rapat.

Fitur:

- Upload banyak foto
- Preview gallery
- Caption
- Waktu pengambilan
- Uploader
- Lokasi, jika tersedia
- Deteksi peserta dengan AI, sebagai fitur lanjutan

Contoh caption:

> Pimpinan rapat memberikan arahan terkait implementasi SPBE.

## 8. Notulen AI

Fitur AI merupakan salah satu fitur utama.

Alur:

```text
Audio Rapat
    ↓
Speech-to-Text
    ↓
Transkrip
    ↓
AI Processing
    ↓
Notulen Otomatis
```

AI menghasilkan:

### Ringkasan Rapat

Ringkasan singkat mengenai isi rapat.

### Pembahasan

Daftar topik utama yang dibahas.

### Keputusan

Keputusan yang disepakati dalam rapat.

### Tindak Lanjut

Daftar tugas yang harus dikerjakan.

### PIC

Orang/unit yang bertanggung jawab.

### Deadline

Tanggal penyelesaian jika disebutkan atau ditentukan.

### Risiko / Masalah

Permasalahan yang muncul dalam pembahasan.

## 9. AI Action Item Extraction

AI dapat mengenali instruksi dalam percakapan.

Contoh:

> "Pak Budi nanti tolong cek server sebelum Jumat."

AI menghasilkan:

```text
Tugas      : Pengecekan server
PIC        : Budi
Deadline   : 21 Agustus 2026
Status     : Belum selesai
```

Action item kemudian otomatis masuk ke modul **Tindak Lanjut**.

## 10. AI Meeting Assistant

Fitur lanjutan:

### Executive Summary

Ringkasan 3–5 kalimat.

### Key Points

Poin-poin penting pembahasan.

### Decisions

Keputusan rapat.

### Action Items

Tugas yang harus dilakukan.

### Risks / Issues

Masalah dan risiko yang ditemukan.

### Follow-up Meeting

AI dapat memberikan rekomendasi kebutuhan rapat lanjutan.

## 11. Transkrip Rapat

Transkrip asli tetap disimpan sebagai sumber.

Contoh:

```text
09:02 Ahmad:
Selamat pagi...

09:05 Budi:
Untuk perkembangan server...

09:10 Citra:
Dari sisi aplikasi...

09:25 Ahmad:
Baik, kita sepakati...
```

Fitur:

- Search dalam transkrip
- Filter berdasarkan pembicara
- Lompat ke timestamp
- Edit transkrip
- Download transkrip

Pengguna juga dapat bertanya kepada AI:

> "Apa yang dibahas mengenai server?"

AI menjawab berdasarkan transkrip rapat tersebut.

## 12. Notulen Manual + AI

AI tidak menggantikan sekretaris.

Tersedia dua mode:

### Manual

Sekretaris menulis notulen sendiri.

### AI

AI membuat draft notulen.

Alur:

```text
[Generate Notulen AI]
        ↓
Draft Notulen
        ↓
[Edit]
        ↓
[Setujui Notulen]
        ↓
Notulen Final
```

## 13. Approval Notulen

Status dokumen:

```text
Draft
  ↓
AI Generated
  ↓
Diperiksa
  ↓
Disetujui
  ↓
Final
```

Setelah final, dokumen tidak boleh diubah tanpa membuat revisi baru.

Semua perubahan dicatat dalam audit trail.

## 14. Tindak Lanjut

Setiap keputusan dapat menghasilkan action item.

Contoh:

```text
TINDAK LANJUT RAPAT

Verifikasi data OPD
PIC       : Ahmad
Deadline  : 25 Agustus 2026
Status    : Berjalan

Evaluasi server
PIC       : Budi
Deadline  : 27 Agustus 2026
Status    : Belum
```

Status:

- Belum dimulai
- Berjalan
- Selesai
- Terlambat
- Dibatalkan

## 15. Reminder

Sistem memberikan pengingat otomatis.

Contoh:

> Tindak lanjut rapat akan jatuh tempo besok.

atau:

> Tindak lanjut "Evaluasi Server" telah melewati deadline.

Channel yang dapat dikembangkan:

- Notifikasi aplikasi
- Email
- WhatsApp
- Telegram

## 16. Generate Dokumen

Sistem dapat menghasilkan:

### PDF

- Notulen rapat
- Berita acara
- Daftar hadir

### Excel

- Daftar hadir
- Rekap peserta
- Tindak lanjut

### Paket Arsip

```text
Rapat-2026-08-20.zip

├── Notulen.pdf
├── Berita_Acara.pdf
├── Daftar_Hadir.xlsx
├── Dokumentasi/
│   ├── foto-01.jpg
│   ├── foto-02.jpg
│   └── foto-03.jpg
└── Transkrip.txt
```

## 17. Struktur Database

### meetings

```text
id
title
meeting_number
agenda
date
start_time
end_time
location
online_url
chairman_id
organizer_id
status
created_by
created_at
updated_at
```

### meeting_participants

```text
id
meeting_id
user_id
invitation_status
attendance_status
check_in
check_out
created_at
```

### meeting_attendances

```text
id
meeting_id
user_id
check_in
check_out
method
device_info
created_at
```

### meeting_photos

```text
id
meeting_id
file
caption
taken_at
uploaded_by
created_at
```

### meeting_documents

```text
id
meeting_id
file
document_type
uploaded_by
created_at
```

### meeting_transcripts

```text
id
meeting_id
speaker
timestamp
content
created_at
```

### meeting_minutes

```text
id
meeting_id
summary
discussion
decisions
ai_generated
status
approved_by
approved_at
created_at
updated_at
```

### meeting_action_items

```text
id
meeting_id
task
description
assigned_to
deadline
status
completed_at
created_at
updated_at
```

### meeting_revisions

```text
id
meeting_id
document_type
version
content
created_by
created_at
```

## 18. Arsitektur AI

AI sebaiknya dipisahkan dari controller utama aplikasi.

```text
                 APPLICATION
                      │
                      ▼
               Meeting Module
                      │
         ┌────────────┼────────────┐
         ▼            ▼            ▼
      Absensi     Dokumentasi    Notulen
                                   │
                                   ▼
                               AI Service
                                   │
                    ┌──────────────┼──────────────┐
                    ▼              ▼              ▼
               Speech-to-Text     LLM          Summary
                    │              │              │
                    └──────────────┼──────────────┘
                                   ▼
                            Meeting Minutes
```

AI Service sebaiknya menggunakan abstraction layer sehingga provider dapat diganti:

```text
AI Provider
├── OpenAI
├── Gemini
├── Local LLM
└── Provider lainnya
```

## 19. Fitur Unggulan: Meeting Intelligence

Konsep utama:

```text
                 RAPAT SELESAI
                       │
                       ▼
                  Audio Rapat
                       │
                       ▼
                Speech-to-Text
                       │
                       ▼
                   AI Engine
                       │
          ┌────────────┼────────────┐
          ▼            ▼            ▼
       Summary     Decisions    Action Items
          │            │            │
          └────────────┼────────────┘
                       ▼
                 NOTULEN FINAL
                       │
                       ▼
                TINDAK LANJUT
                       │
                       ▼
                  MONITORING
```

Dengan konsep ini, aplikasi tidak hanya menjadi sistem pencatatan rapat, tetapi menjadi sistem yang mengelola siklus:

**Rapat → Keputusan → Tugas → Monitoring → Penyelesaian**

## 20. Tahapan Pengembangan

### Phase 1 — MVP

- [ ] Data rapat
- [ ] Peserta
- [ ] Absensi QR
- [ ] Dokumentasi foto
- [ ] Notulen manual
- [ ] Tindak lanjut
- [ ] PDF/Excel
- [ ] Arsip rapat

### Phase 2 — AI

- [ ] Upload rekaman audio
- [ ] Speech-to-text
- [ ] AI summary
- [ ] AI notulen
- [ ] AI keputusan
- [ ] AI action item
- [ ] AI menentukan PIC/deadline dari pembicaraan
- [ ] Transkrip rapat

### Phase 3 — Advanced

- [ ] Live transcription
- [ ] AI Meeting Assistant
- [ ] Pencarian seluruh rapat dengan AI
- [ ] Dashboard tindak lanjut
- [ ] Reminder otomatis
- [ ] Integrasi kalender
- [ ] Integrasi WhatsApp/Telegram
- [ ] Analitik rapat antar-unit kerja

## 21. Rekomendasi Positioning

Nama modul yang disarankan:

**Manajemen Rapat & Tindak Lanjut**

Bukan hanya **"Notulen Rapat"**, karena nilai utama sistem adalah mengelola seluruh siklus rapat:

> **Rencanakan → Laksanakan → Dokumentasikan → Pahami dengan AI → Putuskan → Tindak Lanjuti → Monitor**

AI menjadi mesin yang mempercepat proses, sedangkan keputusan dan validasi tetap berada pada pengguna/pejabat yang berwenang.
