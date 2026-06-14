<?php
define('SECRET_KEY', 'GIB_HIER_DEINEN_32_BYTE_GEHEIMEN_KEY_EIN');

$basename = "daten";
$extension = "tinob";
$filename = $basename . "." . $extension;

function encryptAES($data, $key) {
    $iv = random_bytes(16);
    $ciphertext = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext, $key, true);
    return base64_encode($iv . $hmac . $ciphertext);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cases'])) {
    $cases = $_POST['cases'];
    $lines = ["#TINOB v1#"];
    foreach ($cases as $case) {
        $desc = trim($case['desc']);
        $content = trim($case['content']);
        if ($desc !== '' || $content !== '') {
            $lines[] = "$desc:$content";
        }
    }
    $plain = implode("\n", $lines);
    $encrypted = encryptAES($plain, SECRET_KEY);
    file_put_contents($filename, $encrypted);
    echo "<p style='color:green;'>Datei <strong>$filename</strong> erfolgreich verschlüsselt gespeichert.</p>";
}
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>TINOB Format – Daten eingeben</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <style>
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 8px;
    }

    .removeBtn {
        color: red;
        cursor: pointer;
    }

    .addBtn,
    .submitBtn {
        margin: 10px 5px;
        padding: 8px 16px;
    }
    </style>
</head>

<body>
    <h1>! Prototype Version !</h1>
  
    <h1>TINOB Daten eingeben (verschlüsselt speichern)</h1>
    <form method="post">
        <table id="casesTable">
            <thead>
                <tr>
                    <th>Beschreibung</th>
                    <th>Content</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type='text' name='cases[0][desc]' placeholder='Beschreibung'></td>
                    <td><input type='text' name='cases[0][content]' placeholder='Content'></td>
                    <td><span class='removeBtn'>–</span></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="addBtn">Zeile hinzufügen</button>
        <button type="submit" class="submitBtn">Verschlüsselt speichern</button>
    </form>

    <script>
    let idx = 1;
    $('.addBtn').click(function() {
        const row = `<tr>
        <td><input type="text" name="cases[${idx}][desc]" placeholder="Beschreibung"></td>
        <td><input type="text" name="cases[${idx}][content]" placeholder="Content"></td>
        <td><span class="removeBtn">–</span></td>
      </tr>`;
        $('#casesTable tbody').append(row);
        idx++;
    });
    $('#casesTable').on('click', '.removeBtn', function() {
        $(this).closest('tr').remove();
    });
    </script>
</body>

</html>