# 🎨 Panduan Sistem Desain (Berbasis Tailwind CSS)

Dokumen ini menjadi acuan utama dalam merancang antarmuka pengguna (UI) agar konsisten, intuitif, responsif, dan mudah dipelihara pada berbagai skala proyek, dengan Tailwind CSS sebagai fondasi utilitas utama.

## 1. Filosofi & Prinsip Utama
- **Fungsi di Atas Dekorasi:** Utamakan pengalaman pengguna (sederhana, konsisten, responsif, cepat, aksesibel). Estetika harus mendukung fungsi, bukan mengaburkannya.
- **Mobile First:** Mulai rancangan dengan *prefix* utilitas dasar (layar kecil), lalu gunakan breakpoint Tailwind (`sm:`, `md:`, `lg:`) untuk layar yang lebih besar.
- **Konsistensi Visual:** Gunakan komponen UI dan kelas kombinasi yang identik. Hindari mengarang kombinasi kelas acak jika komponen serupa sudah ada.
- **Simplicity & Performance:** Hilangkan elemen visual tanpa fungsi. Manfaatkan fitur kompilasi JIT (*Just-in-Time*) Tailwind dan jangan menginjeksikan kelas yang tidak perlu.
- **Prinsip Akhir:** Fokus antarmuka adalah membantu selesainya tugas tanpa paksaan berpikir (*don't make me think*).

## 2. Design Token & Pewarnaan (Tailwind)
- **Warna Utama (Theme Color):** Gunakan kelas warna tema yang sudah terdefinisi pada blok `@theme` di `resources/css/app.css` (contoh: `bg-primary`, `text-primary`). Token `--color-primary-*` dipetakan ke skala `emerald` sebagai default. Hindari *hardcode* warna sembarangan (seperti `bg-[#1abc9c]`) secara *inline*. **Catatan Tailwind v4:** proyek ini tidak lagi menggunakan `tailwind.config.js`; seluruh kustomisasi token warna dan font dilakukan lewat directive `@theme` di file CSS.
- **Warna Netral (Neutral Color):** Gunakan skala warna abu-abu bawaan Tailwind (disarankan `slate` atau `zinc`) untuk teks (`text-slate-900`), latar belakang (`bg-slate-50`), dan garis batas (`border-slate-200`).
- **Warna Semantik:** Gunakan konvensi standar untuk umpan balik (*Success* = `emerald`/`green`, *Warning* = `amber`/`yellow`, *Danger* = `red`/`rose`, *Info* = `blue`/`sky`).
- **Data Visualisasi (Grafik/Chart):** Pengecualian dari Warna Utama; grafik, diagram, atau batang progres mutlak wajib menggunakan set warna statis (misal: `emerald` untuk dataset A, `sky` untuk dataset B) agar identitas perbandingan data tetap konsisten dan tidak luntur karena perubahan warna tema (*theme color*).
- **Merek & Sosial Media:** Pengecualian dari Warna Utama; elemen yang mewakili identitas pihak ketiga (seperti logo Facebook, YouTube, WhatsApp) wajib menggunakan palet warna asli merek tersebut secara statis (misal: `blue` untuk Facebook, `red` untuk YouTube) agar dapat langsung dikenali pengguna.
- **Dark Mode:** Gunakan utilitas bawaan *Dark Mode* dari Tailwind (`dark:bg-slate-900`, `dark:text-white`) pada setiap rancangan antarmuka visual.

## 3. Tipografi
- Batasi variasi *font-family*. Manfaatkan `font-sans`, `font-serif`, atau varian kustom yang terdaftar resmi.
- Terapkan hierarki ketat dengan utilitas ukuran teks Tailwind (`text-sm`, `text-base`, `text-xl`, `text-3xl`).
- Atur ruang bernapas antar kalimat menggunakan `leading-relaxed` atau `leading-snug`, serta batasi lebar baris baca dengan utilitas seperti `max-w-prose`.

## 4. Sistem Layout & Spasial
- **Layout Responsif:** Layar harus elastis beradaptasi dengan utilitas *Flexbox* (`flex`) atau *Grid* (`grid`). Dilarang ada *horizontal scroll*, susunan bertumpuk berantakan, atau teks terpotong.
- **Spacing Scale:** Patuhi murni skala spasi bawaan Tailwind untuk *margin* dan *padding* (`p-2`, `m-4`, `gap-6`, `py-8`). Dilarang menciptakan spasi angka tebakan sembarangan seperti `p-[17px]`.
- **Whitespace:** Berikan ruang bernapas. Kepadatan konten (*clutter*) yang fatal bisa dihindari dengan menaikkan tingkat *padding* dan *gap*.

## 5. Visual Foundation
- **Garis Tepi (Border) & Radius:** Gunakan kelas kelengkungan standar secara global (misal selalu gunakan `rounded-xl` atau `rounded-2xl` untuk *card*). Garis batas harus redup (`border-slate-200`).
- **Bayangan (Shadow) & Elevasi:** Gunakan bayangan standar Tailwind (`shadow-sm`, `shadow-md`, `shadow-xl`) untuk membedakan kedalaman (seperti kartu atau *modal popup*).
- **Animasi (*Motion*):** Transisi hanya bertugas memberi petunjuk (seperti `transition duration-300 hover:scale-105`), dilarang digunakan untuk atraksi visual tanpa makna.

## 6. Komponen Interaktif (UI)
- **Tombol (Button):** Wajib menaati interaksi 6 kondisi: *Default*, *Hover* (`hover:`), *Focus* (`focus:ring`), *Active* (`active:scale-95`), *Disabled* (`disabled:opacity-50`), dan *Loading*.
- **Formulir (Form):** Tidak boleh ada kolom masuk yang buta; sertakan label, *placeholder*, serta visual responsif terhadap kondisi `focus:` dan validasi *error*.
- **Kartu (Card):** Efek interaksi/melayang (seperti `hover:shadow-lg`) HANYA ditoleransi jika seluruh kotak tersebut adalah elemen interaktif (tautan/tombol) yang bisa diklik.
- **State Kosong (Empty State):** Tampilan daftar kosong tak boleh hampa; wajib memajang grafis ringan, pesan, dan tombol panggilan aksi (*Call to Action*).

## 7. Aksesibilitas Mutlak (a11y)
Setiap desain harus ramah bagi semua pengguna:
- **Dukungan Keyboard:** Ramah tabulasi.
- **Fokus Visual:** Wajib menggunakan kelas cincin fokus Tailwind (seperti `focus:ring-2 focus:ring-primary focus:outline-none`) saat elemen disentuh *keyboard*.
- **Titik Sentuh (*Touch Target*):** Elemen ketuk di *mobile* harus memiliki utilitas ukuran memadai (seperti `h-11 w-11` minimal) agar ramah jemari.
