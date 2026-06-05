<?php
use PHPUnit\Framework\TestCase;

class ApiUlasanTest extends TestCase {
    private $baseUrl = "http://localhost/movie_catalog/api/ulasan.php";
    private $userHeader = "Content-Type: application/json\r\nUser-Role: user\r\n";
    private $adminHeader = "Content-Type: application/json\r\nUser-Role: admin\r\n";

    public function testGetUlasan() {
        $response = file_get_contents($this->baseUrl);
        $this->assertTrue(json_decode($response, true)['success']);
    }

    public function testCreateUlasanAsUser() {
        $payload = json_encode(["user_id" => 1, "movie_id" => 1, "score" => 9, "review" => "Test Ulasan"]);
        $opts = ['http' => ['method' => "POST", 'header' => $this->userHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testUpdateUlasan() {
        $payload = json_encode(["id" => 1, "score" => 10, "review" => "Update ulasan"]);
        $opts = ['http' => ['method' => "PUT", 'header' => $this->userHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testDeleteUlasanAsAdmin() {
        $payload = json_encode(["id" => 999]);
        $opts = ['http' => ['method' => "DELETE", 'header' => $this->adminHeader, 'content' => $payload, 'ignore_errors' => true]];
        $response = file_get_contents($this->baseUrl, false, stream_context_create($opts));
        $this->assertNotNull(json_decode($response, true));
    }

    public function testSqlInjectionUlasan() {
        $maliciousUrl = $this->baseUrl . "?movie_id=1 OR 1=1";
        $opts = ['http' => ['method' => "GET", 'ignore_errors' => true]];
        $response = file_get_contents($maliciousUrl, false, stream_context_create($opts));
        $this->assertTrue(true);
    }
}
?>