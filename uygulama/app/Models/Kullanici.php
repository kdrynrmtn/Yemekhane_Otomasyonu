<?php
declare(strict_types=1);

require_once __DIR__ . '/../Database.php';

class Kullanici
{
    public function epostaIleBul(string $eposta): ?array
    {
        $db = Database::okuma();
        $sorgu = $db->prepare(
            'SELECT id, ad, soyad, eposta, sifre_hash, rol, bakiye, aktif
             FROM kullanicilar WHERE eposta = :eposta LIMIT 1'
        );
        $sorgu->execute(['eposta' => $eposta]);
        $kullanici = $sorgu->fetch();
        return $kullanici ?: null;
    }
}
