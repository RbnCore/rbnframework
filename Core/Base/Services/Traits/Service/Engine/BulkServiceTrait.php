<?php

namespace Rbn\Framework\Core\Base\Services\Traits\Service\Engine;

/**
 * BulkServiceTrait - Service-side bulk operations for RBN
 */
trait BulkServiceTrait
{
    /**
     * Toplu Silme (Bulk Destroy) 🗑️
     */
    public function bulkDestroy(array $ids): self
    {
        if (empty($ids)) {
            $this->lastResult = true;
            return $this;
        }

        // 🎯 RBN Framework: High-Performance Strategic Delegation
        if (isset($this->provider)) {
            $this->lastResult = (bool) $this->provider->destroyBulk($ids);
        } else {
            $model = $this->component('model');
            $this->lastResult = $model ? ($model->deleteBulk($ids) > 0) : false;
        }

        if ($this->lastResult) {
            $this->clearCache();
        }

        return $this;
    }

    /**
     * Toplu Durum Güncelleme (Bulk Toggle Status) 🔄
     */
    public function bulkToggleStatus(array $ids, bool $status, string $field = 'is_active'): self
    {
        if (empty($ids)) {
            $this->lastResult = true;
            return $this;
        }

        // 🎯 RBN Framework: High-Performance Strategic Delegation
        if (isset($this->provider)) {
            $this->lastResult = (bool) $this->provider->toggleStatusBulk($ids, $status, $field);
        } else {
            $model = $this->component('model');
            $this->lastResult = $model ? ($model->toggleStatusBulk($ids, $status, $field) > 0) : false;
        }

        if ($this->lastResult) {
            $this->clearCache();
        }

        return $this;
    }

    /**
     * Toplu Sıralama Güncelleme (Bulk Order Update) 📊
     */
    public function bulkUpdateOrder(array $order, string $field = 'order_num'): self
    {
        $this->lastResult = true;
        $model = $this->component('model');
        foreach ($order as $index => $id) {
            // Order starts from 1
            if (!$model->update($id, [$field => $index + 1])) {
                $this->lastResult = false;
            }
        }

        if ($this->lastResult) {
            $this->clearCache();
        }

        return $this;
    }

    /**
     * Toplu Değer Güncelleme (Bulk Value Update) 💾 
     * Associative array [key => value] veya [id => array] yapısını destekler.
     * High-performance batch value persistence.
     */
    public function bulkValueUpdate(array $data): self
    {
        $this->lastResult = true;

        foreach ($data as $key => $value) {
            // Eğer value bir dizi değilse, dinamik bir field ismiyle paketle
            $valueField = property_exists($this, 'bulkValueField') ? $this->bulkValueField : 'value';
            $payload = is_array($value) ? $value : [$valueField => $value];

            // Eğer key string ise, bunu bir 'setting_key' olarak işaretle (SettingsService uyumu)
            if (is_string($key) && method_exists($this, 'withKey')) {
                $saveResult = $this->withKey($key)->save(null, $payload);
                $status = ($saveResult instanceof self || (is_object($saveResult) && method_exists($saveResult, 'success'))) 
                    ? $saveResult->success() 
                    : (bool)$saveResult;
            }
            // Eğer key numeric ise ve value dizi ise (Standart CRUD uyumu)
            else if (is_numeric($key) && is_array($value)) {
                $status = (bool) $this->save((int) $key, $value);
            } else {
                $status = false;
            }

            if (!$status) {
                $this->lastResult = false;
            }
        }

        if ($this->lastResult) {
            $this->clearCache();
        }

        return $this;
    }
}
