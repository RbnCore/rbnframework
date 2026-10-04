<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFile\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * FileImportHandler - Uzak Dosya ve URL İçe Aktarım İşleyicisi (Worker) 📥🛰️
 * 
 * RBN 3.5: Masterpiece Standard (BaseComponent Actor).
 * Location: Packages\RbnFile\Handlers\
 */
class FileImportHandler extends BaseComponent
{
    /**
     * Dış URL'deki bir dosyayı sunucunun geçici dizinine indirir ve dosya yolunu döner.
     *
     * @param string $url İndirilecek dış dosya URL'si
     * @return string|bool İndirilen geçici dosya yolu veya hata durumunda false
     */
    public function fromUrl(string $url): string|bool
    {
        try {
            // 1. Birinci Tercih: RBN 3.5 RemoteRequest (SSL bypass ile)
            $result = $this->remote->get($url, [], [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) RBN-File-Import/1.0'
            ], [
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]
            ]);

            $rawData = null;
            if (($result['status'] ?? 'error') === 'success' && !empty($result['raw'])) {
                $rawData = $result['raw'];
            } else {
                // 2. İkinci Tercih: PHP Native file_get_contents (Fallback)
                $context = stream_context_create([
                    'http' => [
                        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) RBN-File-Import/1.0\r\n",
                        'follow_location' => 1,
                        'timeout' => 15
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ]);
                $rawData = @file_get_contents($url, false, $context);
            }

            if (empty($rawData)) {
                return false;
            }

            // Uzantıyı çözümle veya webp fallback yap
            $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH) ?? '');
            $ext = !empty($pathInfo['extension']) ? $pathInfo['extension'] : 'webp';

            $tempDir = defined('TEMP_DIR') ? TEMP_DIR : sys_get_temp_dir();
            $tempPath = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'rbn_import_' . uniqid() . '.' . $ext;

            if (file_put_contents($tempPath, $rawData) !== false) {
                return $tempPath;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
