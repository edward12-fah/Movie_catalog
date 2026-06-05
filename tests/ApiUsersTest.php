<?php
use PHPUnit\Framework\TestCase;

class ApiUsersTest extends TestCase {
    private $baseUrl = "http://localhost/movie_catalog/api/users.php";
    private $adminHeader = "Content-Type: application/json\r\nUser-Role: admin\r\n";

    public function testGetAllUsers() {
        $response = file_get_contents($this->baseUrl);
        $data = json_decode($response, true);
        $this->assertTrue($data['success']);
    }

    public function testCreateUser() {
        $payload = json_encode([
            "name" => "User Test", "email" => "test@gmail.com", 
            "password" => "12345", "role" => "user"
        ]);
        $opts = ['http' => ['method' => "POST", 'header' => $this->adminHeader, 'content' => $payload]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertTrue(json_decode($response, true)['success']);
    }

    public function testUpdateUser() {
        $payload = json_encode(["id" => 3, "name" => "User Update", "email" => "test@gmail.com", "role" => "admin"]);
        $opts = ['http' => ['method' => "PUT", 'header' => $this->adminHeader, 'content' => $payload]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testDeleteUser() {
        $payload = json_encode(["id" => 999]); 
        $opts = ['http' => ['method' => "DELETE", 'header' => $this->adminHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testSqlInjectionUsers() {
        $maliciousUrl = $this->baseUrl . "?email=admin@gmail.com' OR '1'='1";
        $opts = ['http' => ['method' => "GET", 'ignore_errors' => true]];
        $response = file_get_contents($maliciousUrl, false, stream_context_create($opts));
        $data = json_decode($response, true);
        
        // Pastikan tidak mengembalikan seluruh data jika diinjeksi
        if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 1) {
            $this->fail("API Users rentan SQL Injection");
        } else {
            $this->assertTrue(true);
        }
    }
}
?>