<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Support\Exceptions\MailTransportException;
use Rbn\Framework\Packages\RbnEmail\Support\ImapConnection;

/**
 * ImapClientHandler - Native Socket IMAP Protocol Engine 📬⚡
 *
 * PHP IMAP uzantısına bağımlı olmadan saf SSL/TLS soket üzerinden IMAP4rev1.
 *
 * `open($config)` doğrulanmış, oturum açılmış bir `ImapConnection`
 * döndürür; çağıran işini bitirince `logout()` çağırır.
 * Handler kayıtta tekil (paylaşılan) olduğu için bağlantı handler'da
 * DEĞİL, dönen nesnede tutulur — aynı istekte birden çok hesap güvenle açılır.
 */
class ImapClientHandler extends BaseComponent
{
    /**
     * Bağlanır ve giriş yapar.
     *
     * @param array $config host, port, security (ssl|starttls), username, password, timeout?, ca_file?
     * @throws MailTransportException IMAP_UNAVAILABLE | TLS_VERIFY_FAILED | AUTH_FAILED
     */
    public function open(array $config): ImapConnection
    {
        $connection = new ImapConnection($config);
        try {
            $connection->login((string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''));
        } catch (\Throwable $e) {
            $connection->close();
            throw $e;
        }
        return $connection;
    }
}
