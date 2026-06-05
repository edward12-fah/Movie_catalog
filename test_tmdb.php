<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
set_time_limit(0);
include 'config/database.php';

$apiKey = "02deb10cfa7f019e3e6f56966c576838";

// 1. TARIK KAMUS GENRE DULU
$url_genre = "https://api.themoviedb.org/3/genre/movie/list?api_key=$apiKey&language=id-ID";
$data_genre = json_decode(@file_get_contents($url_genre), true);

if (isset($data_genre['genres'])) {
    foreach ($data_genre['genres'] as $g) {
        $g_id = $g['id'];
        $g_name = mysqli_real_escape_string($conn, $g['name']);
        
        // Cek apakah genre sudah ada (Berdasarkan ID ATAU Namanya)
        $cek_genre = mysqli_query($conn, "SELECT id FROM genres WHERE id = '$g_id' OR jenis_genre = '$g_name'");
        
        if ($cek_genre->num_rows == 0) {
            mysqli_query($conn, "INSERT INTO genres (id, jenis_genre, deskripsi) VALUES ('$g_id', '$g_name', 'Genre Resmi TMDB')");
        }
    }
}

// 2. TARIK 150 FILM & AKTORNYA (Looping Halaman TMDB)
$target_movies = 150;
$movies_inserted = 0;
$page = 1;

echo "Memulai proses penarikan film...<br>";

// Terus narik data dari halaman 1, 2, 3... sampai target 150 film tercapai
while ($movies_inserted < $target_movies) {
    $url_movie = "https://api.themoviedb.org/3/movie/popular?api_key=$apiKey&language=id-ID&page=$page";
    $data_movies = json_decode(@file_get_contents($url_movie), true);
    
    if (!$data_movies || empty($data_movies['results'])) break; // Berhenti jika API gagal

    foreach ($data_movies['results'] as $m) {
        if ($movies_inserted >= $target_movies) break; // Stop jika sudah 150

        $tmdb_id = $m['id'];
        $title = mysqli_real_escape_string($conn, $m['title']);
        $desc = mysqli_real_escape_string($conn, $m['overview']);
        $poster = "https://image.tmdb.org/t/p/w500" . $m['poster_path'];
        $year = substr($m['release_date'], 0, 4);
        $rating = $m['vote_average'];
        $genre_id = isset($m['genre_ids'][0]) ? $m['genre_ids'][0] : 'NULL';

        // Cek apakah film dengan judul ini SUDAH ADA di database
        $cek_movie = mysqli_query($conn, "SELECT id FROM movies WHERE title = '$title'");
        if ($cek_movie->num_rows > 0) {
            $movie_id_lokal = $cek_movie->fetch_assoc()['id'];
        } else {
            // Jika belum ada, Insert Film Baru
            mysqli_query($conn, "INSERT INTO movies (title, description, poster_url, release_year, rating, genre_id) 
                                 VALUES ('$title', '$desc', '$poster', '$year', '$rating', $genre_id)");
            $movie_id_lokal = mysqli_insert_id($conn);
        }

        // Tarik Aktor (Ambil 5 aktor teratas per film agar lebih ramai)
        $url_credits = "https://api.themoviedb.org/3/movie/$tmdb_id/credits?api_key=$apiKey";
        $credits = json_decode(@file_get_contents($url_credits), true);
        
        if (isset($credits['cast'])) {
            foreach (array_slice($credits['cast'], 0, 5) as $index => $cast) {
                $actor_name = mysqli_real_escape_string($conn, $cast['name']);
                $char = mysqli_real_escape_string($conn, $cast['character']);
                $gender = ($cast['gender'] == 1) ? 'Perempuan' : 'Laki-laki';
                $role_type = ($index < 2) ? 'Utama' : 'Pendukung';
                
                // Cek apakah Aktor ini sudah ada di tabel actors
                $cek_actor = mysqli_query($conn, "SELECT id FROM actors WHERE actor_name = '$actor_name'");
                if ($cek_actor->num_rows > 0) {
                    $actor_id = $cek_actor->fetch_assoc()['id'];
                } else {
                    mysqli_query($conn, "INSERT INTO actors (actor_name, gender) VALUES ('$actor_name', '$gender')");
                    $actor_id = mysqli_insert_id($conn);
                }
                
                // Cek Relasi Movie-Actor agar aktor tidak terdaftar 2x di film yang sama
                $cek_relasi = mysqli_query($conn, "SELECT id FROM movie_actors WHERE movie_id = '$movie_id_lokal' AND actor_id = '$actor_id'");
                if ($cek_relasi->num_rows == 0) {
                    mysqli_query($conn, "INSERT INTO movie_actors (movie_id, actor_id, character_name, role_type) 
                                         VALUES ('$movie_id_lokal', '$actor_id', '$char', '$role_type')");
                }
            }
        }
        $movies_inserted++;
    }
    $page++; // Pindah ke halaman TMDB berikutnya
}

echo "<h1>✅ DATABASE TERISI SEMPURNA</h1><p>Berhasil memproses $movies_inserted film beserta data genre dan aktor tanpa duplikat!</p>";
?>