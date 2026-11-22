<?php
require_once __DIR__ . '/../app/RedisBaglanti.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Bu betik yalnızca komut satırından çalıştırılabilir.\n");
}

try {
    $redis = RedisBaglanti::istemci();
    $cursor = null;
    $silinen = 0;

    do {
        $anahtarlar = $redis->scan($cursor, 'menu:*', 500);
        if (is_array($anahtarlar) && $anahtarlar !== []) {
            $silinen += (int)$redis->del($anahtarlar);
        }
    } while ($cursor !== 0 && $cursor !== null);

    echo "Eski menü önbelleği temizlendi. Silinen anahtar sayısı: {$silinen}\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Redis menü önbelleği temizlenemedi: ' . $e->getMessage() . "\n");
    exit(1);
}
