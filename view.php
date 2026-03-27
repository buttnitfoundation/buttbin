<?php
$pageTitle = 'Просмотр пасты';
$activePage = 'buttbin';

$id = $_GET['id'] ?? '';
$key = $_GET['key'] ?? '';
$error = '';
$paste = null;
$decrypted = '';

if (empty($id) || !preg_match('/^[a-f0-9]{8}$/', $id)) {
    $error = 'Неверный ID пасты';
} else {
    $file = "data/$id.json";
    
    if (!file_exists($file)) {
        $error = 'Паста не найдена или истек срок действия';
    } else {
        $paste = json_decode(file_get_contents($file), true);
        
        if ($paste['expires'] > 0 && time() > $paste['expires']) {
            unlink($file);
            $error = 'Срок действия пасты истек';
            $paste = null;
        } else {
            if (!empty($paste['password'])) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
                    if (!password_verify($_POST['password'], $paste['password'])) {
                        $error = 'Неверный пароль';
                    }
                } else {
                    $requirePassword = true;
                }
            }
            
            if (empty($error) && !isset($requirePassword)) {
                if ($paste['encrypted']) {
                    if (empty($key)) {
                        $error = 'Требуется ключ для расшифровки';
                    } else {
                        $decrypted = openssl_decrypt($paste['text'], 'AES-256-CBC', $key, 0, substr($key, 0, 16));
                        if ($decrypted === false) {
                            $error = 'Ошибка расшифровки. Неверный ключ.';
                        }
                    }
                } else {
                    $decrypted = $paste['text'];
                }
                
                if (empty($error)) {
                    $paste['views']++;
                    file_put_contents($file, json_encode($paste));
                    
                    if ($paste['burn'] && $paste['views'] >= 1) {
                        unlink($file);
                        $burned = true;
                    }
                }
            }
        }
    }
}
?>

<h1><span class="icon"><?php echo icon('code'); ?></span>ButtBin</h1>

<?php if (!empty($error)): ?>
<div class="info-block" style="border-left-color: #c00;">
    <p><?php echo $error; ?></p>
    <p><a href="index.php">Создать новую пасту</a></p>
</div>

<?php elseif (isset($requirePassword)): ?>
<div class="project-card">
    <h3>Паста защищена паролем</h3>
    <p>Название: <?php echo $paste['title']; ?></p>
    <form method="POST" action="">
        <p>
            <input type="password" name="password" placeholder="Введите пароль" style="padding: 8px; border: 1px solid #ccc;" required>
            <button type="submit" style="background: #555; color: #fff; border: none; padding: 8px 15px; cursor: pointer;">Открыть</button>
        </p>
    </form>
</div>

<?php elseif (isset($burned)): ?>
<div class="info-block" style="border-left-color: #f90;">
    <h4>Паста прочитана и удалена</h4>
    <p>Эта паста была настроена на удаление после прочтения. Она больше недоступна.</p>
</div>

<?php elseif ($decrypted !== ''): ?>
<div class="project-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0;"><?php echo $paste['title']; ?></h3>
        <div style="font-size: 11px; color: #666;">
            <?php if ($paste['encrypted']): ?><span class="icon"><?php echo icon('lock', 12); ?></span>Зашифровано<?php endif; ?>
            <?php if ($paste['burn']): ?> | <span style="color: #c00;">Burn after reading</span><?php endif; ?>
        </div>
    </div>
    
    <pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto; font-size: 12px; line-height: 1.4; margin: 0;"><?php echo htmlspecialchars($decrypted); ?></pre>
    
    <div style="margin-top: 15px; font-size: 11px; color: #666;">
        Создано: <?php echo date('d.m.Y H:i', $paste['created']); ?>
        <?php if ($paste['expires'] > 0): ?> | Истекает: <?php echo date('d.m.Y H:i', $paste['expires']); ?><?php endif; ?>
        | Просмотров: <?php echo $paste['views']; ?>
    </div>
    
    <div style="margin-top: 15px;">
        <button onclick="navigator.clipboard.writeText(document.querySelector('pre').innerText)" style="background: #666; color: #fff; border: none; padding: 5px 15px; cursor: pointer; font-size: 12px;">Копировать</button>
        <a href="download.php?id=<?php echo $id; ?><?php if (!empty($key)) echo '&key=' . $key; ?>" style="background: #888; color: #fff; text-decoration: none; padding: 5px 15px; font-size: 12px; margin-left: 10px;">Скачать</a>
        <a href="index.php" style="margin-left: 10px;">Создать новую</a>
    </div>
</div>

<?php if ($paste['encrypted']): ?>
<div class="info-block" style="margin-top: 15px;">
    <p><small><strong>Эта паста зашифрована.</strong> Без правильного ключа содержимое невозможно прочитать. Ключ находится в URL после <code>&key=</code></small></p>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
