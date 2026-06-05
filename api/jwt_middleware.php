<?php
require_once "../vendor/autoload.php";
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function verifyJWT() {
    // Kunci ini HARUS SAMA PERSIS dengan yang ada di login.php
    $secret_key = "KUNCI_RAHASIA_MOVIE_API_YANG_SANGAT_PANJANG_123!";

    $headers = null;
    
    // Trik membaca header Authorization di berbagai jenis server (Apache/Nginx)
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx atau FastCGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    // Jika header Authorization ditemukan
    if (!empty($headers)) {
        // Ekstrak token dari string "Bearer <token>"
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            $token = $matches[1];
            try {
                // Proses verifikasi tanda tangan digital!
                $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
                return $decoded; // Mengembalikan isi token (user_id, role, exp) jika valid
                
            } catch (\Firebase\JWT\ExpiredException $e) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Sesi login telah habis (Expired). Silakan login ulang."]);
                exit;
            } catch (Exception $e) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Token tidak valid atau telah dimanipulasi!"]);
                exit;
            }
        }
    }

    // Jika tidak ada token sama sekali
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Akses ditolak. Token tidak ditemukan."]);
    exit;
}
?>