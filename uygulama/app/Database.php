<?php
declare(strict_types=1);

class Database
{
    private static function baglan(string $sunucu): PDO
    {
        $varsayilanSunucu = $sunucu === 'DB_YAZMA_HOST' ? 'mysql-primary' : 'mysql-replica';
        $host = getenv($sunucu) ?: $varsayilanSunucu;
        $veritabani = getenv('DB_ADI') ?: 'yemekhane';
        $port = getenv('DB_PORT') ?: '3306';
        $kullanici = getenv('DB_KULLANICI') ?: 'yemekhane_app';
        $sifre = getenv('DB_SIFRE') ?: '';

        $dsn = "mysql:host={$host};port={$port};dbname={$veritabani};charset=utf8mb4";
        return new PDO($dsn, $kullanici, $sifre, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function okuma(): PDO
    {
        return self::baglan('DB_OKUMA_HOST');
    }

    public static function yazma(): PDO
    {
        return self::baglan('DB_YAZMA_HOST');
    }
}
