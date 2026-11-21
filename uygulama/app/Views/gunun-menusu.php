<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Günün Menüsü | Yemekhane Otomasyonu</title>
    <link rel="stylesheet" href="/css/temel.css">
    <style>
        body{background:#f3f6f4;min-height:100vh;padding:20px}
        .sayfa{max-width:1000px;margin:24px auto}
        .ust,.filtreler,.menu-karti{background:#fff;border-radius:14px;padding:22px;margin-bottom:18px}
        .ust{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        h1{margin:0;color:#173b2b}.alt-baslik{color:#66756c;margin:7px 0 0}
        .geri{color:#286b3b;text-decoration:none;font-weight:700}
        .filtreler{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;align-items:end}
        label{display:block;font-weight:600;margin-bottom:7px}
        select,input{width:100%;padding:11px;border:1px solid #ccd7cf;border-radius:8px;font:inherit;background:#fff}
        button{padding:12px 18px;background:#286b3b;color:#fff;border:0;border-radius:8px;font:inherit;font-weight:700;cursor:pointer}
        .menu-baslik{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        .kalori{background:#e7f3e8;color:#286b3b;padding:9px 13px;border-radius:9px;font-weight:700}
        .yemek{padding:14px 0;border-bottom:1px solid #e5ebe6}
        .yemek:last-child{border-bottom:0}.kategori{font-size:13px;color:#66756c;margin-bottom:4px}
        .yemek-adi{font-weight:700;color:#173b2b}
        .etiketler{display:flex;gap:7px;flex-wrap:wrap;margin-top:7px}
        .etiket{font-size:12px;padding:5px 8px;border-radius:20px;background:#eef2ef;color:#405348}
        .alerjen{background:#fff2df;color:#80510b}
        .bos,.hata{padding:16px;border-radius:9px;background:#fff}
        .hata{background:#fff0ef;color:#a12620}
        @media(max-width:650px){.filtreler{grid-template-columns:1fr}.ust,.filtreler,.menu-karti{padding:17px}body{padding:12px}}
    </style>
</head>
<body>
<main class="sayfa">
    <header class="ust">
        <div>
            <h1>Günün Menüsü</h1>
            <p class="alt-baslik">Merhaba, <?= htmlspecialchars($kullanici['ad'], ENT_QUOTES, 'UTF-8') ?>.</p>
        </div>
        <a class="geri" href="/panel">← Panele dön</a>
    </header>

    <form class="filtreler" method="get" action="/gunun-menusu">
        <div>
            <label for="tarih">Tarih</label>
            <input type="date" id="tarih" name="tarih"
                   value="<?= htmlspecialchars($seciliTarih, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label for="yemekhane_id">Yemekhane</label>
            <select id="yemekhane_id" name="yemekhane_id" required>
                <?php foreach ($yemekhaneler as $yemekhane): ?>
                    <option value="<?= (int)$yemekhane['id'] ?>"
                        <?= $yemekhaneId === (int)$yemekhane['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($yemekhane['ad'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="ogun_id">Öğün</label>
            <select id="ogun_id" name="ogun_id" required>
                <?php foreach ($ogunler as $ogun): ?>
                    <option value="<?= (int)$ogun['id'] ?>"
                        <?= $ogunId === (int)$ogun['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ogun['ad'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><button type="submit">Menüyü Göster</button></div>
    </form>

    <?php if ($hata !== ''): ?>
        <div class="hata"><?= htmlspecialchars($hata, ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif (empty($yemekler)): ?>
        <div class="bos">Seçtiğiniz tarih, yemekhane ve öğün için menü bulunamadı.</div>
    <?php else: ?>
        <section class="menu-karti">
            <div class="menu-baslik">
                <div>
                    <h2><?= htmlspecialchars($yemekler[0]['yemekhane'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="alt-baslik">
                        <?= htmlspecialchars($yemekler[0]['ogun'], ENT_QUOTES, 'UTF-8') ?>
                        · <?= htmlspecialchars(date('d.m.Y', strtotime($seciliTarih)), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <div class="kalori">Toplam <?= $toplamKalori ?> kcal</div>
            </div>

            <?php foreach ($yemekler as $yemek): ?>
                <article class="yemek">
                    <div class="kategori"><?= htmlspecialchars($yemek['kategori'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="yemek-adi"><?= htmlspecialchars($yemek['yemek'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="etiketler">
                        <span class="etiket"><?= (int)$yemek['kalori'] ?> kcal</span>
                        <?php if ((int)$yemek['vejetaryen'] === 1): ?>
                            <span class="etiket">Vejetaryen</span>
                        <?php endif; ?>
                        <?php if (!empty($yemek['alerjenler'])): ?>
                            <span class="etiket alerjen">
                                Alerjen: <?= htmlspecialchars($yemek['alerjenler'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php else: ?>
                            <span class="etiket">Kayıtlı alerjen yok</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
