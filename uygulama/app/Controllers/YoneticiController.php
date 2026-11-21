<?php

require_once __DIR__ . '/../Models/Yonetim.php';

class YoneticiController
{
    public function index(): void
    {
        $this->yoneticiKontrolEt();

        $hata = '';
        $formVerileri = [
            'yemekhaneler' => [],
            'ogunler' => [],
            'yemekler' => [],
        ];
        $menuRaporlari = [];
        $duzenlenecekMenu = null;

        try {
            $model = new Yonetim();
            $formVerileri = $model->formVerileriniGetir();
            $menuRaporlari = $model->menuRaporlariniGetir();

            $duzenleId = filter_input(INPUT_GET, 'duzenle', FILTER_VALIDATE_INT);
            if ($duzenleId !== false && $duzenleId !== null && $duzenleId > 0) {
                $duzenlenecekMenu = $model->menuDuzenlemeBilgisiGetir($duzenleId);
                if (!$duzenlenecekMenu) {
                    $hata = 'Düzenlenecek menü bulunamadı.';
                }
            }
        } catch (Throwable $e) {
            error_log('Yönetici paneli verileri alınırken hata: ' . $e->getMessage());
            $hata = 'Yönetici paneli bilgileri şu anda alınamıyor.';
        }

        $bildirim = $this->bildirimAl();
        $kullanici = $_SESSION['kullanici'];
        require __DIR__ . '/../Views/yonetici.php';
    }

    public function menuKaydet(): void
    {
        $this->yoneticiKontrolEt();

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

        $menuIdHam = $_POST['menu_id'] ?? '';
        $menuId = null;
        if ($menuIdHam !== '') {
            $menuId = filter_var($menuIdHam, FILTER_VALIDATE_INT);
            if ($menuId === false || $menuId === null || $menuId <= 0) {
                $this->bildirimAyarla('hata', 'Menü numarası geçersiz.');
                header('Location: /yonetici');
                exit;
            }
        }

        $yemekhaneId = filter_var($_POST['yemekhane_id'] ?? null, FILTER_VALIDATE_INT);
        $ogunId = filter_var($_POST['ogun_id'] ?? null, FILTER_VALIDATE_INT);
        $tarih = $_POST['tarih'] ?? '';
        $ogrenciFiyati = $_POST['ogrenci_fiyati'] ?? '';
        $personelFiyati = $_POST['personel_fiyati'] ?? '';
        $yemekIds = $_POST['yemek_ids'] ?? [];

        if (
            $yemekhaneId === false || $yemekhaneId === null ||
            $ogunId === false || $ogunId === null ||
            !is_string($tarih) ||
            !is_string($ogrenciFiyati) ||
            !is_string($personelFiyati) ||
            !is_array($yemekIds)
        ) {
            $this->bildirimAyarla('hata', 'Formdaki bilgiler geçersiz.');
            header('Location: /yonetici');
            exit;
        }

        try {
            $model = new Yonetim();
            $kaydedilenId = $model->menuKaydet(
                $menuId === null ? null : (int)$menuId,
                (int)$yemekhaneId,
                $tarih,
                (int)$ogunId,
                $ogrenciFiyati,
                $personelFiyati,
                $yemekIds
            );

            $mesaj = $menuId === null
                ? 'Yeni menü oluşturuldu.'
                : 'Menü bilgileri güncellendi.';
            $this->bildirimAyarla('basarili', $mesaj);
            header('Location: /yonetici?duzenle=' . $kaydedilenId . '#menu-formu');
            exit;
        } catch (PDOException $e) {
            error_log('Menü kaydetme veritabanı hatası: ' . $e->getMessage());
            $mesaj = (string)($e->errorInfo[2] ?? '');
            if (str_contains($mesaj, 'uq_menu') || (string)$e->getCode() === '23000') {
                $mesaj = 'Bu yemekhane, tarih ve öğün için zaten bir menü bulunuyor.';
            } else {
                $mesaj = 'Menü kaydedilemedi. Veritabanı bilgilerini kontrol edin.';
            }
            $this->bildirimAyarla('hata', $mesaj);
        } catch (InvalidArgumentException | DomainException | RuntimeException $e) {
            $this->bildirimAyarla('hata', $e->getMessage());
        } catch (Throwable $e) {
            error_log('Menü kaydetme hatası: ' . $e->getMessage());
            $this->bildirimAyarla('hata', 'Menü kaydedilemedi. Lütfen tekrar deneyin.');
        }

        header('Location: /yonetici');
        exit;
    }

    private function yoneticiKontrolEt(): void
    {
        if (!isset($_SESSION['kullanici'])) {
            header('Location: /giris');
            exit;
        }

        if (($_SESSION['kullanici']['rol'] ?? '') !== 'yonetici') {
            http_response_code(403);
            echo 'Bu sayfaya yalnızca yöneticiler erişebilir.';
            exit;
        }
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
        $_SESSION['yonetici_bildirim_turu'] = $tur;
        $_SESSION['yonetici_bildirim_mesaji'] = $mesaj;
    }

    private function bildirimAl(): array
    {
        $bildirim = [
            'tur' => $_SESSION['yonetici_bildirim_turu'] ?? '',
            'mesaj' => $_SESSION['yonetici_bildirim_mesaji'] ?? '',
        ];

        unset($_SESSION['yonetici_bildirim_turu'], $_SESSION['yonetici_bildirim_mesaji']);
        return $bildirim;
    }
}
