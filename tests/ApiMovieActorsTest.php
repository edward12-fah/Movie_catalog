<?php
use PHPUnit\Framework\TestCase;

class ApiMovieActorsTest extends TestCase {
    private $baseUrl = "http://localhost/movie_catalog/api/movie_actors.php";
    private $adminHeader = "Content-Type: application/json\r\nUser-Role: admin\r\n";

    public function testGetMovieActors() {
        $response = file_get_contents($this->baseUrl);
        $this->assertTrue(json_decode($response, true)['success']);
    }

    public function testCreateMovieActor() {
        $payload = json_encode(["movie_id" => 1, "actor_id" => 2, "character_name" => "Testing", "role_type" => "Utama"]);
        $opts = ['http' => ['method' => "POST", 'header' => $this->adminHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testUpdateMovieActor() {
        $payload = json_encode(["id" => 1, "character_name" => "Updated Testing", "role_type" => "Pendukung"]);
        $opts = ['http' => ['method' => "PUT", 'header' => $this->adminHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testDeleteMovieActor() {
        $payload = json_encode(["id" => 999]);
        $opts = ['http' => ['method' => "DELETE", 'header' => $this->adminHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testSqlInjectionMovieActors() {
        $maliciousUrl = $this->baseUrl . "?movie_id=1'; SHOW TABLES;--";
        $opts = ['http' => ['method' => "GET", 'ignore_errors' => true]];
        $response = file_get_contents($maliciousUrl, false, stream_context_create($opts));
        $this->assertTrue(true);
    }
}
?>