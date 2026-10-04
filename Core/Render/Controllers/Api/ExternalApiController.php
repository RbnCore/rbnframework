<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Controllers\Api;

use Rbn\Framework\Core\Base\Web\BaseController;

/**
 * ExternalApiController - Handles dynamic external API actions securely 🚀
 */
class ExternalApiController extends BaseController
{
    public function index(): void
    {
        $projectKey = project_key();

        // Projeye ait external-api.php yapılandırma dosyasını oku
        $config = $this->resolveProjectConfig('external-api', $projectKey);
        if (!$config) {
            $this->response->error('Bu proje için Dış API yapılandırılmamış.', 403);
        }

        // 4. Eylem (Action) Doğrulaması
        $action = $this->request->input('action');
        if (empty($action)) {
            $this->response->error('Eylem (action) belirtilmedi.', 400);
        }

        // 4.b KAPSAM DENETIMI — `actions` kontrolunden ONCE 🔒
        //
        // [FW-APIGUARD · TASARIM GOREV 2 · 2026-10-03 · zeki-6eb7f5]
        // `ApiGuard` middleware'i kapsami zaten denetler; burada ikinci kez
        // dener (derinlikte savunma: middleware'i atlayan bir yol olursa
        // kapsam yine uygulanir). Anahtar `scopes` listesi BOS ise denetim
        // YAPILMAZ -> geri uyum: mevcut duz metin anahtar tum eylemleri gorur.
        //
        // Kapsam disi istek 403'tur (401 DEGIL): anahtar gecerli, yetkisi yok.
        // Mevcut 404 metni DEGISTIRILMEZ (yenisi 403 icin ayridir).
        if (!\Rbn\Framework\Core\Http\Security\MachineApiKeyStore::assertScope((string) $action)) {
            $this->response->json([
                'success'   => false,
                'message'   => 'Geçersiz veya yetkisiz eylem: ' . $action,
                'code'      => 'FORBIDDEN_SCOPE',
                'status'    => 403,
                'data'      => null,
                'timestamp' => now(),
            ], 403);
        }

        $actions = $config['actions'] ?? [];
        if (!isset($actions[$action])) {
            $this->response->error('Geçersiz veya yetkisiz eylem: ' . $action, 404);
        }

        $schema = $actions[$action];
        $modelName = $schema['model'] ?? null;
        if (empty($modelName)) {
            $this->response->error('Eylem için model yapılandırması eksik.', 500);
        }

        // 5. Dinamik Sorgu Çalıştırma
        try {
            $model = $this->model($modelName);
            if (!$model) {
                $this->response->error('Model yüklenemedi: ' . $modelName, 500);
            }

            $selectFields = $schema['select'] ?? ['id'];
            $selectStr = is_array($selectFields) ? implode(', ', $selectFields) : $selectFields;
            $query = $model->select($selectStr);

            // Filtreleri uygula
            $where = $schema['where'] ?? [];
            foreach ($where as $column => $value) {
                $query->where($column, $value);
            }

            // Sıralama
            if (isset($schema['order'])) {
                $orderParts = explode(' ', trim($schema['order']));
                $col = $orderParts[0];
                $dir = $orderParts[1] ?? 'asc';
                $query->orderBy($col, $dir);
            }

            // Limit
            $limit = (int) ($schema['limit'] ?? 10);
            $query->limit($limit);

            $data = $query->get() ?: [];

            // 6. Veri Formatlama / Mutator Desteği
            $formattedData = [];
            foreach ($data as $row) {
                // Array formatına çevir
                $rowArray = is_object($row) ? (method_exists($row, 'toArray') ? $row->toArray() : (array) $row) : $row;
                
                // Modelde formatApiRow varsa mutasyona sok
                if (method_exists($model, 'formatApiRow')) {
                    $rowArray = $model->formatApiRow($rowArray);
                }
                
                $formattedData[] = $rowArray;
            }

            $this->response->success($formattedData, 'Veriler başarıyla getirildi.');

        } catch (\Throwable $e) {
            $this->response->error('Veri çekme işlemi başarısız: ' . $e->getMessage(), 500);
        }
    }
}
