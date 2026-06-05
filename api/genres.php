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
        // GET 1: Berdasarkan ID
        $id = intval($_GET['id']);
        $res = mysqli_query($conn, "SELECT * FROM genres WHERE id=$id");
        echo json_encode(["success" => true, "data" => $res->fetch_assoc()]);
    } elseif (isset($_GET['search'])) {
        // GET 2: Berdasarkan Pencarian Jenis Genre
        $search = mysqli_real_escape_string($conn, $_GET['search']);
        $res = mysqli_query($conn, "SELECT * FROM genres WHERE jenis_genre LIKE '%$search%'");
        $d = []; while($r = $res->fetch_assoc()) $d[] = $r;
        echo json_encode(["success" => true, "data" => $d]);
    } else {
        // GET 3: Ambil Semua (Tanpa pagination karena data sedikit)
        $res = mysqli_query($conn, "SELECT * FROM genres ORDER BY jenis_genre ASC");
        $d = []; while($r = $res->fetch_assoc()) $d[] = $r;
        echo json_encode(["success" => true, "data" => $d]);
    }
} else {
    if ($userData->role !== 'admin') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Akses Ditolak!"]); exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($method === 'POST') {
        $jenis = mysqli_real_escape_string($conn, $input['jenis_genre']);
        $desc = mysqli_real_escape_string($conn, $input['deskripsi'] ?? '');
        mysqli_query($conn, "INSERT INTO genres (jenis_genre, deskripsi) VALUES ('$jenis', '$desc')");
        echo json_encode(["success" => true, "message" => "Genre ditambahkan"]);
    } elseif ($method === 'PUT') {
        $id = intval($input['id']);
        $jenis = mysqli_real_escape_string($conn, $input['jenis_genre']);
        $desc = mysqli_real_escape_string($conn, $input['deskripsi'] ?? '');
        mysqli_query($conn, "UPDATE genres SET jenis_genre='$jenis', deskripsi='$desc' WHERE id=$id");
        echo json_encode(["success" => true, "message" => "Genre diperbarui"]);
    } elseif ($method === 'DELETE') {
        $id = intval($input['id']);
        mysqli_query($conn, "DELETE FROM genres WHERE id=$id");
        echo json_encode(["success" => true, "message" => "Genre dihapus"]);
    }
}
?>