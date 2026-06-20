<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/database.php";
/** @var mysqli $conn */ //

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $email = mysqli_real_escape_string($conn, $input['email']);
    $new_password = $input['new_password'];
    $confirm_password = $input['confirm_password'];

    // Validasi input kosong
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(["success" => false, "message" => "Semua kolom harus diisi!"]);
        exit;
    }

    // Validasi kesamaan password
    if ($new_password !== $confirm_password) {
        echo json_encode(["success" => false, "message" => "Password baru dan konfirmasi tidak cocok!"]);
        exit;
    }

    // Cek apakah email terdaftar
    $query = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if ($query->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Email tidak terdaftar dalam sistem!"]);
        exit;
    }

    // Hash (enkripsi) password baru
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password di database
    if (mysqli_query($conn, "UPDATE users SET password = '$hashed_password' WHERE email = '$email'")) {
        echo json_encode(["success" => true, "message" => "Password berhasil direset! Silakan login dengan password baru Anda."]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal memperbarui password: " . mysqli_error($conn)]);
    }
}
?>