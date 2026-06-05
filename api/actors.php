<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once "../config/database.php";
/** @var mysqli $conn */ //
require_once "jwt_middleware.php";

$userData = verifyJWT(); // Wajib login
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $res = mysqli_query($conn, "SELECT * FROM actors WHERE id=$id");
        $data = $res->fetch_assoc();
        echo json_encode(["success" => (bool)$data, "data" => $data]);
    } else {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 24; // Default 24 sesuai grid kita
        $offset = ($page - 1) * $limit;
        
        // Menggunakan filter pencarian yang konsisten
        $s = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
        $where = ($s !== '') ? "WHERE actor_name LIKE '%$s%'" : "";
        
        $countQuery = mysqli_query($conn, "SELECT COUNT(id) as total FROM actors $where");
        $totalData = $countQuery->fetch_assoc()['total'];
        
        $query = "SELECT * FROM actors $where ORDER BY actor_name ASC LIMIT $limit OFFSET $offset";
        $res = mysqli_query($conn, $query);
        $d = []; 
        while($r = $res->fetch_assoc()) $d[] = $r;
        
        echo json_encode([
            "success" => true, 
            "data" => $d,
            "pagination" => [
                "current_page" => $page, 
                "total_pages" => ceil($totalData / $limit), 
                "total_data" => intval($totalData), 
                "limit" => $limit
            ]
        ]);
    }
} else {
    // Validasi Akses Admin untuk POST, PUT, DELETE
    if ($userData->role !== 'admin') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Akses Ditolak! Hanya Admin."]); exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'POST') {
        $name = mysqli_real_escape_string($conn, $input['actor_name']);
        $gender = mysqli_real_escape_string($conn, $input['gender']);
        if (mysqli_query($conn, "INSERT INTO actors (actor_name, gender) VALUES ('$name', '$gender')")) {
            echo json_encode(["success" => true, "message" => "Aktor berhasil ditambahkan"]);
        } else {
            echo json_encode(["success" => false, "message" => "Gagal: " . mysqli_error($conn)]);
        }
    } elseif ($method === 'PUT') {
        $id = intval($input['id']);
        $name = mysqli_real_escape_string($conn, $input['actor_name']);
        $gender = mysqli_real_escape_string($conn, $input['gender']);
        if (mysqli_query($conn, "UPDATE actors SET actor_name='$name', gender='$gender' WHERE id=$id")) {
            echo json_encode(["success" => true, "message" => "Aktor diperbarui"]);
        }
    } elseif ($method === 'DELETE') {
        $id = intval($input['id']);
        if (mysqli_query($conn, "DELETE FROM actors WHERE id=$id")) {
            echo json_encode(["success" => true, "message" => "Aktor dihapus"]);
        }
    }
}
?>