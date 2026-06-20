<?php
// Mencegah pesan error PHP merusak format JSON ke frontend
error_reporting(0);
ini_set('display_errors', 0);

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
    
    // 1. Ambil data user berdasarkan email SAJA (pastikan kolom password juga ditarik)
    $query = mysqli_query($conn, "SELECT id, name, email, role, password FROM users WHERE email = '$email'");
    $user = mysqli_fetch_assoc($query);

    // 2. Gunakan password_verify() untuk mencocokkan input dengan hash di database
    if ($user && password_verify($password, $user['password'])) {
        
        // 3. Siapkan isi payload JWT
        $payload = [
            "user_id" => $user['id'],
            "role" => $user['role'],
            "exp" => time() + 3600 // Token berlaku selama 1 jam
        ];
        
        // 4. Buat Token
        $jwt = JWT::encode($payload, $secret_key, 'HS256');
        
        // 5. Kirim respons ke frontend beserta token
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
        // Jika email tidak ada atau password salah
        echo json_encode(["success" => false, "message" => "Email atau password salah!"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Email dan password diperlukan"]);
}
?>