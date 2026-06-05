<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once "../config/database.php";
/** @var mysqli $conn */ //

$input = json_decode(file_get_contents("php://input"), true);

if (isset($input['name']) && isset($input['email']) && isset($input['password'])) {
    $name = mysqli_real_escape_string($conn, $input['name']);
    $email = mysqli_real_escape_string($conn, trim($input['email']));
    $password = $input['password'];
    
    // --- 1. VALIDASI DOMAIN EMAIL ---
    // Memastikan email diakhiri dengan tepat "@email.com"
    if (!str_ends_with(strtolower($email), "@email.com")) {
        echo json_encode(["success" => false, "message" => "Pendaftaran ditolak! Gunakan domain @email.com"]);
        exit; // Hentikan script langsung di sini
    }
    
    // --- 2. CEK EMAIL GANDA ---
    $cek_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    
    if ($cek_email->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Email ini sudah terdaftar! Silakan login."]);
    } else {
        // --- 3. SIMPAN KE DATABASE ---
        // Perhatikan bagian VALUES: Role dikunci mati secara paksa menjadi 'user'
        $query = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', 'user')";
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(["success" => true, "message" => "Pendaftaran berhasil! Mengalihkan ke halaman login..."]);
        } else {
            echo json_encode(["success" => false, "message" => "Gagal mendaftar: " . mysqli_error($conn)]);
        }
    }
} else {
    echo json_encode(["success" => false, "message" => "Mohon isi semua data (Nama, Email, Password)"]);
}
?>