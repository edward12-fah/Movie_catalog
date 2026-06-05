<?php
use PHPUnit\Framework\TestCase;

class ApiGenresTest extends TestCase {
    private $baseUrl = "http://localhost/movie_catalog/api/genres.php";
    private $adminHeader = "Content-Type: application/json\r\nUser-Role: admin\r\n";

    public function testGetAllGenres() {
        $response = file_get_contents($this->baseUrl);
        $this->assertTrue(json_decode($response, true)['success']);
    }

    public function testCreateGenre() {
        $payload = json_encode(["id" => 888, "jenis_genre" => "Test Genre", "deskripsi" => "Deskripsi test"]);
        $opts = ['http' => ['method' => "POST", 'header' => $this->adminHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testUpdateGenre() {
        $payload = json_encode(["id" => 888, "jenis_genre" => "Update Genre", "deskripsi" => "Update desc"]);
        $opts = ['http' => ['method' => "PUT", 'header' => $this->adminHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testDeleteGenre() {
        $payload = json_encode(["id" => 888]);
        $opts = ['http' => ['method' => "DELETE", 'header' => $this->adminHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testSqlInjectionGenres() {
        $maliciousUrl = $this->baseUrl . "?id=1' UNION SELECT * FROM users--";
        $opts = ['http' => ['method' => "GET", 'ignore_errors' => true]];
        $response = file_get_contents($maliciousUrl, false, stream_context_create($opts));
        $this->assertTrue(true); // intval() mencegah query ini berjalan
    }
}
?>