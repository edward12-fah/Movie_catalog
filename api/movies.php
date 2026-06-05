<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Izinkan header Authorization

require_once "../config/database.php";
/** @var mysqli $conn */ //
require_once "jwt_middleware.php"; // Panggil si Penjaga Gerbang

// 1. Verifikasi JWT Token sebelum melakukan apapun!
// Jika token salah, fungsi ini akan otomatis menghentikan script (exit).
$userData = verifyJWT(); 

// $userData sekarang berisi object: { "user_id": 1, "role": "admin", "exp": 171... }

$method = $_SERVER['REQUEST_METHOD'];

// --- KODE UNTUK BACA DATA (Bisa diakses User & Admin) ---
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $query = "SELECT m.*, g.jenis_genre FROM movies m LEFT JOIN genres g ON m.genre_id=g.id WHERE m.id=$id";
        $res = mysqli_query($conn, $query);
        $data = $res->fetch_assoc();
        
        if ($data) {
            echo json_encode(["success" => true, "data" => $data]);
        } else {
            echo json_encode(["success" => false, "message" => "Film tidak ditemukan"]);
        }
    } else {
        // Logika Pagination yang sudah kita buat sebelumnya
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        $s = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
        
        // --- LOGIKA FILTER PENCARIAN, GENRE, & TAHUN ---
        $filterQuery = "WHERE m.title LIKE '%$s%'";
        
        if (isset($_GET['genre_id']) && $_GET['genre_id'] != '') {
            $g_id = intval($_GET['genre_id']);
            $filterQuery .= " AND m.genre_id = $g_id";
        }
        
        if (isset($_GET['year']) && $_GET['year'] != '') {
            $year = intval($_GET['year']);
            $filterQuery .= " AND m.release_year = $year";
        }

        // --- TAMBAHAN BARU: FILTER BERDASARKAN AKTOR ---
        // Pastikan bagian ini di dalam blok IF GET movies
        if (isset($_GET['actor_id']) && $_GET['actor_id'] != '') {
            $actor_id = intval($_GET['actor_id']);
            // Menggunakan JOIN yang benar untuk memastikan HANYA film dengan aktor tsb yang muncul
            $filterQuery .= " AND m.id IN (SELECT movie_id FROM movie_actors WHERE actor_id = $actor_id)";
        }
        // ---------------------------------------------

$countQuery = mysqli_query($conn, "SELECT COUNT(m.id) as total FROM movies m $filterQuery");
        $totalData = $countQuery->fetch_assoc()['total'];
        $totalPages = ceil($totalData / $limit);

        // --- TAMBAHAN LOGIKA SORTING RATING ---
        $orderQuery = "ORDER BY m.id DESC"; // Default (Film terbaru)
        if (isset($_GET['sort']) && $_GET['sort'] === 'rating') {
            $orderQuery = "ORDER BY m.rating DESC"; // Urutkan rating tertinggi
        }

        $query = "SELECT m.*, g.jenis_genre FROM movies m LEFT JOIN genres g ON m.genre_id=g.id 
                  $filterQuery $orderQuery LIMIT $limit OFFSET $offset";
        $res = mysqli_query($conn, $query);

        $d = []; while($r = $res->fetch_assoc()) $d[] = $r;
        
        echo json_encode([
            "success" => true, 
            "data" => $d,
            "pagination" => [
                "current_page" => $page,
                "total_pages" => $totalPages,
                "total_data" => $totalData,
                "limit" => $limit
            ]
        ]);
    }
} 
// --- KODE UNTUK MODIFIKASI DATA (Hanya untuk Admin) ---
else {
    // 2. CEK ROLE: Tolak mentah-mentah jika bukan admin yang mencoba POST/PUT/DELETE
    if ($userData->role !== 'admin') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Akses Ditolak! Hanya Admin yang boleh melakukan aksi ini."]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'POST') {
        if (isset($input['title']) && isset($input['release_year'])) {
            $title = mysqli_real_escape_string($conn, $input['title']);
            $desc = mysqli_real_escape_string($conn, $input['description'] ?? '');
            $year = intval($input['release_year']);
            $genre_id = intval($input['genre_id'] ?? 0);
            
            $query = "INSERT INTO movies (title, description, release_year, genre_id) VALUES ('$title', '$desc', $year, $genre_id)";
            if (mysqli_query($conn, $query)) {
                echo json_encode(["success" => true, "message" => "Film berhasil ditambahkan"]);
            } else {
                echo json_encode(["success" => false, "message" => "Gagal menambahkan film: " . mysqli_error($conn)]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Data tidak lengkap"]);
        }
    } 
    elseif ($method === 'PUT') {
        if (isset($input['id']) && isset($input['title'])) {
            $id = intval($input['id']);
            $title = mysqli_real_escape_string($conn, $input['title']);
            $desc = mysqli_real_escape_string($conn, $input['description'] ?? '');
            $year = intval($input['release_year']);
            $genre_id = intval($input['genre_id'] ?? 0);
            
            $query = "UPDATE movies SET title='$title', description='$desc', release_year=$year, genre_id=$genre_id WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                echo json_encode(["success" => true, "message" => "Film berhasil diperbarui"]);
            } else {
                echo json_encode(["success" => false, "message" => "Gagal memperbarui film"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Data tidak lengkap"]);
        }
    } 
    elseif ($method === 'DELETE') {
        if (isset($input['id'])) {
            $id = intval($input['id']);
            $query = "DELETE FROM movies WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                echo json_encode(["success" => true, "message" => "Film berhasil dihapus"]);
            } else {
                echo json_encode(["success" => false, "message" => "Gagal menghapus film"]);
            }
        }
    }
}
?>