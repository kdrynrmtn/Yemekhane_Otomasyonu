<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Bakiye.php';

class BakiyeController
{
    /** Görev 5: Güncel bakiye ve geçmiş para hareketleri. */
    public function index(): void
    {
        if (!isset($_SESSION['kullanici'])) {
            header('Location: /giris');
            exit;
        }

        $kullanici = $_SESSION['kullanici'];
        $bakiye = '0.00';
        $hareketler = [];
        $hata = '';

        try {
            $model = new Bakiye();
            // Güncel bakiye kaynaktan, hareket listesi replikadan okunur.
            $bakiye = $model->guncelBakiyeGetir((int)$kullanici['id']);
            $hareketler = $model->hareketleriGetir((int)$kullanici['id']);
        } catch (Throwable $e) {
            error_log('Bakiye bilgileri alınırken hata: ' . $e->getMessage());
            $hata = 'Bakiye bilgileriniz şu anda alınamıyor.';
        }

        $bildirim = $this->bildirimAl();
        require __DIR__ . '/../Views/bakiye.php';
    }

    /** Bakiye yükleme işlemini sp_bakiye_yukle prosedürüne iletir. */
    public function yukle(): void
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

        $tutar = $_POST['tutar'] ?? '';
        if (!is_string($tutar)) {
            $tutar = '';
        }

        try {
            $model = new Bakiye();
            $sonuc = $model->yukle((int)$_SESSION['kullanici']['id'], $tutar);
            $this->bildirimAyarla(
                $sonuc['basarili'] ? 'basarili' : 'hata',
                $sonuc['mesaj']
            );
        } catch (Throwable $e) {
            error_log('Bakiye yükleme isteği hatası: ' . $e->getMessage());
            $this->bildirimAyarla('hata', 'Bakiye yükleme işlemi gerçekleştirilemedi.');
        }

        header('Location: /bakiye');
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
