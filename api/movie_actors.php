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
        $query = "SELECT ma.*, m.title, a.actor_name FROM movie_actors ma 
                  LEFT JOIN movies m ON ma.movie_id = m.id 
                  LEFT JOIN actors a ON ma.actor_id = a.id WHERE ma.id=$id";
        $res = mysqli_query($conn, $query);
        echo json_encode(["success" => true, "data" => $res->fetch_assoc()]);
    } else {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        if (isset($_GET['movie_id'])) {
            $movie_id = intval($_GET['movie_id']);
            $filter = "WHERE ma.movie_id = $movie_id";
        } else {
            $filter = "";
        }
        
        $countQuery = mysqli_query($conn, "SELECT COUNT(ma.id) as total FROM movie_actors ma $filter");
        $totalData = $countQuery->fetch_assoc()['total'];
        
        $query = "SELECT ma.*, m.title, a.actor_name FROM movie_actors ma 
                  LEFT JOIN movies m ON ma.movie_id = m.id 
                  LEFT JOIN actors a ON ma.actor_id = a.id 
                  $filter ORDER BY ma.id DESC LIMIT $limit OFFSET $offset";
                  
        $res = mysqli_query($conn, $query);
        $d = []; while($r = $res->fetch_assoc()) $d[] = $r;
        
        echo json_encode([
            "success" => true, "data" => $d,
            "pagination" => ["current_page" => $page, "total_pages" => ceil($totalData / $limit), "total_data" => $totalData, "limit" => $limit]
        ]);
    }
} else {
    if ($userData->role !== 'admin') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Akses Ditolak!"]); exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'POST') {
        $movie_id = intval($input['movie_id']);
        $actor_id = intval($input['actor_id']);
        $char_name = isset($input['character_name']) ? mysqli_real_escape_string($conn, $input['character_name']) : '';
        
        // Mencegah error jika frontend tidak mengirim role_type
        $role_type = isset($input['role_type']) ? mysqli_real_escape_string($conn, $input['role_type']) : 'Actor';
        
        if (mysqli_query($conn, "INSERT INTO movie_actors (movie_id, actor_id, character_name, role_type) VALUES ($movie_id, $actor_id, '$char_name', '$role_type')")) {
            echo json_encode(["success" => true, "message" => "Casting ditambahkan"]);
        } else {
            echo json_encode(["success" => false, "message" => "Gagal: " . mysqli_error($conn)]);
        }
    } elseif ($method === 'PUT') {
        $id = intval($input['id']);
        $char_name = mysqli_real_escape_string($conn, $input['character_name']);
        $role_type = isset($input['role_type']) ? mysqli_real_escape_string($conn, $input['role_type']) : 'Actor';
        
        if (mysqli_query($conn, "UPDATE movie_actors SET character_name='$char_name', role_type='$role_type' WHERE id=$id")) {
            echo json_encode(["success" => true, "message" => "Casting diperbarui"]);
        }
    } elseif ($method === 'DELETE') {
        $id = intval($input['id']);
        if (mysqli_query($conn, "DELETE FROM movie_actors WHERE id=$id")) {
            echo json_encode(["success" => true, "message" => "Casting dihapus"]);
        }
    }
}
?>