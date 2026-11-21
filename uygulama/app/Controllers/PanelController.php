<?php
declare(strict_types=1);

class PanelController
{
    public function index(): void
    {
        if (!isset($_SESSION['kullanici'])) {
            header('Location: /giris');
            exit;
        }
        $kullanici = $_SESSION['kullanici'];
        require __DIR__ . '/../Views/panel.php';
    }
}
