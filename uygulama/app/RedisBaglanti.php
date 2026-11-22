<?php
declare(strict_types=1);

/** Redis sunucusuna uygulama içinden bağlantı sağlar. */
class RedisBaglanti
{
    private static ?Redis $istemci = null;

    public static function istemci(): Redis
    {
        if (self::$istemci instanceof Redis) {
            try {
                if (self::$istemci->isConnected()) {
                    return self::$istemci;
                }
            } catch (Throwable $e) {
                self::$istemci = null;
            }
        }

        if (!extension_loaded('redis')) {
            throw new RuntimeException('PHP Redis eklentisi yüklü değil.');
        }

        $host = getenv('REDIS_HOST') ?: 'redis';
        $port = (int)(getenv('REDIS_PORT') ?: 6379);
        $timeout = 2.5;

        $redis = new Redis();
        if (!$redis->connect($host, $port, $timeout)) {
            throw new RuntimeException('Redis sunucusuna bağlanılamadı.');
        }

        self::$istemci = $redis;
        return self::$istemci;
    }
}
