<?php
$pageTitle = 'ButtBin';
$activePage = 'buttbin';

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['text'] ?? '';
    $title = $_POST['title'] ?? 'Без названия';
    $password = $_POST['password'] ?? '';
    $expires = $_POST['expires'] ?? 'never';
    $burn = isset($_POST['burn']) ? true : false;
    $encrypt = isset($_POST['encrypt']) ? true : false;
    
    if (empty($text)) {
        $error = 'Введите текст';
    } else {
        $id = bin2hex(random_bytes(4));
        $created = time();
        
        switch ($expires) {
            case '1h': $expireTime = $created + 3600; break;
            case '1d': $expireTime = $created + 86400; break;
            case '1w': $expireTime = $created + 604800; break;
            case '1m': $expireTime = $created + 2592000; break;
            default: $expireTime = 0;
        }
        
        if ($encrypt || !empty($password)) {
            $key = bin2hex(random_bytes(16));
            $encrypted = openssl_encrypt($text, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
            $text = $encrypted;
            $isEncrypted = true;
        } else {
            $isEncrypted = false;
            $key = '';
        }
        
        $data = [
            'id' => $id,
            'title' => htmlspecialchars($title),
            'text' => $text,
            'created' => $created,
            'expires' => $expireTime,
            'burn' => $burn,
            'encrypted' => $isEncrypted,
            'password' => !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '',
            'views' => 0
        ];
        
        file_put_contents("data/$id.json", json_encode($data));
        
        $url = $baseUrl . '/view.php?id=' . $id;
        if ($isEncrypted && !empty($key)) {
            $url .= '&key=' . $key;
        }
        
        $success = $url;
    }
}
?>

<h1><span class="icon"><?php echo icon('code'); ?></span>ButtBin</h1>

<p>Альтернатива PasteBin с шифрованием. Создавайте защищенные заметки с паролем, сроком действия и автоудалением.</p>

<?php if (isset($error)): ?>
<div class="info-block" style="border-left-color: #c00;">
    <p><?php echo $error; ?></p>
</div>
<?php endif; ?>

<?php if (isset($success)): ?>
<div class="info-block" style="border-left-color: #0c0;">
    <h4>Паста создана!</h4>
    <p>Ссылка: <input type="text" value="<?php echo $success; ?>" readonly onclick="this.select()" style="width: 100%; padding: 5px; border: 1px solid #ccc;"></p>
    <p><small>Скопируйте ссылку сейчас. <?php if (strpos($success, 'key=') !== false) echo 'Ключ восстановить невозможно!'; ?></small></p>
</div>
<?php endif; ?>

<div class="project-card">
    <h3>Новая паста</h3>
    <form method="POST" action="">
        <p>
            <input type="text" name="title" placeholder="Название (необязательно)" style="width: 100%; padding: 8px; border: 1px solid #ccc; font-size: 13px;">
        </p>
        <p>
            <textarea name="text" rows="15" placeholder="Введите текст здесь..." style="width: 100%; padding: 8px; border: 1px solid #ccc; font-family: monospace; font-size: 12px; resize: vertical;" required></textarea>
        </p>
        
        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin: 15px 0;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Срок действия:</label>
                <select name="expires" style="padding: 5px; border: 1px solid #ccc;">
                    <option value="never">Никогда</option>
                    <option value="1h">1 час</option>
                    <option value="1d">1 день</option>
                    <option value="1w">1 неделя</option>
                    <option value="1m">1 месяц</option>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Пароль:</label>
                <input type="password" name="password" placeholder="Без пароля" style="padding: 5px; border: 1px solid #ccc;">
            </div>
        </div>
        
        <div style="margin: 15px 0;">
            <label style="margin-right: 20px;">
                <input type="checkbox" name="encrypt"> Шифровать содержимое (AES-256)
            </label>
            <label>
                <input type="checkbox" name="burn"> Удалить после прочтения
            </label>
        </div>
        
        <p>
            <button type="submit" style="background: #555; color: #fff; border: none; padding: 10px 25px; cursor: pointer; font-size: 13px;">Создать пасту (Ctrl+Enter)</button>
        </p>
    </form>
<script>
document.querySelector('textarea').addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'Enter') {
        this.form.submit();
    }
});
</script>
</div>

<div class="sidebar-box" style="margin-top: 20px;">
    <h3>Возможности</h3>
    <p><span class="icon"><?php echo icon('lock', 14); ?></span><strong>Шифрование</strong> — AES-256 для защиты данных</p>
    <p><span class="icon"><?php echo icon('clock', 14); ?></span><strong>Срок действия</strong> — автоудаление по времени</p>
    <p><span class="icon"><?php echo icon('shield', 14); ?></span><strong>Пароль</strong> — дополнительная защита</p>
    <p><span class="icon"><?php echo icon('arrow-right', 14); ?></span><strong>Burn after reading</strong> — удаление после 1 просмотра</p>
    <p><span class="icon"><?php echo icon('code', 14); ?></span><strong>Без логов</strong> — не сохраняем IP и метаданные</p>
</div>

<?php require_once '../includes/footer.php'; ?>
