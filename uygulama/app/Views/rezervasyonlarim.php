<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervasyonlarım</title>
    <style>
        *{box-sizing:border-box}body{margin:0;padding:22px;font-family:Arial,sans-serif;background:#f3f6f4;color:#26352a}
        .container{max-width:1000px;margin:0 auto}.ust{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
        h1{margin:0;color:#173b2b}.geri{background:#34495e;color:#fff;text-decoration:none;padding:10px 15px;border-radius:7px}
        .navigasyon{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}.navigasyon a{background:#286b3b;color:#fff;padding:10px 14px;border-radius:7px;text-decoration:none}
        .kart{background:#fff;padding:18px;border-radius:11px;margin-bottom:13px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
        .satir{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}.durum{font-weight:700}.aktif{color:#176b35}.kullanildi{color:#286b9b}.iptal{color:#777}
        .bilgi{color:#657269;font-size:14px;line-height:1.7;margin-top:8px}.mesaj{padding:13px 15px;border-radius:8px;margin-bottom:16px;background:#f8d7da;color:#721c24}.mesaj.basarili{background:#d4edda;color:#155724}
        button{background:#b83b3b;color:#fff;padding:10px 14px;border:0;border-radius:7px;cursor:pointer}button:hover{background:#982f2f}
        .bos{background:#fff;padding:18px;border-radius:10px;color:#657269}
        @media(max-width:600px){body{padding:12px}.kart{padding:14px}.satir{display:block}.satir form{margin-top:12px}button{width:100%}}
    </style>
</head>
<body>
<main class="container">
    <div class="ust">
        <h1>Rezervasyonlarım</h1>
        <a class="geri" href="/panel">Panele Dön</a>
    </div>

    <nav class="navigasyon">
        <a href="/haftalik-menu">Haftalık Menü</a>
        <a href="/bakiye">Bakiyem</a>
    </nav>

    <?php if (!empty($hata)): ?>
        <div class="mesaj"><?= htmlspecialchars($hata, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($bildirim['mesaj'])): ?>
        <div class="mesaj <?= ($bildirim['tur'] ?? '') === 'basarili' ? 'basarili' : '' ?>">
            <?= htmlspecialchars($bildirim['mesaj'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (empty($rezervasyonlar)): ?>
        <div class="bos">Henüz rezervasyonunuz bulunmuyor.</div>
    <?php else: ?>
        <?php foreach ($rezervasyonlar as $rezervasyon): ?>
            <?php
            $durumlar = [
                'aktif' => 'Aktif',
                'kullanildi' => 'Kullanıldı',
                'iptal' => 'İptal edildi',
            ];
            $durum = $rezervasyon['durum'];
            ?>
            <article class="kart">
                <div class="satir">
                    <div>
                        <h2><?= htmlspecialchars($rezervasyon['ogun'], ENT_QUOTES, 'UTF-8') ?> · <?= date('d.m.Y', strtotime($rezervasyon['tarih'])) ?></h2>
                        <span class="durum <?= htmlspecialchars($durum, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($durumlar[$durum] ?? $durum, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <strong><?= number_format((float)$rezervasyon['ucret'], 2, ',', '.') ?> TL</strong>
                </div>

                <div class="bilgi">
                    Yemekhane: <?= htmlspecialchars($rezervasyon['yemekhane'], ENT_QUOTES, 'UTF-8') ?><br>
                    Rezervasyon tarihi: <?= date('d.m.Y H:i', strtotime($rezervasyon['olusturma'])) ?><br>
                    Son iptal saati: <?= date('d.m.Y H:i', strtotime($rezervasyon['rezervasyon_son'])) ?>
                    <?php if (!empty($rezervasyon['kullanma_zamani'])): ?>
                        <br>Kullanılma zamanı: <?= date('d.m.Y H:i', strtotime($rezervasyon['kullanma_zamani'])) ?>
                    <?php endif; ?>
                    <?php if (!empty($rezervasyon['iptal_zamani'])): ?>
                        <br>İptal zamanı: <?= date('d.m.Y H:i', strtotime($rezervasyon['iptal_zamani'])) ?>
                    <?php endif; ?>
                </div>

                <?php if ((int)$rezervasyon['iptal_edilebilir'] === 1): ?>
                    <form method="POST" action="/rezervasyon-iptal" style="margin-top:14px"
                          onsubmit="return confirm('Bu rezervasyonu iptal etmek istediğinize emin misiniz?');">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="rezervasyon_id" value="<?= (int)$rezervasyon['rezervasyon_id'] ?>">
                        <button type="submit">Rezervasyonu İptal Et</button>
                    </form>
                <?php elseif ($durum === 'aktif'): ?>
                    <p class="bilgi">İptal süresi sona erdiği için bu rezervasyon iptal edilemez.</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>
