<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require "../vendor/autoload.php"; // Memanggil library JWT
use Firebase\JWT\JWT;

// Sesuaikan dengan konfigurasi database-mu
require_once "../config/database.php"; 
/** @var mysqli $conn */ //

$secret_key = "KUNCI_RAHASIA_MOVIE_API_YANG_SANGAT_PANJANG_123!";

$input = json_decode(file_get_contents("php://input"), true);

if (isset($input['email']) && isset($input['password'])) {
    $email = mysqli_real_escape_string($conn, $input['email']);
    $password = $input['password']; 
    
    // Asumsi password tidak di-hash (sesuai tahap pembelajaran saat ini)
    $query = mysqli_query($conn, "SELECT id, name, email, role FROM users WHERE email = '$email' AND password = '$password'");
    $user = mysqli_fetch_assoc($query);

    if ($user) {
        // 1. Siapkan isi payload JWT
        $payload = [
            "user_id" => $user['id'],
            "role" => $user['role'],
            "exp" => time() + 3600 // Token berlaku selama 1 jam
        ];
        
        // 2. Buat Token
        $jwt = JWT::encode($payload, $secret_key, 'HS256');
        
        // 3. Kirim respons ke frontend beserta token
        echo json_encode([
            "success" => true,
            "message" => "Login berhasil",
            "token" => $jwt,
            "data" => [
                "name" => $user['name'],
                "role" => $user['role']
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Kredensial tidak valid"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Email dan password diperlukan"]);
}
?>