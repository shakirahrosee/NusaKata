<?php
// db.php - Sambungan ke database MySQL untuk projek NusaKata

// Maklumat sambungan database
$servername = "localhost";  // biasanya localhost
$username = "root";         // username MySQL awak
$password = "";             // password MySQL awak
$dbname = "nusakata_db";    // nama database

// Cipta sambungan
$conn = new mysqli($servername, $username, $password, $dbname);

// Semak sambungan
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Fungsi untuk dapatkan semua data dari table kamus_arkaik
function getAllKamus() {
    global $conn;
    $sql = "SELECT * FROM kamus_arkaik ORDER BY id ASC";
    $result = $conn->query($sql);

    $data = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Fungsi untuk dapatkan kata berdasarkan ID
function getKataById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM kamus_arkaik WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fungsi untuk search kata
function searchKata($keyword) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM kamus_arkaik WHERE kata LIKE ?");
    $likeKeyword = "%".$keyword."%";
    $stmt->bind_param("s", $likeKeyword);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

// Tutup sambungan (boleh panggil bila dah selesai)
function closeConnection() {
    global $conn;
    $conn->close();
}
?>
