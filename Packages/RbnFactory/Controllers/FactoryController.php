<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Packages\RbnFactory\Models\FactoryContract;

/**
 * FactoryController - VPS içerik fabrikası uçları (api/factory/jobs, api/factory/result) 🏭
 * VPS siteyi yoklar; her istek ve her 2xx cevap HMAC ile imzalıdır. İş mantığı FactoryService'tedir.
 */
class FactoryController extends BaseController
{
    private const PATH_JOBS = 'api/factory/jobs';
    private const PATH_RESULT = 'api/factory/result';

    public function jobs(): void
    {
        $this->siteGuard();
        $key = $this->authenticate(self::PATH_JOBS, '');
        $this->respond($key, self::PATH_JOBS, [
            FactoryContract::JOBS_ENVELOPE           => $this->service('factory')->offerJobs(),
            FactoryContract::EXISTING_POSTS_ENVELOPE => $this->service('factory')->existingPosts(),
        ]);
    }

    public function result(): void
    {
        $this->siteGuard();
        $signature = $this->handler('factorySignature');
        $maxBytes = (int) $this->handler('factoryConfig')->get('max_body_bytes', 2000000);
        $body = $signature->readBody($maxBytes);
        if (strlen($body) > $maxBytes) {
            $this->deny(413, 'gövde çok büyük');
        }
        $key = $this->authenticate(self::PATH_RESULT, $body);

        $input = json_decode($body, true);
        if (!is_array($input)) {
            $this->deny(400, 'is_no geçersiz');
        }
        [$status, $reply] = $this->service('factory')->handleResult($input);
        if ($status !== 200) {
            $this->deny($status, (string) ($reply[FactoryContract::REPLY['error']] ?? 'hata'));
        }
        $this->respond($key, self::PATH_RESULT, $reply);
    }

    /** Yalnız bu uçları sunması gereken proje; aksi halde yok say */
    private function siteGuard(): void
    {
        // Site, aktif project_key ile çözülür; ayar dosyası ya da anahtarı olmayan sitede uç yok
        if (!$this->handler('factoryConfig')->exists()) {
            $this->deny(404, 'bulunamadı');
        }
    }

    private function authenticate(string $path, string $body): string
    {
        $signature = $this->handler('factorySignature');
        $key = $signature->signingKey();
        if ($key === null) {
            $this->deny(503, 'yapılandırılmamış');
        }
        if ($signature->verify($key, $path, $body) !== null) {
            $this->deny(401, 'imza geçersiz'); // nedeni dışarı vermeyiz
        }
        return $key;
    }

    /** 2xx cevap da imzalıdır: aynı yol, sitenin kendi zamanı, isteğin nonce'u, kendi gövdesi */
    private function respond(string $key, string $path, array $data): never
    {
        $signature = $this->handler('factorySignature');
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $time = time();
        $this->response->status(200)
            ->contentType('application/json')
            ->header('Cache-Control', 'no-store')
            ->header($signature::HEADER_TIME, (string) $time)
            ->header($signature::HEADER_SIGNATURE, $signature->sign($key, $time, $path, $signature->requestNonce(), $json))
            ->body($json)
            ->send();
        exit;
    }

    private function deny(int $status, string $message): never
    {
        $this->response->status($status)
            ->contentType('application/json')
            ->header('Cache-Control', 'no-store')
            ->body(json_encode([FactoryContract::REPLY['error'] => $message], JSON_UNESCAPED_UNICODE))
            ->send();
        exit;
    }
}
