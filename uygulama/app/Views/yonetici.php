<?php
$duzenlemeVar = is_array($duzenlenecekMenu);
$seciliYemekler = $duzenlemeVar ? ($duzenlenecekMenu['yemek_ids'] ?? []) : [];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli</title>
    <style>
        *{box-sizing:border-box}body{margin:0;padding:22px;font-family:Arial,sans-serif;background:#f3f6f4;color:#26352a}
        .container{max-width:1200px;margin:0 auto}.ust{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
        h1{margin:0;color:#173b2b}h2{color:#173b2b}.geri,.nav a{display:inline-block;background:#34495e;color:#fff;text-decoration:none;padding:10px 14px;border-radius:7px}
        .nav{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}.nav a{background:#286b3b}
        .kart{background:#fff;padding:20px;border-radius:11px;margin-bottom:18px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
        .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.alan label{display:block;font-weight:700;margin-bottom:6px}
        input,select,button{width:100%;padding:10px;border:1px solid #cbd5ce;border-radius:7px;font-size:15px;font-family:inherit}
        .yemek-listesi{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;max-height:320px;overflow:auto;border:1px solid #e1e8e2;border-radius:8px;padding:12px}
        .yemek-secim{display:flex;align-items:flex-start;gap:8px;font-weight:400;padding:5px}.yemek-secim input{width:auto;margin-top:2px}
        button{background:#286b3b;color:#fff;border:0;cursor:pointer;margin-top:15px}button:hover{background:#1f5630}
        .mesaj{padding:13px 15px;border-radius:8px;margin-bottom:16px;background:#f8d7da;color:#721c24}.mesaj.basarili{background:#d4edda;color:#155724}
        .tablo-kapsayici{overflow-x:auto;width:100%}table{width:100%;border-collapse:collapse;min-width:1050px}th,td{padding:10px;border-bottom:1px solid #e6ece7;text-align:left;vertical-align:top;font-size:14px}th{background:#f5f8f5;color:#34495e;white-space:nowrap}
        .duzenle{display:inline-block;background:#286b3b;color:#fff;text-decoration:none;padding:7px 10px;border-radius:6px;white-space:nowrap}.aciklama{font-size:14px;color:#657269;line-height:1.6}
        .form-alt{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.form-alt button{width:auto;min-width:190px}.form-alt a{color:#286b3b;text-decoration:none}
        @media(max-width:700px){body{padding:12px}.kart{padding:14px}.form-grid{grid-template-columns:1fr}.yemek-listesi{grid-template-columns:repeat(2,minmax(0,1fr))}.form-alt button{width:100%}}
        @media(max-width:420px){.yemek-listesi{grid-template-columns:1fr}}
    </style>
</head>
<body>
<main class="container">
    <div class="ust">
        <h1>Yönetici Paneli</h1>
        <a class="geri" href="/panel">Panele Dön</a>
    </div>

    <nav class="nav">
        <a href="/haftalik-menu">Haftalık Menü</a>
        <a href="/degerlendirme">Değerlendirmeler</a>
    </nav>

    <?php if (!empty($hata)): ?>
        <div class="mesaj"><?= htmlspecialchars($hata, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($bildirim['mesaj'])): ?>
        <div class="mesaj <?= ($bildirim['tur'] ?? '') === 'basarili' ? 'basarili' : '' ?>">
            <?= htmlspecialchars($bildirim['mesaj'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="kart" id="menu-formu">
        <h2><?= $duzenlemeVar ? 'Menüyü Düzenle' : 'Yeni Menü Oluştur' ?></h2>
        <p class="aciklama">Bir yemekhane ve öğün için menü oluşturun. Menüye bir veya daha fazla yemek ekleyebilirsiniz. Var olan menüyü rapor tablosundaki Düzenle bağlantısıyla açabilirsiniz.</p>

        <form method="POST" action="/yonetici-menu-kaydet">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="menu_id" value="<?= $duzenlemeVar ? (int)$duzenlenecekMenu['id'] : '' ?>">

            <div class="form-grid">
                <div class="alan">
                    <label for="yemekhane_id">Yemekhane</label>
                    <select id="yemekhane_id" name="yemekhane_id" required>
                        <option value="">Yemekhane seçin</option>
                        <?php foreach ($formVerileri['yemekhaneler'] as $yemekhane): ?>
                            <option value="<?= (int)$yemekhane['id'] ?>" <?= $duzenlemeVar && (int)$duzenlenecekMenu['yemekhane_id'] === (int)$yemekhane['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($yemekhane['ad'], ENT_QUOTES, 'UTF-8') ?> (Kapasite: <?= (int)$yemekhane['kapasite'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="alan">
                    <label for="tarih">Menü tarihi</label>
                    <input type="date" id="tarih" name="tarih" value="<?= htmlspecialchars($duzenlemeVar ? $duzenlenecekMenu['tarih'] : date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="alan">
                    <label for="ogun_id">Öğün</label>
                    <select id="ogun_id" name="ogun_id" required>
                        <option value="">Öğün seçin</option>
                        <?php foreach ($formVerileri['ogunler'] as $ogun): ?>
                            <option value="<?= (int)$ogun['id'] ?>" <?= $duzenlemeVar && (int)$duzenlenecekMenu['ogun_id'] === (int)$ogun['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ogun['ad'], ENT_QUOTES, 'UTF-8') ?> (Rezervasyon son: <?= htmlspecialchars(substr($ogun['son_saat'], 0, 5), ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="alan">
                    <label for="ogrenci_fiyati">Öğrenci fiyatı (TL)</label>
                    <input type="number" id="ogrenci_fiyati" name="ogrenci_fiyati" min="0" max="999999.99" step="0.01" value="<?= htmlspecialchars($duzenlemeVar ? (string)$duzenlenecekMenu['ogrenci_fiyati'] : '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="alan">
                    <label for="personel_fiyati">Personel fiyatı (TL)</label>
                    <input type="number" id="personel_fiyati" name="personel_fiyati" min="0" max="999999.99" step="0.01" value="<?= htmlspecialchars($duzenlemeVar ? (string)$duzenlenecekMenu['personel_fiyati'] : '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>

            <h3>Menüde bulunacak yemekler</h3>
            <div class="yemek-listesi">
                <?php foreach ($formVerileri['yemekler'] as $yemek): ?>
                    <label class="yemek-secim">
                        <input type="checkbox" name="yemek_ids[]" value="<?= (int)$yemek['id'] ?>" <?= in_array((int)$yemek['id'], $seciliYemekler, true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($yemek['ad'], ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($yemek['kategori'], ENT_QUOTES, 'UTF-8') ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="form-alt">
                <button type="submit"><?= $duzenlemeVar ? 'Menüyü Güncelle' : 'Menüyü Oluştur' ?></button>
                <?php if ($duzenlemeVar): ?>
                    <a href="/yonetici#menu-formu">Yeni menü oluştur</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="kart">
        <h2>Menü Doluluk ve Ortalama Puan Raporu</h2>
        <p class="aciklama">Bu rapordaki menü, doluluk ve değerlendirme bilgileri replika veritabanından okunur. Replikasyon kısa süreli gecikme gösterebilir.</p>

        <?php if (empty($menuRaporlari)): ?>
            <p class="aciklama">Henüz raporlanacak menü bulunmuyor.</p>
        <?php else: ?>
            <div class="tablo-kapsayici">
                <table>
                    <thead>
                    <tr>
                        <th>Tarih / Öğün</th>
                        <th>Yemekhane</th>
                        <th>Yemekler</th>
                        <th>Öğrenci / Personel</th>
                        <th>Rezerve / Kapasite</th>
                        <th>Boş Yer</th>
                        <th>Doluluk</th>
                        <th>Ortalama Puan</th>
                        <th>Oy Sayısı</th>
                        <th>İşlem</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($menuRaporlari as $rapor): ?>
                        <tr>
                            <td><?= date('d.m.Y', strtotime($rapor['tarih'])) ?><br><?= htmlspecialchars($rapor['ogun'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($rapor['yemekhane'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($rapor['yemekler'] ?? 'Yemek eklenmemiş', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$rapor['ogrenci_fiyati'], 2, ',', '.') ?> TL<br><?= number_format((float)$rapor['personel_fiyati'], 2, ',', '.') ?> TL</td>
                            <td><?= (int)$rapor['rezerve'] ?> / <?= (int)$rapor['kapasite'] ?></td>
                            <td><?= (int)$rapor['bos_yer'] ?></td>
                            <td>%<?= number_format((float)$rapor['doluluk_yuzde'], 1, ',', '.') ?></td>
                            <td><?= $rapor['ortalama_puan'] === null ? 'Henüz yok' : number_format((float)$rapor['ortalama_puan'], 2, ',', '.') . ' / 5' ?></td>
                            <td><?= (int)$rapor['oy_sayisi'] ?></td>
                            <td><a class="duzenle" href="/yonetici?duzenle=<?= (int)$rapor['menu_id'] ?>#menu-formu">Düzenle</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
