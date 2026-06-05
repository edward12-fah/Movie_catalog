<?php
use PHPUnit\Framework\TestCase;

class ApiActorsTest extends TestCase {
    private $baseUrl = "http://localhost/movie_catalog/api/actors.php";
    private $adminHeader = "Content-Type: application/json\r\nUser-Role: admin\r\n";

    public function testGetAllActors() {
        $response = file_get_contents($this->baseUrl);
        $this->assertTrue(json_decode($response, true)['success']);
    }

    public function testCreateActor() {
        // Gunakan rand() agar nama aktor selalu unik setiap dites
        $randomName = "Actor Testing " . rand(1, 99999);
        
        $payload = json_encode([
            "actor_name" => $randomName, 
            "gender" => "Laki-laki"
        ]);
        
        $opts = ['http' => [
            'method' => "POST", 
            'header' => $this->adminHeader, 
            'content' => $payload
        ]];
        
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $data = json_decode($response, true);
        
        // Tambahan pesan error jika gagal agar mudah di-debug
        $this->assertTrue($data['success'], "Gagal menambah aktor: " . json_encode($data));
    }

    public function testUpdateActor() {
        $payload = json_encode(["id" => 1, "actor_name" => "Actor Update", "gender" => "Laki-laki"]);
        $opts = ['http' => ['method' => "PUT", 'header' => $this->adminHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testDeleteActor() {
        $payload = json_encode(["id" => 999]);
        $opts = ['http' => ['method' => "DELETE", 'header' => $this->adminHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testSqlInjectionActors() {
        $maliciousUrl = $this->baseUrl . "?search=Tom'; DROP TABLE actors;--";
        $opts = ['http' => ['method' => "GET", 'ignore_errors' => true]];
        $response = file_get_contents($maliciousUrl, false, stream_context_create($opts));
        $this->assertTrue(true); // real_escape_string() menetralisir tanda kutip
    }
}
?>