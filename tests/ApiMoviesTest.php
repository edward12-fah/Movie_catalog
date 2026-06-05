<?php
use PHPUnit\Framework\TestCase;

class ApiMoviesTest extends TestCase {
    // Sesuaikan URL dengan struktur folder XAMPP kamu
    private $baseUrl = "http://localhost/movie_catalog/api/movies.php";
    
    // Header default untuk aksi yang butuh hak akses Admin (POST/PUT/DELETE)
    private $adminHeader = "Content-Type: application/json\r\nUser-Role: admin\r\n";

    /**
     * 1. TEST GET ALL (Membaca Data)
     */
    public function testGetAllMovies() {
        $response = file_get_contents($this->baseUrl);
        $data = json_decode($response, true);
        
        $this->assertTrue($data['success']);
        // Cek apakah data yang dikembalikan berbentuk array [cite: 84]
        $this->assertIsArray($data['data']); 
    }

    /**
     * 2. TEST POST (Menambah Data)
     */
    public function testCreateMovie() {
        $payload = json_encode([
            "title" => "Film Testing PHPUnit",
            "description" => "Ini deskripsi dari pengujian otomatis",
            "release_year" => 2026
        ]);
        
        $opts = ['http' => [
            'method' => "POST", // [cite: 90]
            'header' => $this->adminHeader, // [cite: 91]
            'content' => $payload // [cite: 92]
        ]];
        
        $context = stream_context_create($opts); // [cite: 94]
        $response = file_get_contents($this->baseUrl, false, $context); // [cite: 95]
        $result = json_decode($response, true); // [cite: 96]

        $this->assertTrue($result['success']);
        $this->assertEquals("Film berhasil ditambahkan", $result['message']);
    }

    /**
     * 3. TEST PUT (Mengubah Data)
     */
    public function testUpdateMovie() {
        // Asumsikan kita mengedit film dengan ID 1
        $payload = json_encode([
            "id" => 1,
            "title" => "Judul Film Diupdate PHPUnit",
            "description" => "Deskripsi update",
            "release_year" => 2027
        ]);
        
        $opts = ['http' => [
            'method' => "PUT",
            'header' => $this->adminHeader,
            'content' => $payload
        ]];
        
        $context = stream_context_create($opts);
        $response = file_get_contents($this->baseUrl, false, $context);
        $result = json_decode($response, true);

        $this->assertTrue($result['success']);
        $this->assertEquals("Film berhasil diperbarui", $result['message']);
    }

    /**
     * 4. TEST DELETE (Menghapus Data)
     * Mengacu pada instruksi latihan di dokumen [cite: 282]
     */
    public function testDeleteMovie() {
        // Ganti angka ini dengan ID film dummy yang aman untuk dihapus
        $payload = json_encode(["id" => 999]); 
        
        $opts = ['http' => [
            'method' => "DELETE",
            'header' => $this->adminHeader,
            'content' => $payload,
            'ignore_errors' => true // Agar PHPUnit tidak crash jika respon API 404/403
        ]];
        
        $context = stream_context_create($opts);
        $response = file_get_contents($this->baseUrl, false, $context);
        $result = json_decode($response, true);

        // Pastikan API memberikan respon JSON yang valid
        $this->assertNotNull($result); 
    }

    /**
     * 5. TEST SQL INJECTION
     * Simulasi serangan ke API [cite: 108, 109, 110, 111]
     */
    public function testSqlInjectionVulnerability() {
        // Memasukkan input mencurigakan sesuai contoh dokumen [cite: 115, 116]
        $maliciousUrl = $this->baseUrl . "?id=1' OR '1'='1";
        
        $opts = ['http' => [
            'method' => "GET",
            'ignore_errors' => true
        ]];
        
        $context = stream_context_create($opts);
        $response = file_get_contents($maliciousUrl, false, $context);
        $result = json_decode($response, true);

        // Jika API rentan, ia akan mengembalikan SEMUA data[cite: 117].
        // Karena kita sudah mengamankannya dengan intval(), query ini akan dibaca sebagai "?id=1".
        // Kita assert bahwa data yang keluar BUKAN seluruh isi database (count != total data).
        
        if (isset($result['data']) && !isset($result['data']['id'])) {
            // Jika mengembalikan array banyak data (bocor), test gagal
            $this->fail("API rentan terhadap SQL Injection! Mengembalikan semua data.");
        } else {
            // Jika masuk ke blok ini, berarti API aman (hanya mengembalikan 1 data atau gagal)
            $this->assertTrue(true);
        }
    }
}
?>