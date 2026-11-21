<?php

require_once __DIR__ . '/../Models/Degerlendirme.php';

class DegerlendirmeController
{
    public function index(): void
    {
        if (!isset($_SESSION['kullanici'])) {
            header('Location: /giris');
            exit;
        }

        $kullanici = $_SESSION['kullanici'];
        $degerlendirilecekMenuler = [];
        $hata = '';

        try {
            $model = new Degerlendirme();
            $degerlendirilecekMenuler = $model->kullanilmisMenuleriGetir(
                (int)$kullanici['id']
            );
        } catch (Throwable $e) {
            error_log('Değerlendirmeler alınırken hata: ' . $e->getMessage());
            $hata = 'Değerlendirme bilgileri şu anda alınamıyor.';
        }

        $bildirim = $this->bildirimAl();
        require __DIR__ . '/../Views/degerlendirme.php';
    }

    public function kaydet(): void
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

        $menuId = filter_var($_POST['menu_id'] ?? null, FILTER_VALIDATE_INT);
        $puan = filter_var($_POST['puan'] ?? null, FILTER_VALIDATE_INT);
        $yorum = $_POST['yorum'] ?? '';

        if (
            $menuId === false || $menuId === null || $menuId <= 0 ||
            $puan === false || $puan === null || $puan < 1 || $puan > 5 ||
            !is_string($yorum)
        ) {
            $this->bildirimAyarla('hata', 'Menü, puan veya yorum bilgisi geçersiz.');
            header('Location: /degerlendirme');
            exit;
        }

        try {
            $model = new Degerlendirme();
            $model->kaydet(
                (int)$_SESSION['kullanici']['id'],
                (int)$menuId,
                (int)$puan,
                $yorum
            );
            $this->bildirimAyarla('basarili', 'Değerlendirmeniz kaydedildi.');
        } catch (DomainException | InvalidArgumentException $e) {
            $this->bildirimAyarla('hata', $e->getMessage());
        } catch (Throwable $e) {
            error_log('Değerlendirme kaydetme hatası: ' . $e->getMessage());
            $this->bildirimAyarla('hata', 'Değerlendirme kaydedilemedi. Lütfen tekrar deneyin.');
        }

        header('Location: /degerlendirme');
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
        $_SESSION['degerlendirme_bildirim_turu'] = $tur;
        $_SESSION['degerlendirme_bildirim_mesaji'] = $mesaj;
    }

    private function bildirimAl(): array
    {
        $bildirim = [
            'tur' => $_SESSION['degerlendirme_bildirim_turu'] ?? '',
            'mesaj' => $_SESSION['degerlendirme_bildirim_mesaji'] ?? '',
        ];

        unset(
            $_SESSION['degerlendirme_bildirim_turu'],
            $_SESSION['degerlendirme_bildirim_mesaji']
        );

        return $bildirim;
    }
}
