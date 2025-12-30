<?php
// senarai.php — paparan perkataan ikut abjad (versi dibetulkan & debug-ready)
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';
session_start();

if (!isset($_GET['abjad'])) {
    die("Ralat: Tiada abjad dipilih.");
}

$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

$abjad = strtoupper(substr($_GET['abjad'], 0, 1)); // pastikan hanya 1 huruf

// Ambil kolum yang diperlukan secara eksplisit supaya tiada kekeliruan nama lajur
$sql = "SELECT perkataan, transkripsi_fonetik, makna, ayat, cadangan_kegunaan, gambar, audio 
        FROM kamus_arkaik 
        WHERE perkataan LIKE CONCAT(?, '%') 
        ORDER BY perkataan ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Ralat prepare SQL: " . $conn->error);
}
$stmt->bind_param("s", $abjad);
$stmt->execute();
$result = $stmt->get_result();
$perkataanArr = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($debug) {
    echo "<pre style='color:#fff;background:#222;padding:10px;margin:10px;border-radius:6px;'>";
    echo "DEBUG: \$perkataanArr:\n";
    var_dump($perkataanArr);
    echo "</pre>";
}

if (count($perkataanArr) === 0) {
    echo "<!DOCTYPE html><html lang='ms'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>Tiada Data</title></head><body style='background:#111;color:#fff;font-family:Arial,sans-serif;padding:40px;text-align:center;'><h2>Tiada perkataan bermula dengan huruf " . htmlspecialchars($abjad) . ".</h2><p><a href='abjad.php' style='color:#ffd046'>Kembali ke Abjad</a></p></body></html>";
    exit;
}

// current index (?index=)
$currentIndex = isset($_GET['index']) ? intval($_GET['index']) : 0;
if ($currentIndex < 0) $currentIndex = 0;
if ($currentIndex >= count($perkataanArr)) $currentIndex = count($perkataanArr) - 1;

$perkataanData = $perkataanArr[$currentIndex];

// Ambil masing-masing dari array — gunakan nama lajur yang betul
$perkataan = $perkataanData['perkataan'] ?? '';
$transkripsi = $perkataanData['transkripsi_fonetik'] ?? '';
$makna = $perkataanData['makna'] ?? '';
$ayat = $perkataanData['ayat'] ?? '';
$cadangan = $perkataanData['cadangan_kegunaan'] ?? '';
$gambar = !empty($perkataanData['gambar']) ? $perkataanData['gambar'] : 'placeholder.jpg';
$audio_file = !empty($perkataanData['audio']) ? $perkataanData['audio'] : '';

$conn->close();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8" />
<title><?php echo htmlspecialchars($perkataan); ?> — NusaKata</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<style>
/* (Sama seperti sebelum ini; boleh ubah ikut keperluan) */
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
img{width:330px;height:330px;object-fit:cover;border-radius:12px;border:4px solid #cfa700;box-shadow:0 0 20px rgba(0,0,0,0.7);}
.btn{padding:8px 14px;border-radius:8px;border:1px solid #cfa700;background:#111;color:#ffd046;cursor:pointer;margin-right:5px;font-weight:bold;}
.btn:hover{background:#cfa700;color:#111;box-shadow:0 0 10px #ffd046;transition:0.25s;}
.status-box{margin-top:10px;background:rgba(255,255,255,0.08);padding:10px;border-radius:10px;border:1px solid #ffd046;}
audio{width:100%}
a.backbtn{background:#111;color:#ffd046;border:1px solid #ffd046;padding:8px 13px;border-radius:8px;text-decoration:none;font-weight:bold;}
.nav-row{display:flex;justify-content:space-between;gap:10px;margin-top:20px;align-items:center;}
.nav-link{text-decoration:none;}
.nav-small{background:#cfa700;color:#111;border:none;padding:10px 16px;border-radius:8px;font-weight:bold;cursor:pointer;}
.info-block p{margin:8px 0}
@media (max-width:800px){.row{flex-direction:column;align-items:center} img{width:260px;height:260px}}
.transkripsi-val{display:inline-block;padding:6px 8px;background:rgba(0,0,0,0.18);border-radius:6px;border:1px solid rgba(255,255,255,0.05);}
</style>
</head>
<body>

<div class="card">
  <div class="row">
    <div class="left">
      <img src="Gambar/<?php echo htmlspecialchars($gambar); ?>" alt="Gambar <?php echo htmlspecialchars($perkataan); ?>" onerror="this.src='Gambar/placeholder.jpg'">

      <div class="audio-player" style="margin-top:12px">
        <?php if($audio_file): ?>
          <p><strong>Audio Sedia Ada:</strong></p>
          <audio controls src="audio/<?php echo rawurlencode($audio_file); ?>"></audio>
        <?php else: ?>
          <p><strong>Tiada audio sedia ada.</strong></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="right">
      <h1 style="margin-top:0;"><?php echo htmlspecialchars($perkataan); ?></h1>

      <p><strong>Transkripsi:</strong>
         <span class="transkripsi-val">
           <?php
             // Papar transkripsi — kalau kosong, tunjuk fallback supaya mudah dikesan
             echo $transkripsi !== '' ? nl2br(htmlspecialchars($transkripsi)) : '<em>— Tiada transkripsi ditemui —</em>';
           ?>
         </span>
      </p>

      <div class="info-block">
        <p><strong>Makna:</strong></p>
        <div style="background:rgba(0,0,0,0.25);padding:10px;border-radius:8px"><?php echo nl2br(htmlspecialchars($makna ?: '—')); ?></div>

        <p style="margin-top:12px"><strong>Ayat contoh:</strong></p>
        <div style="background:rgba(0,0,0,0.12);padding:10px;border-radius:8px"><?php echo nl2br(htmlspecialchars($ayat ?: '—')); ?></div>

        <p style="margin-top:12px"><strong>Cadangan Kegunaan:</strong></p>
        <div style="background:rgba(0,0,0,0.08);padding:10px;border-radius:8px"><?php echo nl2br(htmlspecialchars($cadangan ?: '—')); ?></div>
      </div>

      <hr style="border:none;border-top:1px dashed rgba(255,255,255,0.08);margin:18px 0">

      <h3>🎤 Uji Sebutan</h3>
      <button class="btn" onclick="mulaRakaman()">Mula Ujian</button>
      <div class="status-box">
        <p id="statusAI">Status: Menunggu rakaman...</p>
      </div>

      <div style="margin-top:18px;">
        <a class="backbtn" href="abjad.php?abjad=<?php echo urlencode($abjad); ?>">← Kembali ke Abjad</a>
      </div>
    </div>
  </div>

  <div class="nav-row">
    <div>
      <?php if ($currentIndex > 0): ?>
        <a class="nav-link" href="?abjad=<?php echo urlencode($abjad); ?>&index=<?php echo $currentIndex - 1; ?>"><button class="nav-small">← Sebelum</button></a>
      <?php endif; ?>
    </div>
    <div>
      <small>Perkataan <?php echo ($currentIndex + 1) . " / " . count($perkataanArr); ?> dalam abjad "<?php echo htmlspecialchars($abjad); ?>"</small>
    </div>
    <div>
      <?php if ($currentIndex < count($perkataanArr) - 1): ?>
        <a class="nav-link" href="?abjad=<?php echo urlencode($abjad); ?>&index=<?php echo $currentIndex + 1; ?>"><button class="nav-small">Seterusnya →</button></a>
      <?php endif; ?>
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
