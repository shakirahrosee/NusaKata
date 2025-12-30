<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';

$perkataan = isset($_GET['perkataan']) ? trim($_GET['perkataan']) : '';
if ($perkataan === '') {
    echo "Tiada perkataan dipilih.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM kamus_arkaik WHERE perkataan = ?");
$stmt->bind_param("s", $perkataan);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo "Ralat pangkalan data.";
    exit;
}

$row = $result->fetch_assoc();
if (!$row) {
    echo "Perkataan tidak ditemui.";
    exit;
}

$gambar = !empty($row['gambar']) ? $row['gambar'] : 'placeholder.jpg';
$audio_file = !empty($row['audio']) ? $row['audio'] : '';

/* 🔥 PEMBETULAN KOLOM DI SINI 🔥 */
$transkripsi = $row['transkripsi_fonetik'] ?? '';
$makna = $row['makna'] ?? '';
$ayat = $row['ayat'] ?? '';
$cadangan = $row['cadangan_kegunaan'] ?? '';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8" />
<title><?php echo htmlspecialchars($perkataan); ?> — NusaKata</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<style>
  body{
    font-family: Arial, sans-serif;
    background: url('Gambar/background 1.jpg') no-repeat center center fixed;
    background-size: cover;
    margin:20px;
    color:#fff;
  }

  body:before{
    content:"";
    position:fixed;
    top:0;left:0;right:0;bottom:0;
    background:rgba(0,0,0,0.65);
    z-index:-1;
  }

  .card{
    max-width:1000px;
    margin:auto;
    background:rgba(255,255,255,0.08);
    padding:25px;
    border-radius:14px;
    border:2px solid #cfa700;
    box-shadow:0 0 25px rgba(0,0,0,0.8);
  }

  .row{display:flex;gap:25px;flex-wrap:wrap}
  .left{flex:0 0 330px;text-align:center}
  .right{flex:1}

  img{
    width:330px;height:330px;object-fit:cover;
    border-radius:12px;
    border:4px solid #cfa700;
    box-shadow:0 0 20px rgba(0,0,0,0.7);
  }

  h1{margin:0 0 12px;color:#ffd046;text-shadow:0 0 12px #000;}
  .meta p{margin:8px 0;color:#fff;font-size:17px}
  strong{color:#ffd046}

  .audio-player{
    margin-top:15px;
    background:rgba(0,0,0,0.65);
    padding:12px;
    border-radius:10px;
    border:1px solid #cfa700;
  }

  audio{width:100%}

  hr{border:1px solid #ffd046;margin:14px 0}

  a.backbtn{
    background:#111;color:#ffd046;
    border:1px solid #ffd046;
    padding:8px 13px;border-radius:8px;
    text-decoration:none;
    font-weight:bold;
  }
  a.backbtn:hover{
    background:#ffd046;color:#111;
    transition:0.25s;
  }

  .btn{
    padding:8px 14px;border-radius:8px;border:1px solid #cfa700;
    background:#111;color:#ffd046;cursor:pointer;margin-right:5px;font-weight:bold;
  }
  .btn:hover{
    background:#cfa700;color:#111;box-shadow:0 0 10px #ffd046;transition:0.25s;
  }
  .status-box{
    margin-top:10px;background:rgba(255,255,255,0.08);
    padding:10px;border-radius:10px;border:1px solid #ffd046;
  }
</style>
</head>
<body>

<div class="card">
  <div class="row">
    <div class="left">
      <img src="Gambar/<?php echo htmlspecialchars($gambar); ?>" 
           alt="Gambar <?php echo htmlspecialchars($perkataan); ?>"
           onerror="this.src='Gambar/placeholder.jpg'">

      <div class="audio-player">
        <?php if($audio_file): ?>
          <p><strong>Audio sedia ada:</strong></p>
          <audio controls src="audio/<?php echo rawurlencode($audio_file); ?>"></audio>
        <?php else: ?>
          <p><strong>Tiada audio sedia ada.</strong></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="right">
      <h1><?php echo htmlspecialchars($perkataan); ?></h1>

      <div class="meta">
        <p><strong>Transkripsi:</strong> <?php echo nl2br(htmlspecialchars($transkripsi)); ?></p>
        <p><strong>Makna:</strong> <?php echo nl2br(htmlspecialchars($makna)); ?></p>
        <p><strong>Ayat contoh:</strong> <?php echo nl2br(htmlspecialchars($ayat)); ?></p>
        <p><strong>Cadangan kegunaan:</strong> <?php echo nl2br(htmlspecialchars($cadangan)); ?></p>
      </div>

      <hr>

      <h3>🎤 Uji Sebutan</h3>
      <button class="btn" onclick="mulaRakaman()">Mula Ujian</button>
      <div class="status-box">
        <p id="statusAI">Status: Menunggu rakaman...</p>
      </div>

      <div style="margin-top:18px;">
        <a href="abjad.php" class="backbtn">← Kembali</a>
      </div>
    </div>
  </div>
</div>

<script>
const expectedWord = <?php echo json_encode($perkataan); ?>.toLowerCase();
const statusBox = document.getElementById("statusAI");

function mulaRakaman() {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRecognition) {
    alert("Browser anda tidak menyokong fungsi Ujian Sebutan (gunakan Chrome terkini).");
    return;
  }

  const recognition = new SpeechRecognition();
  recognition.lang = "ms-MY";
  recognition.continuous = false;
  recognition.interimResults = false;

  statusBox.innerHTML = "🎙 Mendengar... Sila sebut: <b>" + expectedWord + "</b>";
  recognition.start();

  recognition.onresult = function(event) {
    const result = event.results[0][0].transcript.toLowerCase().trim();
    let similarity = stringSimilarity(result, expectedWord);
    let scorePercent = Math.round(similarity * 100);

    let msg = `✅ Teks dikesan: <b>${result}</b><br>`;
    msg += `🎯 Ketepatan: <b>${scorePercent}%</b><br>`;

    if (scorePercent >= 90) {
      msg += `<span style='color:#00ff7f'>✅ Sangat baik! Sebutan tepat.</span>`;
    } else if (scorePercent >= 60) {
      msg += `<span style='color:#ffd046'>⚠ Sebutan baik tetapi boleh diperbaiki.</span>`;
    } else {
      msg += `<span style='color:#ff4c4c'>❌ Sebutan kurang tepat.</span>`;
    }

    statusBox.innerHTML = msg;
  };

  recognition.onerror = function() {
    statusBox.innerHTML = "❌ Ralat semasa merakam. Cuba lagi.";
  };
}

function stringSimilarity(str1, str2) {
  const longer = str1.length > str2.length ? str1 : str2;
  const shorter = str1.length > str2.length ? str2 : str1;
  const longerLength = longer.length;
  if (longerLength === 0) return 1.0;
  return (longerLength - editDistance(longer, shorter)) / longerLength;
}

function editDistance(a, b) {
  a = a.toLowerCase(); b = b.toLowerCase();
  const costs = [];
  for (let i = 0; i <= a.length; i++) {
    let lastValue = i;
    for (let j = 0; j <= b.length; j++) {
      if (i === 0) { costs[j] = j; } 
      else { 
        if (j > 0) {
          let newValue = costs[j-1];
          if (a[i-1] !== b[j-1])
            newValue = Math.min(Math.min(newValue, lastValue), costs[j]) + 1;
          costs[j-1] = lastValue;
          lastValue = newValue;
        }
      }
    }
    if (i > 0) costs[b.length] = lastValue;
  }
  return costs[b.length];
}
</script>

</body>
</html>

