<?php
$gunler = [
    'Monday' => 'Pazartesi',
    'Tuesday' => 'Salı',
    'Wednesday' => 'Çarşamba',
    'Thursday' => 'Perşembe',
    'Friday' => 'Cuma',
    'Saturday' => 'Cumartesi',
    'Sunday' => 'Pazar',
];

$haftalikGruplar = [];
foreach ($yemekler as $yemek) {
    $tarih = $yemek['tarih'];
    $menuId = (int)$yemek['menu_id'];

    if (!isset($haftalikGruplar[$tarih][$menuId])) {
        $haftalikGruplar[$tarih][$menuId] = [
            'menu' => $yemek,
            'yemekler' => [],
        ];
    }

    $haftalikGruplar[$tarih][$menuId]['yemekler'][] = $yemek;
}

$rol = $kullanici['rol'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haftalık Menü ve Rezervasyon</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 22px; font-family: Arial, sans-serif; background: #f3f6f4; color: #26352a; }
        .container { max-width: 1100px; margin: 0 auto; }
        .ust-alan { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 22px; }
        h1 { margin: 0; color: #173b2b; font-size: 27px; }
        .geri, .link { display: inline-block; color: #fff; background: #34495e; padding: 10px 15px; border-radius: 7px; text-decoration: none; }
        .navigasyon { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
        .navigasyon .link { background: #286b3b; }
        .filtre, .gun-kart { background: #fff; padding: 19px; border-radius: 12px; margin-bottom: 18px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
        .filtre form { display: flex; align-items: end; flex-wrap: wrap; gap: 14px; }
        label { display: block; font-weight: 700; margin-bottom: 6px; }
        input, select, button { padding: 10px; border: 1px solid #cbd5ce; border-radius: 7px; font-size: 15px; }
        button { background: #286b3b; color: #fff; border: 0; cursor: pointer; }
        button:hover { background: #1f5630; }
        button:disabled { background: #aab3ac; cursor: not-allowed; }
        .gun-baslik { color: #286b3b; border-bottom: 1px solid #e5ebe6; padding-bottom: 10px; margin: 0 0 16px; }
        .ogun { border: 1px solid #e4ebe5; border-radius: 10px; padding: 15px; margin-top: 14px; }
        .ogun-baslik { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px; }
        .ogun h3 { margin: 0 0 8px; color: #34495e; }
        .yemek { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 10px; background: #f8faf8; border-radius: 7px; margin-top: 7px; }
        .bilgi { font-size: 13px; color: #68756c; }
        .detaylar { display: flex; flex-wrap: wrap; gap: 8px 18px; margin: 8px 0 12px; font-size: 14px; }
        .fiyat { font-weight: 700; color: #173b2b; }
        .alerjen-uyari { margin-top: 7px; padding: 8px 10px; border-radius: 7px; background: #fff0ed; color: #9a2f20; font-size: 13px; font-weight: 700; }
        .porsiyon-alani { margin-top: 10px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .porsiyon-alani label { font-weight: 700; font-size: 14px; }
        .rezervasyon-fiyat { color: #173b2b; font-weight: 700; }
        .rezervasyon-form { margin-top: 12px; }
        .mesaj { padding: 13px 15px; border-radius: 8px; margin-bottom: 16px; background: #fff3cd; color: #664d03; }
        .mesaj.basarili { background: #d4edda; color: #155724; }
        .mesaj.hata { background: #f8d7da; color: #721c24; }
        .bos { color: #68756c; padding: 12px 0; }
        @media (max-width: 600px) {
            body { padding: 12px; }
            h1 { font-size: 22px; }
            .filtre form, .filtre form > div { display: block; width: 100%; }
            .filtre form > div { margin-bottom: 12px; }
            input, select, .filtre button, .rezervasyon-form button { width: 100%; }
            .ogun { padding: 11px; }
        }
    </style>
</head>
<body>
<main class="container">
    <div class="ust-alan">
        <h1>Haftalık Yemek Menüsü</h1>
        <a href="/panel" class="geri">Panele Dön</a>
    </div>

    <nav class="navigasyon">
        <a class="link" href="/rezervasyonlarim">Rezervasyonlarım</a>
        <a class="link" href="/bakiye">Bakiyem</a>
        <a class="link" href="/degerlendirme">Değerlendirme</a>
        <a class="link" href="/profil">Alerjen Tercihlerim</a>
        <?php if (($kullanici['rol'] ?? '') === 'yonetici'): ?>
            <a class="link" href="/yonetici">Yönetici Paneli</a>
        <?php endif; ?>
    </nav>

    <?php if ($bakiye !== null): ?>
        <div class="mesaj basarili">Güncel bakiyeniz: <strong><?= number_format((float)$bakiye, 2, ',', '.') ?> TL</strong></div>
    <?php endif; ?>

    <?php if (!empty($hata)): ?>
        <div class="mesaj hata"><?= htmlspecialchars($hata, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($bildirim['mesaj'])): ?>
        <div class="mesaj <?= ($bildirim['tur'] ?? '') === 'basarili' ? 'basarili' : 'hata' ?>">
            <?= htmlspecialchars($bildirim['mesaj'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="filtre">
        <form method="GET" action="/haftalik-menu">
            <div>
                <label for="tarih">Hafta başlangıç tarihi</label>
                <input type="date" id="tarih" name="tarih"
                       value="<?= htmlspecialchars($seciliTarih, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div>
                <label for="yemekhane_id">Yemekhane</label>
                <select name="yemekhane_id" id="yemekhane_id">
                    <?php foreach ($yemekhaneler as $yemekhane): ?>
                        <option value="<?= (int)$yemekhane['id'] ?>"
                            <?= $yemekhaneId === (int)$yemekhane['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($yemekhane['ad'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><button type="submit">Menüyü Göster</button></div>
        </form>
    </section>

    <?php if (empty($yemekler) && empty($hata)): ?>
        <div class="mesaj">Seçilen tarih aralığında menü bulunamadı.</div>
    <?php endif; ?>

    <?php foreach ($haftalikGruplar as $tarih => $menuler): ?>
        <?php
        $tarihNesnesi = new DateTime($tarih);
        $gunAdi = $gunler[$tarihNesnesi->format('l')] ?? $tarihNesnesi->format('l');
        ?>
        <section class="gun-kart">
            <h2 class="gun-baslik">
                <?= htmlspecialchars($gunAdi, ENT_QUOTES, 'UTF-8') ?> - <?= $tarihNesnesi->format('d.m.Y') ?>
            </h2>

            <?php foreach ($menuler as $menuGrubu): ?>
                <?php
                $menu = $menuGrubu['menu'];
                $ogunYemekleri = $menuGrubu['yemekler'];
                $fiyat = $rol === 'ogrenci'
                    ? $menu['ogrenci_fiyati']
                    : $menu['personel_fiyati'];
                $rezervasyonDurumu = $menu['kullanici_rezervasyon_durumu'] ?? null;
                $kullaniciRezervasyonYapmis = in_array($rezervasyonDurumu, ['aktif', 'kullanildi'], true);
                $sonSaatGecmis = (int)$menu['rezervasyon_acik'] !== 1;
                $yerYok = (int)$menu['bos_yer'] <= 0;
                $yarimPorsiyonUygun = in_array((int)$menu['ogun_id'], [2, 3], true);
                $yarimFiyat = round((float)$fiyat * 0.60, 2);
                $butonPasif = $kullaniciRezervasyonYapmis || $sonSaatGecmis || $yerYok;
                ?>
                <div class="ogun">
                    <div class="ogun-baslik">
                        <h3><?= htmlspecialchars($menu['ogun'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="fiyat">Tam porsiyon: <?= number_format((float)$fiyat, 2, ',', '.') ?> TL</span>
                    </div>

                    <div class="detaylar">
                        <span>Boş yer: <strong><?= (int)$menu['bos_yer'] ?></strong> / <?= (int)$menu['kapasite'] ?></span>
                        <span>Rezervasyon son: <strong><?= date('d.m.Y H:i', strtotime($menu['rezervasyon_son'])) ?></strong></span>
                    </div>

                    <?php foreach ($ogunYemekleri as $yemek): ?>
                        <div class="yemek">
                            <div>
                                <strong><?= htmlspecialchars($yemek['yemek'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <div class="bilgi">
                                    <?= htmlspecialchars($yemek['kategori'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($yemek['vejetaryen'])): ?> · Vejetaryen<?php endif; ?>
                                    <?php if (!empty($yemek['alerjenler'])): ?>
                                        · Alerjen: <?= htmlspecialchars($yemek['alerjenler'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($yemek['alerjen_uyarilari'])): ?>
                                    <div class="alerjen-uyari">DİKKAT: Seçtiğiniz alerjenleri içeriyor: <?= htmlspecialchars(implode(', ', $yemek['alerjen_uyarilari']), ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="bilgi"><?= (int)$yemek['kalori'] ?> kcal</span>
                        </div>
                    <?php endforeach; ?>

                    <form method="POST" action="/rezervasyon-yap" class="rezervasyon-form">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="menu_id" value="<?= (int)$menu['menu_id'] ?>">
                        <input type="hidden" name="tarih" value="<?= htmlspecialchars($tarih, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="yemekhane_id" value="<?= (int)$yemekhaneId ?>">

                        <?php if ($yarimPorsiyonUygun): ?>
                            <div class="porsiyon-alani">
                                <label for="porsiyon-<?= (int)$menu['menu_id'] ?>">Porsiyon:</label>
                                <select
                                    id="porsiyon-<?= (int)$menu['menu_id'] ?>"
                                    name="porsiyon"
                                    class="porsiyon-secimi"
                                    data-tam-fiyat="<?= htmlspecialchars(number_format((float)$fiyat, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-yarim-fiyat="<?= htmlspecialchars(number_format($yarimFiyat, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $butonPasif ? 'disabled' : '' ?>
                                >
                                    <option value="tam">Tam porsiyon</option>
                                    <option value="yarim">Yarım porsiyon (%60 ücret)</option>
                                </select>
                                <span class="rezervasyon-fiyat" data-fiyat-alani>Ödenecek: <?= number_format((float)$fiyat, 2, ',', '.') ?> TL</span>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="porsiyon" value="tam">
                            <div class="porsiyon-alani"><span class="bilgi">Kahvaltıda yalnızca tam porsiyon seçilebilir.</span></div>
                        <?php endif; ?>

                        <?php if ($kullaniciRezervasyonYapmis): ?>
                            <button type="button" disabled>Bu öğün için rezervasyonunuz var</button>
                        <?php elseif ($sonSaatGecmis): ?>
                            <button type="button" disabled>Rezervasyon süresi doldu</button>
                        <?php elseif ($yerYok): ?>
                            <button type="button" disabled>Yemekhane kapasitesi doldu</button>
                        <?php else: ?>
                            <button type="submit">Bu Öğün İçin Rezervasyon Yap</button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
</main>
<script>
document.querySelectorAll('.rezervasyon-form').forEach(function (form) {
    const secim = form.querySelector('.porsiyon-secimi');
    const fiyatAlani = form.querySelector('[data-fiyat-alani]');
    if (!secim || !fiyatAlani) return;

    function fiyatGuncelle() {
        const fiyat = secim.value === 'yarim'
            ? Number(secim.dataset.yarimFiyat)
            : Number(secim.dataset.tamFiyat);
        fiyatAlani.textContent = 'Ödenecek: ' + fiyat.toLocaleString('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' TL';
    }

    secim.addEventListener('change', fiyatGuncelle);
    fiyatGuncelle();
});
</script>
</body>
</html>
