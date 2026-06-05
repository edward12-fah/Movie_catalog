<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once "../config/database.php";
/** @var mysqli $conn */ //
require_once "jwt_middleware.php";

$userData = verifyJWT();
$method = $_SERVER['REQUEST_METHOD'];

// Hanya Admin yang boleh mengelola atau melihat daftar User
if ($userData->role !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Akses Ditolak! Fitur khusus Admin."]); exit;
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // GET 1: Berdasarkan ID
        $id = intval($_GET['id']);
        $res = mysqli_query($conn, "SELECT id, name, email, role FROM users WHERE id=$id"); 
        $data = $res->fetch_assoc();
        echo json_encode(["success" => (bool)$data, "data" => $data]);
    } elseif (isset($_GET['search'])) {
        // GET 2: Berdasarkan Pencarian Nama
        $search = mysqli_real_escape_string($conn, $_GET['search']);
        $res = mysqli_query($conn, "SELECT id, name, email, role FROM users WHERE name LIKE '%$search%'");
        $d = []; while($r = $res->fetch_assoc()) $d[] = $r;
        echo json_encode(["success" => true, "data" => $d]);
    } else {
        // GET 3: Ambil Semua + Pagination
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        $countQuery = mysqli_query($conn, "SELECT COUNT(id) as total FROM users");
        $totalData = $countQuery->fetch_assoc()['total'];
        
        $query = "SELECT id, name, email, role FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $res = mysqli_query($conn, $query);
        $d = []; while($r = $res->fetch_assoc()) $d[] = $r;
        
        echo json_encode([
            "success" => true, "data" => $d,
            "pagination" => ["current_page" => $page, "total_pages" => ceil($totalData / $limit), "total_data" => $totalData, "limit" => $limit]
        ]);
    }
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'POST') {
        $name = mysqli_real_escape_string($conn, $input['name']);
        $email = mysqli_real_escape_string($conn, $input['email']);
        $password = $input['password']; // Di tahap lanjut, gunakan password_hash()
        $role = mysqli_real_escape_string($conn, $input['role']);
        
        if (mysqli_query($conn, "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')")) {
            echo json_encode(["success" => true, "message" => "User berhasil ditambahkan"]);
        }
    } elseif ($method === 'PUT') {
        $id = intval($input['id']);
        $name = mysqli_real_escape_string($conn, $input['name']);
        $email = mysqli_real_escape_string($conn, $input['email']);
        $role = mysqli_real_escape_string($conn, $input['role']);
        
        if (mysqli_query($conn, "UPDATE users SET name='$name', email='$email', role='$role' WHERE id=$id")) {
            echo json_encode(["success" => true, "message" => "User diperbarui"]);
        }
    } elseif ($method === 'DELETE') {
        $id = intval($input['id']);
        if (mysqli_query($conn, "DELETE FROM users WHERE id=$id")) {
            echo json_encode(["success" => true, "message" => "User dihapus"]);
        }
    }
}
?>