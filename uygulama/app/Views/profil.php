<?php
// Bu görünüm kullanıcı tarafından seçilen alerjenleri düzenler.
$seciliAlerjenHaritasi = array_fill_keys($seciliAlerjenler, true);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil ve Alerjen Tercihleri</title>
    <style>
        *{box-sizing:border-box}body{margin:0;padding:22px;font-family:Arial,sans-serif;background:#f3f6f4;color:#26352a}
        .container{max-width:850px;margin:0 auto}.ust{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
        h1{margin:0;color:#173b2b}.geri,.link{display:inline-block;background:#34495e;color:#fff;text-decoration:none;padding:10px 14px;border-radius:7px}
        .kart{background:#fff;padding:20px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
        .liste{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;margin:18px 0}
        .secim{display:flex;align-items:center;gap:9px;padding:11px;background:#f7faf7;border:1px solid #e1e9e2;border-radius:8px}
        .secim input{width:18px;height:18px}.yardim{color:#657269;line-height:1.6}
        button{background:#286b3b;color:#fff;border:0;border-radius:7px;padding:12px 18px;cursor:pointer;font-size:15px}
        .mesaj{padding:12px 14px;border-radius:8px;margin-bottom:15px;background:#f8d7da;color:#721c24}.mesaj.basarili{background:#d4edda;color:#155724}
        .navigasyon{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 18px}.navigasyon a{background:#286b3b}
        @media(max-width:550px){body{padding:12px}.kart{padding:15px}button{width:100%}}
    </style>
</head>
<body>
<main class="container">
    <div class="ust">
        <h1>Profil – Alerjen Tercihleri</h1>
        <a class="geri" href="/panel">Panele Dön</a>
    </div>

    <nav class="navigasyon">
        <a class="link" href="/gunun-menusu">Günün Menüsü</a>
        <a class="link" href="/haftalik-menu">Haftalık Menü</a>
    </nav>

    <?php if ($hata !== ''): ?>
        <div class="mesaj"><?= htmlspecialchars($hata, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($bildirim['mesaj'])): ?>
        <div class="mesaj <?= ($bildirim['tur'] ?? '') === 'basarili' ? 'basarili' : '' ?>">
            <?= htmlspecialchars($bildirim['mesaj'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="kart">
        <h2>Alerjenlerinizi seçin</h2>
        <p class="yardim">Seçtiğiniz alerjenleri içeren yemekler günlük ve haftalık menüde uyarıyla işaretlenir. Birden fazla seçim yapabilirsiniz.</p>

        <form method="POST" action="/profil-alerjen-kaydet">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div class="liste">
                <?php foreach ($alerjenler as $alerjen): ?>
                    <?php $id = (int)$alerjen['id']; ?>
                    <label class="secim">
                        <input type="checkbox" name="alerjen_ids[]" value="<?= $id ?>"
                            <?= isset($seciliAlerjenHaritasi[$id]) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($alerjen['ad'], ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit">Alerjen Tercihlerimi Kaydet</button>
        </form>
    </section>
</main>
</body>
</html>
