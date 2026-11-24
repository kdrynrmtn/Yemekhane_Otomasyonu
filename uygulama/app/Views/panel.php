<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel | Yemekhane Otomasyonu</title>
    <link rel="stylesheet" href="/css/temel.css">
    <style>
        body{background:#f3f6f4;min-height:100vh;padding:24px}
        .panel{max-width:1000px;margin:35px auto;background:#fff;padding:28px;border-radius:14px}
        .ust{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}
        .rol{color:#286b3b;font-weight:700;text-transform:capitalize}
        button{padding:10px 16px;background:#286b3b;color:#fff;border:0;border-radius:8px;cursor:pointer}
        .kartlar{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin-top:22px}
        .kart{display:block;background:#f7faf7;border:1px solid #e0e9e1;border-radius:12px;padding:20px;color:#173b2b;text-decoration:none}
        .kart:hover{border-color:#286b3b;background:#eef6ef}
        .kart h2{font-size:18px;margin:0 0 8px}.kart p{margin:0;color:#66756c;line-height:1.5}
        @media(max-width:500px){body{padding:10px}.panel{margin:10px auto;padding:18px}}
    </style>
</head>
<body>
<main class="panel">
    <div class="ust">
        <div>
            <h1>Hoş geldin, <?= htmlspecialchars($kullanici['ad'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="rol"><?= htmlspecialchars($kullanici['rol'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <form action="/cikis" method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Çıkış Yap</button>
        </form>
    </div>

    <h2>Yemekhane İşlemleri</h2>
    <p>İşlem yapmak istediğiniz bölümü seçin.</p>

    <section class="kartlar">
        <a class="kart" href="/gunun-menusu">
            <h2>Günün Menüsü</h2>
            <p>Yemekhane ve öğün seçerek yemekleri, kalorileri ve alerjenleri görüntüleyin.</p>
        </a>
        <a class="kart" href="/haftalik-menu">
            <h2>Haftalık Menü ve Rezervasyon</h2>
            <p>Haftalık menüyü, öğün fiyatlarını ve boş yer sayılarını görün; rezervasyon yapın.</p>
        </a>
        <a class="kart" href="/rezervasyonlarim">
            <h2>Rezervasyonlarım</h2>
            <p>Aktif, kullanılmış ve iptal edilmiş rezervasyonlarınızı görüntüleyin.</p>
        </a>
        <a class="kart" href="/bakiye">
            <h2>Bakiye</h2>
            <p>Güncel bakiyenizi görün, bakiye yükleyin ve hareket geçmişinizi inceleyin.</p>
        </a>
        <a class="kart" href="/profil">
            <h2>Profil ve Alerjen Tercihleri</h2>
            <p>Alerjenlerinizi seçin; bu alerjenleri içeren yemekler menüde uyarıyla gösterilsin.</p>
        </a>
        <a class="kart" href="/degerlendirme">
            <h2>Değerlendirme</h2>
            <p>Kullanılmış menülere 1–5 puan verin ve yorumunuzu kaydedin.</p>
        </a>
        <?php if (($kullanici['rol'] ?? '') === 'yonetici'): ?>
            <a class="kart" href="/yonetici">
                <h2>Yönetici Paneli</h2>
                <p>Menü oluşturun, menüleri düzenleyin; doluluk ve ortalama puan raporlarını inceleyin.</p>
            </a>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
