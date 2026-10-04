<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Data;

/**
 * ExceptionData - The Pure Error DTO (Data Transfer Object) 🏺
 * 
 * RBN 3.5: Masterpiece Standard.
 * Standardizes how error information is passed between Handlers and Providers.
 * No constants, no hidden logic—just raw diagnostic data.
 */
class ExceptionData
{
    // --- Tactical Orchestration Data 🎻 ---
    /** @var string (pre_flight, fatal, development, user) */
    public string $level;
    /** @var string (pre_flight, fatal, development, user) */
    public string $design;
    /** @var string (pre_flight, internal, development, etc.) */
    public string $view;
    public string $label = 'Sistem Hatası';

    // --- Core Diagnostic Data 🩺 ---
    public string $class;
    public string $message;
    public string $file;
    public int $line;
    public string $trace;
    public int $code = 500;
    public string $type = 'Hata';
    public string $hint = '';
    public bool $isDatabase = false;
    public bool $isValidation = false;
    public bool $isDiagnostic = false;
    public array $context = [];
    public bool $canRenderRich = false;

    public const DEBUG_MODE = true;
}
