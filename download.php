<?php
$id = $_GET['id'] ?? '';
$key = $_GET['key'] ?? '';

if (!preg_match('/^[a-f0-9]{8}$/', $id)) {
    die('Invalid ID');
}

$file = "data/$id.json";

if (!file_exists($file)) {
    die('Not found');
}

$paste = json_decode(file_get_contents($file), true);

if ($paste['expires'] > 0 && time() > $paste['expires']) {
    unlink($file);
    die('Expired');
}

$text = $paste['text'];

if ($paste['encrypted']) {
    if (empty($key)) {
        die('Key required');
    }
    $text = openssl_decrypt($text, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
    if ($text === false) {
        die('Decryption failed');
    }
}

if (!empty($paste['password']) && (!isset($_POST['password']) || !password_verify($_POST['password'], $paste['password']))) {
    die('Password required');
}

$filename = preg_replace('/[^a-z0-9]/i', '_', $paste['title']) . '.txt';

header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($text));

echo $text;

$paste['views']++;
file_put_contents($file, json_encode($paste));

if ($paste['burn'] && $paste['views'] >= 1) {
    unlink($file);
}
