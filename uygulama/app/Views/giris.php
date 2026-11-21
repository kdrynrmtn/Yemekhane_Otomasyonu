<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş | Yemekhane Otomasyonu</title>
    <link rel="stylesheet" href="/css/temel.css">
    <style>
        body{min-height:100vh;display:grid;place-items:center;background:#f3f6f4;padding:20px}
        .giris-karti{width:min(100%,430px);background:#fff;padding:32px;border-radius:16px;box-shadow:0 12px 35px #173b2b14}
        .giris-karti h1{margin:0 0 8px;color:#173b2b}.aciklama{color:#66756c;margin-bottom:26px}
        label{display:block;font-weight:600;margin:16px 0 7px}input{width:100%;padding:13px;border:1px solid #ccd7cf;border-radius:8px;font:inherit}
        button{width:100%;margin-top:22px;padding:13px;background:#286b3b;color:white;border:0;border-radius:8px;font:inherit;font-weight:700;cursor:pointer}
        .hata{padding:12px;background:#fff0ef;color:#a12620;border-radius:8px;margin:15px 0}
    </style>
</head>
<body>
<main class="giris-karti">
    <h1>Yemekhane Otomasyonu</h1>
    <p class="aciklama">Devam etmek için hesabınıza giriş yapın.</p>
    <?php if (!empty($hata)): ?><div class="hata"><?= htmlspecialchars($hata, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form action="/giris-yap" method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <label for="eposta">E-posta</label>
        <input id="eposta" name="eposta" type="email" autocomplete="username" required>
        <label for="sifre">Şifre</label>
        <input id="sifre" name="sifre" type="password" autocomplete="current-password" required>
        <button type="submit">Giriş Yap</button>
    </form>
</main>
</body>
</html>
