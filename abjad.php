<?php
// abjad.php - Paparan kad A-Z dengan navigasi, pautan ke senarai.php, dan fungsi carian kata dengan database
// Sambung ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nusakata_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Handle carian kata via AJAX (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search') {
    $input = trim($_POST['perkataan']);
    if (empty($input)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Sila masukkan kata untuk dicari.']);
        exit;
    }

    // ✅ Betulkan nama jadual ke arkis
    $stmt = $conn->prepare("SELECT * FROM kamus_arkaik WHERE LOWER(perkataan) = LOWER(?)");
    $stmt->bind_param("s", $input);
    $stmt->execute();
    $result = $stmt->get_result();

    header('Content-Type: application/json; charset=utf-8');
    if ($result->num_rows > 0) {
        // ✅ Betulkan redirect ke lihat.php
        echo json_encode(['status' => 'success', 'redirect' => 'lihat.php?perkataan=' . urlencode($input)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Maaf kata tidak disenaraikan berdasarkan Kamus Dewan Perdana.']);
    }
    $stmt->close();
    $conn->close();
    exit;
}
$conn->close(); // Tutup koneksi untuk bahagian HTML
?>

<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="UTF-8" />
  <title>Senarai Abjad - NusaKata</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, sans-serif;
      background-image: url('Gambar/background 1.jpg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
    }

    .card-container {
      position: relative;
      width: 300px;
      height: 400px;
      perspective: 1000px;
      margin-bottom: 30px;
    }

    .card {
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.2);
      border: 2px solid #fff;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 100px;
      font-weight: bold;
      color: #fff;
      backdrop-filter: blur(10px);
      transition: transform 0.6s ease;
      text-shadow: 2px 2px 8px rgba(0,0,0,0.4);
      cursor: pointer;
      text-decoration: none;
    }

    .nav-buttons {
      display: flex;
      justify-content: space-between;
      width: 160px;
      margin-bottom: 15px;
    }

    .nav-buttons button {
      background-color: rgba(0, 0, 0, 0.5);
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 10px;
      font-size: 20px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .nav-buttons button:hover {
      background-color: rgba(255, 255, 255, 0.3);
    }

    .search-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 15px;
      width: 300px;
    }

    .search-container input {
      width: 100%;
      padding: 10px;
      border: 2px solid #fff;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.2);
      color: #fff;
      font-size: 16px;
      backdrop-filter: blur(10px);
      margin-bottom: 10px;
    }

    .search-container input::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }

    .search-container button {
      background-color: #ff6b6b;
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s;
      box-shadow: 0 3px 8px rgba(0,0,0,0.2);
    }

    .search-container button:hover {
      background-color: #ff5252;
    }

    .action-buttons {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 15px;
      width: 100%;
    }

    .action-buttons a {
      text-decoration: none;
      color: #fff;
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: bold;
      transition: background-color 0.3s;
      box-shadow: 0 3px 8px rgba(0,0,0,0.2);
      display: inline-block;
      text-align: center;
    }

    .select-button {
      background-color: #28a745;
    }

    .select-button:hover {
      background-color: #218838;
    }

    .back-button {
      background-color: #3a7bd5;
    }

    .back-button:hover {
      background-color: #2f6db0;
    }
  </style>
</head>
<body>

  <div class="card-container">
    <a id="abjadCard" class="card" href="senarai.php?abjad=A">A</a>
  </div>

  <div class="nav-buttons">
    <button onclick="prevAbjad()">←</button>
    <button onclick="nextAbjad()">→</button>
  </div>

  <div class="search-container">
    <input type="text" id="searchInput" placeholder="Cari kata..." />
    <button onclick="searchKata()">Cari</button>
  </div>

  <div class="action-buttons">
    <a id="selectButton" class="select-button" href="senarai.php?abjad=A">Pilih</a>
    <a class="back-button" href="menu.html">← Kembali ke Menu</a>
  </div>

  <script>
    const abjad = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split('');
    let currentIndex = 0;

    function updateCard() {
      const card = document.getElementById('abjadCard');
      const selectButton = document.getElementById('selectButton');
      card.style.transform = "scale(0.9)";
      setTimeout(() => {
        const currentLetter = abjad[currentIndex];
        card.textContent = currentLetter;
        card.href = "senarai.php?abjad=" + currentLetter;
        selectButton.href = "senarai.php?abjad=" + currentLetter;
        card.style.transform = "scale(1)";
      }, 150);
    }

    function prevAbjad() {
      currentIndex = (currentIndex - 1 + abjad.length) % abjad.length;
      updateCard();
    }

    function nextAbjad() {
      currentIndex = (currentIndex + 1) % abjad.length;
      updateCard();
    }

    // Fungsi carian kata dengan AJAX ke database
    function searchKata() {
      const input = document.getElementById('searchInput').value.trim();
      if (input === '') {
        alert('Sila masukkan kata untuk dicari.');
        return;
      }

      // Hantar POST request ke abjad.php sendiri (handle oleh PHP di atas)
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'abjad.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          const response = JSON.parse(xhr.responseText);
          if (response.status === 'success') {
            window.location.href = response.redirect; // Redirect ke maklumat.php
          } else {
            alert(response.message); // Papar mesej error
          }
        }
      };
      xhr.send('action=search&perkataan=' + encodeURIComponent(input)); // Guna 'perkataan' untuk POST
    }

    // Event listener untuk Enter key
    document.getElementById('searchInput').addEventListener('keypress', function(event) {
      if (event.key === 'Enter') {
        searchKata();
      }
    });
  </script>

</body>
</html>

