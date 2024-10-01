<link rel="stylesheet" href="style.css">

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$result = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $text = '';
    $key = strtoupper(trim($_POST["key"]));
    $method = $_POST["method"];

    // Cek apakah file diunggah
    if (!empty($_FILES['file']['tmp_name'])) {
        $fileContent = file_get_contents($_FILES['file']['tmp_name']);
        $text = strtoupper($fileContent);
    } else {
        $text = strtoupper(trim($_POST["text"]));
    }

    if (empty($method) || empty($text)) {
        $result = "Metode atau teks/file tidak boleh kosong.";
    } else {
        switch ($method) {
            case "vigenere":
                if (isset($_POST['encrypt'])) {
                    $result = vigenereCipher($text, $key);
                } else {
                    $result = vigenereCipher($text, $key, true);
                }
                break;
            case "auto_key":
                if (isset($_POST['encrypt'])) {
                    $result = autoKeyVigenereCipher($text, $key);
                } else {
                    $result = autoKeyVigenereCipher($text, $key, true);
                }
                break;
            case "playfair":
                if (isset($_POST['encrypt'])) {
                    $result = playfairCipher($text, $key);
                } else {
                    $result = playfairCipher($text, $key, true);
                }
                break;
            case "hill":
                if (isset($_POST['encrypt'])) {
                    $result = hillCipher($text, $key);
                } else {
                    $result = hillCipher($text, $key, true);
                }
                break;
            case "super":
                if (isset($_POST['encrypt'])) {
                    $result = superEncryption($text, $key);
                } else {
                    $result = superDecryption($text, $key);
                }
                break;
        }

        // Jika file diunggah, simpan hasil enkripsi dalam file
        if (!empty($_FILES['file']['tmp_name'])) {
            $outputFileName = 'result_' . basename($_FILES['file']['name']);
            file_put_contents($outputFileName, $result);
            $result = "File hasil enkripsi: <a href=\"$outputFileName\" download>Unduh File</a>";
        }
    }
}

// Vigenere Cipher
function vigenereCipher($text, $key, $decrypt = false) {
    $key = strtoupper($key);
    $keyLength = strlen($key);
    $result = '';

    for ($i = 0, $j = 0; $i < strlen($text); $i++) {
        $char = $text[$i];

        if (ctype_alpha($char)) {
            $shift = ord($key[$j % $keyLength]) - 65;
            if ($decrypt) {
                $shift = 26 - $shift;
            }

            $result .= chr((ord($char) + $shift - 65) % 26 + 65);
            $j++;
        } else {
            $result .= $char;
        }
    }
    return $result;
}

// Auto-Key Vigenere Cipher
function autoKeyVigenereCipher($text, $key, $decrypt = false) {
    $key = strtoupper($key);
    $result = '';
    $keyLength = strlen($key);
    $keyIndex = 0;

    for ($i = 0; $i < strlen($text); $i++) {
        $char = $text[$i];
    
        if (ctype_alpha($char)) {
            if ($keyIndex < $keyLength) {
                $shift = ord($key[$keyIndex]) - 65;
            } else {
                $shift = ord($result[$i - 1]) - 65;
            }            
            if ($decrypt) {
                $shift = 26 - $shift;
            }

            $result .= chr((ord($char) + $shift - 65) % 26 + 65);
        } else {
            $result .= $char;
        }
    }
    return $result;
}

// Playfair Cipher
function playfairCipher($text, $key, $decrypt = false) {
    $text = strtoupper($text);
    $key = strtoupper($key);
    $key = str_replace('J', 'I', $key);
    $matrix = createPlayfairMatrix($key);
    $textPairs = createTextPairs($text);
    
    $result = '';
    foreach ($textPairs as $pair) {
        $pos1 = findPosition($pair[0], $matrix);
        $pos2 = findPosition($pair[1], $matrix);
        
        if ($pos1[0] == $pos2[0]) {
            $result .= $matrix[$pos1[0]][($pos1[1] + ($decrypt ? 4 : 1)) % 5];
            $result .= $matrix[$pos2[0]][($pos2[1] + ($decrypt ? 4 : 1)) % 5];
        } elseif ($pos1[1] == $pos2[1]) {
            $result .= $matrix[($pos1[0] + ($decrypt ? 4 : 1)) % 5][$pos1[1]];
            $result .= $matrix[($pos2[0] + ($decrypt ? 4 : 1)) % 5][$pos2[1]];
        } else {
            $result .= $matrix[$pos1[0]][$pos2[1]];
            $result .= $matrix[$pos2[0]][$pos1[1]];
        }
    }
    
    return $result;
}

