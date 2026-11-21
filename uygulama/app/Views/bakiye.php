<?php
$turAdlari = [
    'yukleme' => 'Bakiye yükleme',
    'rezervasyon' => 'Rezervasyon',
    'iade' => 'İade',
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakiyem</title>
    <style>
        *{box-sizing:border-box}body{margin:0;padding:22px;font-family:Arial,sans-serif;background:#f3f6f4;color:#26352a}
        .container{max-width:1000px;margin:0 auto}.ust{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
        h1{margin:0;color:#173b2b}.geri{background:#34495e;color:#fff;text-decoration:none;padding:10px 15px;border-radius:7px}
        .navigasyon{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}.navigasyon a{background:#286b3b;color:#fff;padding:10px 14px;border-radius:7px;text-decoration:none}
        .kart{background:#fff;padding:20px;border-radius:11px;margin-bottom:17px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
        .bakiye{font-size:32px;font-weight:700;color:#286b3b;margin:8px 0 0}.form-alani{display:flex;align-items:end;gap:12px;flex-wrap:wrap}.form-alani>div{flex:1;min-width:190px}
        label{display:block;font-weight:700;margin-bottom:7px}input,button{padding:11px;border:1px solid #cbd5ce;border-radius:7px;font-size:15px}input{width:100%}
        button{background:#286b3b;color:#fff;border:0;cursor:pointer}button:hover{background:#1f5630}
        .mesaj{padding:13px 15px;border-radius:8px;margin-bottom:16px;background:#f8d7da;color:#721c24}.mesaj.basarili{background:#d4edda;color:#155724}
        .hareket{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:13px 0;border-bottom:1px solid #e8ede9}.hareket:last-child{border-bottom:0}
        .tutar{font-weight:700;white-space:nowrap}.pozitif{color:#176b35}.negatif{color:#a52e2e}.bilgi{color:#657269;font-size:13px;margin-top:5px}.bos{color:#657269}
        @media(max-width:600px){body{padding:12px}.kart{padding:15px}.form-alani{display:block}.form-alani>div{margin-bottom:12px}button{width:100%}.hareket{display:block}.tutar{display:block;margin-top:7px}}
    </style>
</head>
<body>
<main class="container">
    <div class="ust">
        <h1>Bakiyem</h1>
        <a class="geri" href="/panel">Panele Dön</a>
    </div>

    <nav class="navigasyon">
        <a href="/haftalik-menu">Haftalık Menü</a>
        <a href="/rezervasyonlarim">Rezervasyonlarım</a>
    </nav>

    <?php if (!empty($hata)): ?>
        <div class="mesaj"><?= htmlspecialchars($hata, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($bildirim['mesaj'])): ?>
        <div class="mesaj <?= ($bildirim['tur'] ?? '') === 'basarili' ? 'basarili' : '' ?>">
            <?= htmlspecialchars($bildirim['mesaj'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="kart">
        <h2>Güncel Bakiyeniz</h2>
        <p class="bakiye"><?= number_format((float)$bakiye, 2, ',', '.') ?> TL</p>
        <p class="bilgi">Güncel bakiye, işlem sonrası doğru tutarı göstermek için kaynak veritabanından okunur.</p>
    </section>

    <section class="kart">
        <h2>Bakiye Yükle</h2>
        <p>Tek seferde 10 TL ile 1000 TL arasında yükleme yapabilirsiniz.</p>
        <form method="POST" action="/bakiye-yukle" class="form-alani">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div>
                <label for="tutar">Yüklenecek tutar (TL)</label>
                <input type="number" id="tutar" name="tutar" min="10" max="1000" step="0.01" placeholder="Örn. 100" required>
            </div>
            <div><button type="submit">Bakiye Yükle</button></div>
        </form>
    </section>

    <section class="kart">
        <h2>Bakiye Hareketleri</h2>
        <?php if (empty($hareketler)): ?>
            <p class="bos">Henüz bakiye hareketiniz bulunmuyor.</p>
        <?php else: ?>
            <?php foreach ($hareketler as $hareket): ?>
                <?php $pozitif = (float)$hareket['tutar'] >= 0; ?>
                <div class="hareket">
                    <div>
                        <strong><?= htmlspecialchars($turAdlari[$hareket['tur']] ?? $hareket['tur'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <div class="bilgi">
                            <?= htmlspecialchars($hareket['aciklama'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            · <?= date('d.m.Y H:i', strtotime($hareket['olusturma'])) ?>
                            <?php if (!empty($hareket['rezervasyon_id'])): ?>
                                · Rezervasyon no: <?= (int)$hareket['rezervasyon_id'] ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="tutar <?= $pozitif ? 'pozitif' : 'negatif' ?>">
                        <?= $pozitif ? '+' : '' ?><?= number_format((float)$hareket['tutar'], 2, ',', '.') ?> TL
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
