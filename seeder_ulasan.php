<?php
include 'config/database.php';

echo "<h2>✍️ Memulai Seeder Ulasan...</h2><hr>";

// 1. Ambil semua ID User yang ada di database
$res_users = mysqli_query($conn, "SELECT id FROM users");
$user_ids = [];
while ($row = mysqli_fetch_assoc($res_users)) {
    $user_ids[] = $row['id'];
}

// 2. Ambil semua ID Movie yang ada di database
$res_movies = mysqli_query($conn, "SELECT id FROM movies");
$movie_ids = [];
while ($row = mysqli_fetch_assoc($res_movies)) {
    $movie_ids[] = $row['id'];
}

// Cek jika user atau movie masih kosong
if (empty($user_ids) || empty($movie_ids)) {
    die("❌ Gagal: Pastikan tabel 'users' dan 'movies' sudah ada isinya terlebih dahulu!");
}

// 3. Daftar komentar dummy agar variatif
$komentar_list = [
    "Filmnya keren banget, visualnya memanjakan mata!",
    "Alurnya agak lambat di tengah, tapi endingnya juara.",
    "Akting aktor utamanya luar biasa, sangat mendalami peran.",
    "Kurang suka dengan ceritanya, terlalu mudah ditebak.",
    "Rekomendasi banget buat ditonton bareng keluarga!",
    "Efek suaranya gila sih, berasa nyata banget.",
    "Salah satu film terbaik yang saya tonton tahun ini.",
    "Biasa saja, ekspektasi saya terlalu tinggi sepertinya.",
    "Sinematografinya sangat artistik, setiap adegan adalah wallpaper.",
    "Gak sabar nunggu sekuelnya keluar!"
];

$count = 0;
$jumlah_ulasan_yang_diinginkan = 50; // Kamu bisa ganti angka ini

for ($i = 0; $i < $jumlah_ulasan_yang_diinginkan; $i++) {
    // Ambil ID User acak
    $random_user = $user_ids[array_rand($user_ids)];
    // Ambil ID Movie acak
    $random_movie = $movie_ids[array_rand($movie_ids)];
    // Ambil komentar acak
    $random_review = mysqli_real_escape_string($conn, $komentar_list[array_rand($komentar_list)]);
    // Beri skor acak antara 1 sampai 10 (atau 1-5 sesuai keinginan)
    $random_score = rand(1, 10);

    // Gunakan REPLACE INTO agar jika user yang sama mengulas film yang sama, datanya diperbarui (tidak error)
    $query = "REPLACE INTO ulasan (user_id, movie_id, score, review) 
              VALUES ($random_user, $random_movie, $random_score, '$random_review')";

    if (mysqli_query($conn, $query)) {
        $count++;
    }
}

echo "<h3>✅ Sukses! $count ulasan acak berhasil dimasukkan ke database.</h3>";
?>