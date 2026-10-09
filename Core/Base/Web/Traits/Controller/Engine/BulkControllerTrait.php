<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Web\Traits\Controller\Engine;

use Exception;

/**
 * BulkControllerTrait - Optimized RBN Framework Bulk Actions ⛓️🎡
 * 
 * RBN Framework: Standard - Centralized Dispatching
 */
trait BulkControllerTrait
{
    /**
     * Standard Bulk Deletion Endpoint & Logic 🗑️
     */
    public function bulkDelete($target = null, string $method = 'bulkDestroy', $redirect = null): void
    {
        $target = $target ?? $this->activeService;
        $redirect = $redirect ?? ($this->sub_module ?? $this->getDetectedEntityName());

        $data = $this->request->form(['ids' => 'required|array']);
        $ids = $data['ids'];

        if (!method_exists($target, $method)) {
            throw new Exception("Bulk target " . get_class($target) . " does not implement {$method}() method.");
        }

        $result = $target->$method($ids);

        // 🎼 RBN Framework: [EXPLICIT DISPATCH] 🗑️🛰️⚓
        $this->handleResult($result, null, $redirect, 'delete');
    }

    /**
     * Standard Bulk Status Toggle Endpoint & Logic 🔄
     */
    public function bulkStatus($target = null, ?bool $status = null, string $method = 'bulkToggleStatus'): void
    {
        $target = $target ?? $this->activeService;
        $data = $this->request->form([
            'ids' => 'required|array',
            'status' => 'nullable'
        ]);
        $ids = $data['ids'];
        // 🛡️ B-48: kural `nullable` olduğu için 'status' anahtarı hiç gelmeyebilir.
        // Ölçüldü: eski okuma "Undefined array key" uyarısı üretiyordu.
        // `??` ile okuma sonucu DEĞİŞTİRİLMİYOR (null zaten false idi).
        $status = $status ?? ((($data['status'] ?? null) == '1') || (($data['status'] ?? false) === true));

        if (!method_exists($target, $method)) {
            throw new Exception("Bulk target " . get_class($target) . " does not implement {$method}() method.");
        }

        $result = $target->$method($ids, $status);

        // 🎼 RBN Framework: [EXPLICIT DISPATCH] 🚥🛰️⚓
        $this->handleResult($result, null, false, 'status');
    }

    /**
     * Standard Bulk Order Update Endpoint & Logic 📊
     */
    public function bulkOrder($target = null, string $method = 'bulkUpdateOrder'): void
    {
        $target = $target ?? $this->activeService;
        $data = $this->request->form(['order' => 'required|array']);
        $order = $data['order']; // Expecting [index => id]

        if (!method_exists($target, $method)) {
            throw new Exception("Bulk target " . get_class($target) . " does not implement {$method}() method.");
        }

        $result = $target->$method($order);

        // 🎼 RBN Framework: [EXPLICIT DISPATCH] 📊🛰️⚓ - Stay in place after sort
        $this->handleResult($result, null, false, 'bulkOrder');
    }

    /**
     * Generic Helper for Custom Bulk Actions 🎻
     */
    public function handleBulkAction($target, string $method, array $extraParams = [], array $messages = []): void
    {
        $data = $this->request->form(['ids' => 'required|array']);
        $ids = $data['ids'];

        if (!method_exists($target, $method)) {
            throw new Exception("Bulk target " . get_class($target) . " does not implement {$method}() method.");
        }

        $success = true;
        $basarisizIdler = [];

        foreach ($ids as $id) {
            $params = array_merge([(int) $id], $extraParams);

            // 🛡️ B-47: dongude `try/catch` YOKTU; ilk `Error` tum toplu islemi
            // durduruyordu ve hangi id'de patladigi GORUNMUYORDU.
            // Burada yalniz HAT KAYDI alinir ve istisna **AYNI SEKILDE**
            // yeniden firlatilir -> kontrol akisi ve "hata sonrasi dur"
            // davranisi BIREBIR korunur (toplu islemde devam etmek
            // yan etkili bir davranis degisimi olurdu).
            try {
                $result = $target->$method(...$params);

                // 🎯 RBN Framework: Fluent Awareness
                $isSuccess = ($result instanceof \Rbn\Framework\Core\Support\Contracts\Service\BaseServiceInterface) ? $result->success() : (bool) $result;

                if (!$isSuccess) {
                    $success = false;
                    $basarisizIdler[] = (int) $id;
                }
            } catch (\Throwable $e) {
                error_log('[RBN] Toplu islem basarisiz: id=' . (int) $id
                    . ' metot=' . $method . ' -> ' . get_class($e) . ': ' . $e->getMessage());
                throw $e;
            }
        }

        // Kismi basarisizlikta hangi id'lerin basarisiz oldugu ARTIK gorunur.
        if ($basarisizIdler !== []) {
            error_log('[RBN] Toplu islem kismi basarisiz: metot=' . $method
                . ' basarisiz id=' . implode(',', $basarisizIdler)
                . ' toplam=' . count($ids));
        }

        $this->handleResult($success, $messages['success_message'] ?? null, $messages['path'] ?? false);
    }

    /**
     * Standard Bulk Value Update Action (Settings, Batch Forms, etc.) 💾
     * Optimized for RBN Framework Architecture.
     */
    public function bulkValueUpdate(): void
    {
        // 🎯 RBN Framework: Automatic input key discovery or override
        $inputKey = (isset($this->bulkInputKey) && !empty($this->bulkInputKey)) ? $this->bulkInputKey : 'data';

        $data = $this->request->form([$inputKey => 'required|array']);
        $payload = $data[$inputKey];

        $result = $this->activeService->bulkValueUpdate($payload);

        // 🎼 RBN Framework: [EXPLICIT DISPATCH] 💾🛰️⚓
        $this->handleResult($result, null, null, 'bulkValueUpdate');
    }
}
