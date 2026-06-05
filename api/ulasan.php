<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once "../config/database.php";
/** @var mysqli $conn */ //

require_once "jwt_middleware.php";

$userData = verifyJWT();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $res = mysqli_query($conn, "SELECT ulasan.*, users.name AS user_name, movies.title AS movie_title FROM ulasan LEFT JOIN users ON ulasan.user_id = users.id LEFT JOIN movies ON ulasan.movie_id = movies.id WHERE ulasan.id=$id");
        echo json_encode(["success" => true, "data" => $res->fetch_assoc()]);
    } else {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        $filter = "";
        if (isset($_GET['movie_id'])) {
            $movie_id = intval($_GET['movie_id']);
            $filter = "WHERE ulasan.movie_id = $movie_id"; 
        } 
        
        // FITUR BARU: Pencarian Berdasarkan Judul Film atau Nama User
        if (isset($_GET['search']) && $_GET['search'] != '') {
            $s = mysqli_real_escape_string($conn, $_GET['search']);
            $filter = "WHERE movies.title LIKE '%$s%' OR users.name LIKE '%$s%'";
        }
        
        $countQuery = mysqli_query($conn, "SELECT COUNT(ulasan.id) as total FROM ulasan LEFT JOIN users ON ulasan.user_id = users.id LEFT JOIN movies ON ulasan.movie_id = movies.id $filter");
        $totalData = $countQuery->fetch_assoc()['total'];
        
        $query = "SELECT ulasan.*, users.name AS user_name, movies.title AS movie_title 
                  FROM ulasan 
                  LEFT JOIN users ON ulasan.user_id = users.id 
                  LEFT JOIN movies ON ulasan.movie_id = movies.id
                  $filter ORDER BY ulasan.id DESC LIMIT $limit OFFSET $offset";
                  
        $res = mysqli_query($conn, $query);
        $d = []; while($r = $res->fetch_assoc()) $d[] = $r;
        
        echo json_encode([
            "success" => true, "data" => $d,
            "pagination" => ["current_page" => $page, "total_pages" => ceil($totalData / $limit), "total_data" => intval($totalData), "limit" => $limit]
        ]);
    }
} else {
    // ... (SISA KODE POST, PUT, DELETE TETAP SAMA SEPERTI SEBELUMNYA)
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'POST') {
        $user_id = $userData->id ?? $userData->user_id; 
        $movie_id = intval($input['movie_id']);
        $score = intval($input['score']);
        $review = mysqli_real_escape_string($conn, $input['review']);
        
        if (mysqli_query($conn, "INSERT INTO ulasan (user_id, movie_id, score, review) VALUES ($user_id, $movie_id, $score, '$review')")) {
            echo json_encode(["success" => true, "message" => "Ulasan terkirim"]);
        } else {
            echo json_encode(["success" => false, "message" => "Gagal: " . mysqli_error($conn)]);
        }
    } elseif ($method === 'DELETE' || $method === 'PUT') {
        if ($userData->role !== 'admin') {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Hanya Admin yang bisa mengubah data ini."]); exit;
        }
        
        if ($method === 'DELETE') {
            $id = intval($input['id']);
            mysqli_query($conn, "DELETE FROM ulasan WHERE id=$id");
            echo json_encode(["success" => true, "message" => "Ulasan dihapus"]);
        } elseif ($method === 'PUT') {
            $id = intval($input['id']);
            $score = intval($input['score']);
            $review = mysqli_real_escape_string($conn, $input['review']);
            mysqli_query($conn, "UPDATE ulasan SET score=$score, review='$review' WHERE id=$id");
            echo json_encode(["success" => true, "message" => "Ulasan diperbarui"]);
        }
    }
}
?>