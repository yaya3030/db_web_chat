# Laravel Reverb Real-Time Chat Pro 💬

Aplikasi web chat Pro berbasis *real-time* tanpa *refresh* halaman yang dibangun menggunakan **Laravel 11**, **Laravel Reverb** (WebSocket Server bawaan Laravel), dan **Laravel Echo**. Aplikasi ini mendukung komunikasi instan baik melalui pesan privat (Direct Message) maupun obrolan grup (Group Chat).

---

## ✨ Fitur Utama
- **Real-Time Direct Messaging (DM):** Kirim dan terima pesan antar pengguna secara instan tanpa perlu memuat ulang halaman.
- **Dynamic Group Chat:** Membuat grup baru secara instan dan mengotomatisasi penggabungan anggota ke dalam ruang obrolan grup yang privat.
- **Presence Tracking (Indikator Online):** Melacak status pengguna secara langsung (*live*). Bulatan hijau akan otomatis menyala jika pengguna sedang membuka aplikasi chat dan mati saat mereka keluar.
- **Instant Broadcasting:** Menggunakan implementasi `ShouldBroadcastNow` untuk mem-bypass antrean (*queue*), memastikan pesan terkirim pada detik yang sama saat tombol kirim diklik.
- **Optimized Chat History:** Mengambil riwayat pesan lama secara efisien menggunakan Axios dan merender balon chat secara rapi (sisi kanan untuk pengirim, sisi kiri untuk penerima).

---

## 🚀 Teknologi yang Digunakan
- **Backend:** [Laravel 11](https://laravel.com/)
- **WebSocket Server:** [Laravel Reverb](https://laravel.com/docs/11.x/reverb)
- **Frontend & Real-time Client:** Tailwind CSS (Blade Components), [Laravel Echo](https://laravel.com/docs/11.x/broadcasting#installing-laravel-echo), Axios, Pusher JS Client (sebagai jembatan Reverb).

---

## 🛠️ Panduan Instalasi & Menjalankan Proyek

Jika proyek ini ingin dijalankan kembali di komputer lokal atau komputer lain, ikuti langkah-langkah berikut:

### 1. Clone Repositori
```bash
git clone [https://github.com/username-kamu/nama-repositori-kamu.git](https://github.com/username-kamu/nama-repositori-kamu.git)
cd nama-repositori-kamu