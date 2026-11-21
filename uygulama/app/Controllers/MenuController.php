<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Menu.php';
require_once __DIR__ . '/../Models/Rezervasyon.php';
require_once __DIR__ . '/../Models/Bakiye.php';

class MenuController
{
    public function gununMenusu(): void
    {
        if (!isset($_SESSION['kullanici'])) {
            header('Location: /giris');
            exit;
        }

        $hata = '';
        $yemekhaneler = [];
        $ogunler = [];
        $yemekler = [];
        $seciliTarih = $_GET['tarih'] ?? date('Y-m-d');

        if (!is_string($seciliTarih)) {
            $seciliTarih = date('Y-m-d');
        }

        $tarihNesnesi = DateTime::createFromFormat('!Y-m-d', $seciliTarih);
        if (!$tarihNesnesi || $tarihNesnesi->format('Y-m-d') !== $seciliTarih) {
            $seciliTarih = date('Y-m-d');
        }

        $yemekhaneId = 0;
        $ogunId = 0;

        try {
            $model = new Menu();
            $yemekhaneler = $model->yemekhaneleriGetir();
            $ogunler = $model->ogunleriGetir();

            $gelenYemekhane = filter_input(INPUT_GET, 'yemekhane_id', FILTER_VALIDATE_INT);
            $gelenOgun = filter_input(INPUT_GET, 'ogun_id', FILTER_VALIDATE_INT);

            $yemekhaneId = $gelenYemekhane ?: (int)($yemekhaneler[0]['id'] ?? 0);
            $ogunId = $gelenOgun ?: (int)($ogunler[0]['id'] ?? 0);

            if ($yemekhaneId > 0 && $ogunId > 0) {
                $yemekler = $model->gununMenuDetayiniGetir(
                    $seciliTarih,
                    $yemekhaneId,
                    $ogunId
                );
            }
        } catch (Throwable $e) {
            error_log('Günün menüsü alınırken hata: ' . $e->getMessage());
            $hata = 'Menü bilgileri şu anda alınamıyor. Lütfen tekrar deneyin.';
        }

        $toplamKalori = 0;
        foreach ($yemekler as $yemek) {
            $toplamKalori += (int)$yemek['kalori'];
        }

        $kullanici = $_SESSION['kullanici'];
        require __DIR__ . '/../Views/gunun-menusu.php';
    }

    /** Görev 3: Haftalık menü, fiyat, boş yer ve rezervasyon. */
    public function haftalikMenu(): void
    {
        if (!isset($_SESSION['kullanici'])) {
            header('Location: /giris');
            exit;
        }

        $hata = '';
        $yemekhaneler = [];
        $yemekler = [];
        $kullanici = $_SESSION['kullanici'];
        $seciliTarih = $_GET['tarih'] ?? date('Y-m-d');

        if (!is_string($seciliTarih)) {
            $seciliTarih = date('Y-m-d');
        }

        $tarihNesnesi = DateTime::createFromFormat('!Y-m-d', $seciliTarih);
        if (!$tarihNesnesi || $tarihNesnesi->format('Y-m-d') !== $seciliTarih) {
            $seciliTarih = date('Y-m-d');
        }

        $yemekhaneId = 0;
        $gelenYemekhane = filter_input(INPUT_GET, 'yemekhane_id', FILTER_VALIDATE_INT);

        try {
            $model = new Menu();
            $yemekhaneler = $model->yemekhaneleriGetir();
            $yemekhaneId = $gelenYemekhane ?: (int)($yemekhaneler[0]['id'] ?? 0);

            if ($yemekhaneId > 0) {
                $yemekler = $model->haftalikMenuGetir(
                    $seciliTarih,
                    $yemekhaneId,
                    (int)$kullanici['id']
                );
            }
        } catch (Throwable $e) {
            error_log('Haftalık menü alınırken hata: ' . $e->getMessage());
            $hata = 'Haftalık menü bilgileri şu anda alınamıyor.';
        }

        $bildirim = $this->bildirimAl();

        // Rezervasyonun hemen ardından güncel bakiye kaynak MySQL'den okunur.
        $bakiye = null;
        try {
            $bakiyeModel = new Bakiye();
            $bakiye = $bakiyeModel->guncelBakiyeGetir((int)$kullanici['id']);
        } catch (Throwable $e) {
            error_log('Haftalık menüde güncel bakiye okunamadı: ' . $e->getMessage());
        }

        require __DIR__ . '/../Views/haftalik-menu.php';
    }

    /** Haftalık menü ekranından gelen rezervasyon formunu işler. */
    public function rezervasyonYap(): void
    {
        if (!isset($_SESSION['kullanici'])) {
            header('Location: /giris');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo 'Geçersiz istek yöntemi.';
            return;
        }

        if (!$this->csrfGecerliMi()) {
            http_response_code(400);
            echo 'Geçersiz istek.';
            return;
        }

        $tarih = $_POST['tarih'] ?? date('Y-m-d');
        if (!is_string($tarih)) {
            $tarih = date('Y-m-d');
        }

        $tarihNesnesi = DateTime::createFromFormat('!Y-m-d', $tarih);
        if (!$tarihNesnesi || $tarihNesnesi->format('Y-m-d') !== $tarih) {
            $tarih = date('Y-m-d');
        }

        $yemekhaneId = filter_var($_POST['yemekhane_id'] ?? null, FILTER_VALIDATE_INT);
        $geriDon = '/haftalik-menu?tarih=' . urlencode($tarih);

        if ($yemekhaneId !== false && $yemekhaneId !== null && $yemekhaneId > 0) {
            $geriDon .= '&yemekhane_id=' . $yemekhaneId;
        }

        $menuId = filter_var($_POST['menu_id'] ?? null, FILTER_VALIDATE_INT);
        if ($menuId === false || $menuId === null || $menuId <= 0) {
            $this->bildirimAyarla('hata', 'Geçersiz menü bilgisi.');
            header('Location: ' . $geriDon);
            exit;
        }

        try {
            $model = new Rezervasyon();
            $sonuc = $model->olustur((int)$_SESSION['kullanici']['id'], $menuId);
            $this->bildirimAyarla(
                $sonuc['basarili'] ? 'basarili' : 'hata',
                $sonuc['mesaj']
            );
        } catch (Throwable $e) {
            error_log('Rezervasyon isteği hatası: ' . $e->getMessage());
            $this->bildirimAyarla('hata', 'Rezervasyon işlemi gerçekleştirilemedi.');
        }

        header('Location: ' . $geriDon);
        exit;
    }

    private function csrfGecerliMi(): bool
    {
        $oturumToken = $_SESSION['csrf'] ?? '';
        $gelenToken = $_POST['csrf'] ?? '';

        return is_string($oturumToken)
            && $oturumToken !== ''
            && is_string($gelenToken)
            && hash_equals($oturumToken, $gelenToken);
    }

    private function bildirimAyarla(string $tur, string $mesaj): void
    {
        $_SESSION['bildirim_turu'] = $tur;
        $_SESSION['bildirim_mesaji'] = $mesaj;
    }

    private function bildirimAl(): array
    {
        $bildirim = [
            'tur' => $_SESSION['bildirim_turu'] ?? '',
            'mesaj' => $_SESSION['bildirim_mesaji'] ?? '',
        ];

        unset($_SESSION['bildirim_turu'], $_SESSION['bildirim_mesaji']);
        return $bildirim;
    }
}
