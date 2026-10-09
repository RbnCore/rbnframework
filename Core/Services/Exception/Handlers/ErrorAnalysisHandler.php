<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Services\Exception\Data\ExceptionData;
use Rbn\Framework\Core\Services\Exception\Data\ShieldMetadata;
use Rbn\Framework\Core\Support\Exceptions\DiagnosticException;
use Rbn\Framework\Core\Support\Exceptions\ValidationException;
use Throwable;

/**
 * ErrorAnalysisHandler - The Master Diagnostic Engine 🧠🩺🛰️
 * 
 * RBN Framework: Standard.
 * This "Brain" organ uses the ShieldMetadata map to classify exceptions 
 * and hydrates the ExceptionData DTO for the orchestrator.
 */
class ErrorAnalysisHandler extends BaseComponent
{
    /**
     * Analyzes the exception and returns a structured internal DTO.
     */
    public function analyze(Throwable $e): ExceptionData
    {
        $classification = $this->classify($e);

        return $this->hydrate([
            'level'          => $classification['level'],
            'design'         => $classification['design'],
            'view'           => $classification['view'],
            'label'          => $classification['label'],
            'class'          => get_class($e),
            'message'        => $e->getMessage(),
            'file'           => $e->getFile(),
            'line'           => $e->getLine(),
            'trace'          => $e->getTraceAsString(),
            'code'           => $e->getCode() ?: 500,
            'type'           => $classification['label'],
            'hint'           => ($e instanceof DiagnosticException) ? $e->getHint() : '',
            'is_database'    => ($e instanceof \PDOException),
            'is_validation'  => ($e instanceof ValidationException),
            'is_diagnostic'  => ($e instanceof DiagnosticException),
            'can_render_rich' => (defined('RBN_DEBUG') && RBN_DEBUG && !($e instanceof ValidationException)),
            'context'        => $this->extractContext($e)
        ]);
    }

    /**
     * Analiz dizisinden `ExceptionData` nesnesini doldurur (eski `ExceptionData::fromArray`).
     *
     * Veri sınıfı yalnız özellik taşır; doldurma mantığı bu işleyicidedir.
     */
    private function hydrate(array $analysis): ExceptionData
    {
        $data = new ExceptionData();

        // Orkestrasyon değerleri (sınıflandırma haritasından gelir)
        $data->level = (string) ($analysis['level'] ?? 'development');
        $data->design = (string) ($analysis['design'] ?? 'development');
        $data->view = (string) ($analysis['view'] ?? 'development');
        $data->label = (string) ($analysis['label'] ?? 'Sistem Hatası');

        // Temel tanılama değerleri
        $data->class = (string) ($analysis['class'] ?? 'Exception');
        $data->message = (string) ($analysis['message'] ?? '');
        $data->file = (string) ($analysis['file'] ?? 'unknown');
        $data->line = (int) ($analysis['line'] ?? 0);
        $data->trace = (string) ($analysis['trace'] ?? '');
        $data->code = (int) ($analysis['code'] ?? 500);
        $data->type = (string) ($analysis['type'] ?? 'Hata');
        $data->hint = (string) ($analysis['hint'] ?? '');
        $data->isDatabase = (bool) ($analysis['is_database'] ?? false);
        $data->isValidation = (bool) ($analysis['is_validation'] ?? false);
        $data->isDiagnostic = (bool) ($analysis['is_diagnostic'] ?? false);
        $data->context = (array) ($analysis['context'] ?? []);
        $data->canRenderRich = (bool) ($analysis['can_render_rich'] ?? false);

        return $data;
    }

    /**
     * Strategist: Classify the Exception using the Master Map 🧠🛡️
     */
    private function classify(Throwable $e): array
    {
        $map = ShieldMetadata::ERROR_MAP;

        // 1. Search for a direct match or inheritance match in the global map 🧬
        foreach ($map as $exceptionClass => $config) {
            if ($exceptionClass !== 'default' && $e instanceof $exceptionClass) {
                return $config;
            }
        }

        // 2. Fallback to default policy 🔇
        return $map['default'];
    }

    /**
     * Extracts specialized metadata based on error type 🔍
     */
    private function extractContext(Throwable $e): array
    {
        $context = [];

        if ($e instanceof ValidationException) {
            $context['validation'] = [
                'errors' => $e->getErrors(),
                'redirect_url' => $e->getRedirectUrl()
            ];
        }

        if ($e instanceof \PDOException) {
            $context['db'] = [
                'sql_state' => $e->getCode(),
                'driver_code' => $e->errorInfo[1] ?? null
            ];
        }

        return $context;
    }
}