function createPlayfairMatrix($key) {
    $matrix = [];
    $alphabet = 'ABCDEFGHIKLMNOPQRSTUVWXYZ';
    $key = str_replace('J', 'I', $key);
    $key = strtoupper($key);
    $key = preg_replace('/[^A-Z]/', '', $key);
    $key = implode('', array_unique(str_split($key)));

    $combinedKey = $key . $alphabet;
    $combinedKey = implode('', array_unique(str_split($combinedKey)));

    $index = 0;
    for ($i = 0; $i < 5; $i++) {
        for ($j = 0; $j < 5; $j++) {
            $matrix[$i][$j] = $combinedKey[$index++];
        }
    }

    return $matrix;
}

function createTextPairs($text) {
    $pairs = [];
    $text = preg_replace('/[^A-Z]/', '', $text);
    $text = str_replace('J', 'I', $text);
    $textLength = strlen($text);

    for ($i = 0; $i < $textLength; $i += 2) {
        if ($i + 1 < $textLength) {
            if ($text[$i] == $text[$i + 1]) {
                $pairs[] = [$text[$i], 'X'];
                $i--;
            } else {
                $pairs[] = [$text[$i], $text[$i + 1]];
            }
        } else {
            $pairs[] = [$text[$i], 'X'];
        }
    }

    return $pairs;
}

function findPosition($char, $matrix) {
    for ($i = 0; $i < 5; $i++) {
        for ($j = 0; $j < 5; $j++) {
            if ($matrix[$i][$j] == $char) {
                return [$i, $j];
            }
        }
    }
    return [0, 0];
}

// Hill Cipher
function hillCipher($text, $key, $decrypt = false) {
    $matrixKey = [
        [6, 24, 1],
        [13, 16, 10],
        [20, 17, 15]
    ];

    $textVector = [];
    $text = strtoupper($text);
    $text = preg_replace('/[^A-Z]/', '', $text);
    $textLength = strlen($text);
    $result = '';

    for ($i = 0; $i < $textLength; $i += 3) {
        for ($j = 0; $j < 3; $j++) {
            if ($i + $j < $textLength) {
                $textVector[$j] = ord($text[$i + $j]) - 65;
            } else {
                $textVector[$j] = 0;
            }
        }

        $cipherVector = [];
        for ($j = 0; $j < 3; $j++) {
            $cipherVector[$j] = 0;
            for ($k = 0; $k < 3; $k++) {
                $cipherVector[$j] += $matrixKey[$j][$k] * $textVector[$k];
            }
            $cipherVector[$j] = ($cipherVector[$j] % 26) + 65;
            $result .= chr($cipherVector[$j]);
        }
    }

    return $result;
}

// Super Encryption
function superEncryption($text, $key) {
    // Super enkripsi dengan kombinasi metode
    return vigenereCipher(playfairCipher($text, $key), $key);
}

function superDecryption($text, $key) {
    // Super dekripsi dengan kombinasi metode
    return playfairCipher(vigenereCipher($text, $key, true), $key, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enkripsi & Dekripsi</title>
</head>
<body>
    <h1>Enkripsi dan Dekripsi</h1>
    <form method="post" enctype="multipart/form-data">
        <label for="text">Teks (atau unggah file):</label>
        <textarea id="text" name="text"></textarea>

        <label for="file">Unggah File:</label>
        <input type="file" id="file" name="file">

        <label for="key">Kunci:</label>
        <input type="text" id="key" name="key" required>

        <label for="method">Metode:</label>
        <select id="method" name="method" required>
            <option value="">Pilih Metode</option>
            <option value="vigenere">Vigenere Cipher</option>
            <option value="auto_key">Auto-Key Vigenere Cipher</option>
            <option value="playfair">Playfair Cipher</option>
            <option value="hill">Hill Cipher</option>
            <option value="super">Super Enkripsi</option>
        </select>

        <div>
            <button type="submit" name="encrypt">Enkripsi</button>
            <button type="submit" name="decrypt">Dekripsi</button>
        </div>
    </form>

    <div class="result">
        <h2>Hasil:</h2>
        <p><?php echo isset($result) ? $result : ''; ?></p>
    </div>
</body>
</html>
