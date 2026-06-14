<?php
define('SECRET_KEY', 'GIB_HIER_DEINEN_32_BYTE_GEHEIMEN_KEY_EIN');

$basename = "daten";
$extension = "tinob";
$filename = $basename . "." . $extension;

function decryptAES($input, $key) {
    $decoded = base64_decode($input);
    if (strlen($decoded) < 48) return false;
    $iv = substr($decoded, 0, 16);
    $hmac = substr($decoded, 16, 32);
    $ciphertext = substr($decoded, 48);
    $calcHmac = hash_hmac('sha256', $ciphertext, $key, true);
    if (!hash_equals($hmac, $calcHmac)) return false;
    return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

function parseEncryptedTinob($file, $key) {
    if (!file_exists($file)) return [];
    $encrypted = file_get_contents($file);
    $decrypted = decryptAES($encrypted, $key);
    if (!$decrypted || strpos($decrypted, "#TINOB v1#") !== 0) return [];
    $lines = explode("\n", $decrypted);
    $cases = [];
    for ($i = 1; $i < count($lines); $i++) {
        if (strpos($lines[$i], ':') !== false) {
            list($desc, $content) = explode(":", $lines[$i], 2);
            $cases[] = ['desc' => $desc, 'content' => $content];
        }
    }
    return $cases;
}

$savedCases = parseEncryptedTinob($filename, SECRET_KEY);
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>TINOB Datei anzeigen</title>
  <style>
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { border: 1px solid #ccc; padding: 8px; }
  </style>
</head>
<body>
  <h1>! Prototype Version !</h1>
  
  <h1>Verschlüsselte Datei anzeigen (<?php echo $filename; ?>)</h1>
  <table>
    <thead>
      <tr><th>Beschreibung</th><th>Content</th></tr>
    </thead>
    <tbody>
      <?php
      if (!empty($savedCases)) {
        foreach ($savedCases as $case) {
          echo "<tr><td>" . htmlspecialchars($case['desc']) . "</td><td>" . htmlspecialchars($case['content']) . "</td></tr>";
        }
      } else {
        echo "<tr><td colspan='2'>Keine oder ungültige Daten gefunden.</td></tr>";
      }
      ?>
    </tbody>
  </table>
</body>
</html>
