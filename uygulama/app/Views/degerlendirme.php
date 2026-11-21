<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menü Değerlendirmeleri</title>
    <style>
        *{box-sizing:border-box}body{margin:0;padding:22px;font-family:Arial,sans-serif;background:#f3f6f4;color:#26352a}
        .container{max-width:900px;margin:0 auto}.ust{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
        h1{margin:0;color:#173b2b}.geri,.nav a{display:inline-block;background:#34495e;color:#fff;text-decoration:none;padding:10px 14px;border-radius:7px}
        .nav{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}.nav a{background:#286b3b}
        .kart{background:#fff;padding:19px;border-radius:11px;margin-bottom:15px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
        .ust-kart{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}.bilgi{font-size:14px;color:#657269;line-height:1.7;margin:7px 0 13px}
        label{display:block;font-weight:700;margin:12px 0 6px}select,textarea,button{width:100%;padding:11px;border:1px solid #cbd5ce;border-radius:7px;font-size:15px;font-family:inherit}
        textarea{min-height:90px;resize:vertical}button{margin-top:12px;background:#286b3b;color:#fff;border:0;cursor:pointer}button:hover{background:#1f5630}
        .mesaj{padding:13px 15px;border-radius:8px;margin-bottom:16px;background:#f8d7da;color:#721c24}.mesaj.basarili{background:#d4edda;color:#155724}
        .bos{background:#fff;padding:18px;border-radius:10px;color:#657269}
        @media(max-width:600px){body{padding:12px}.kart{padding:14px}.ust-kart{display:block}.ust-kart strong{display:block;margin-top:6px}}
    </style>
</head>
<body>
<main class="container">
    <div class="ust">
        <h1>Menü Değerlendirmeleri</h1>
        <a class="geri" href="/panel">Panele Dön</a>
    </div>

    <nav class="nav">
        <a href="/haftalik-menu">Haftalık Menü</a>
        <a href="/rezervasyonlarim">Rezervasyonlarım</a>
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

    <?php if (empty($degerlendirilecekMenuler)): ?>
        <div class="bos">Henüz kullanılmış bir menünüz bulunmuyor. Menü değerlendirmesi yapabilmek için rezervasyonun kullanılmış olması gerekir.</div>
    <?php else: ?>
        <?php foreach ($degerlendirilecekMenuler as $menu): ?>
            <article class="kart">
                <div class="ust-kart">
                    <h2><?= htmlspecialchars($menu['ogun'], ENT_QUOTES, 'UTF-8') ?> · <?= date('d.m.Y', strtotime($menu['tarih'])) ?></h2>
                    <?php if ($menu['puan'] !== null): ?>
                        <strong>Mevcut puanınız: <?= (int)$menu['puan'] ?>/5</strong>
                    <?php endif; ?>
                </div>

                <div class="bilgi">
                    Yemekhane: <?= htmlspecialchars($menu['yemekhane'], ENT_QUOTES, 'UTF-8') ?><br>
                    Yemekler: <?= htmlspecialchars($menu['yemekler'] ?? 'Menü yemekleri', ENT_QUOTES, 'UTF-8') ?><br>
                    Kullanılma zamanı:
                    <?= !empty($menu['kullanma_zamani']) ? date('d.m.Y H:i', strtotime($menu['kullanma_zamani'])) : 'Belirtilmemiş' ?>
                </div>

                <form method="POST" action="/degerlendirme-kaydet">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="menu_id" value="<?= (int)$menu['menu_id'] ?>">

                    <label for="puan-<?= (int)$menu['menu_id'] ?>">Puanınız (1–5)</label>
                    <select id="puan-<?= (int)$menu['menu_id'] ?>" name="puan" required>
                        <option value="">Puan seçin</option>
                        <?php for ($puan = 1; $puan <= 5; $puan++): ?>
                            <option value="<?= $puan ?>" <?= (int)($menu['puan'] ?? 0) === $puan ? 'selected' : '' ?>>
                                <?= $puan ?> / 5
                            </option>
                        <?php endfor; ?>
                    </select>

                    <label for="yorum-<?= (int)$menu['menu_id'] ?>">Yorumunuz (isteğe bağlı, en fazla 500 karakter)</label>
                    <textarea id="yorum-<?= (int)$menu['menu_id'] ?>" name="yorum" maxlength="500" placeholder="Menü hakkındaki görüşünüzü yazın..."><?= htmlspecialchars($menu['yorum'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

                    <button type="submit">Değerlendirmeyi Kaydet</button>
                </form>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>
