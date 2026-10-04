<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Web\Traits\Controller;

use Rbn\Framework\Core\Base\Web\Traits\Controller\Engine\CrudControllerTrait;
use Rbn\Framework\Core\Base\Web\Traits\Controller\Engine\BulkControllerTrait;
use Rbn\Framework\Core\Base\Web\Traits\Controller\Engine\DownloadControllerTrait;

/**
 * ActionControllerTrait - Controller Orchestrator Hub 🛰️🎻
 * 
 * RBN 3.5: Strategic Hub that composes all controller-layer engine traits.
 * This is the primary entry point for CRUD, Bulk, and Download actions.
 * 
 * Symmetry: Enforces layer-specific naming standards (ControllerTrait).
 */
trait ActionControllerTrait
{
    use CrudControllerTrait,
        BulkControllerTrait,
        DownloadControllerTrait;

    /* ==========================================================================
       [ SOVEREIGN PROPERTIES ] - Layer Specific Overrides 🪟
       ========================================================================== */

    /** @var string|null Explicit modal view path override 🪟 */
    protected ?string $modalView = null;

    /* ==========================================================================
       [ ORCHESTRATION HELPERS ] - Core Utilities for Traits 🛠️
       ========================================================================== */

    /**
     * Detect entity name from class name
     * Example: SuppliersController -> supplier
     */
    protected function getDetectedEntityName(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        $name = strtolower(str_replace('Controller', '', $className));

        if (str_ends_with($name, 's')) {
            $name = substr($name, 0, -1);
        }

        return $name;
    }

    /**
     * Get Custom Message or Default
     */
    protected function getCrudMessage(string $key, string $default): string
    {
        if (property_exists($this, 'crudMessages') && isset($this->crudMessages[$key])) {
            return $this->crudMessages[$key];
        }
        return $default;
    }

    /**
     * Get Sovereign Resolved Return Path 🛰️⚓
     * RBN 3.5: Multi-role autonomous discovery from URL.
     */
    protected function returnPath(mixed $subPath = null)
    {
        // 🎯 1. [NAMED ROUTE DISCOVERY] Eğer 'ofis.personel' gibi isimlendirilmiş bir rota (alias) verildiyse:
        if (is_string($subPath) && !empty($subPath)) {
            $namedUrl = $this->Route->url($subPath);
            if (!empty($namedUrl) && $namedUrl !== '/' && $namedUrl !== $subPath) {
                return $namedUrl;
            }
        }

        // 🎻 2. [ABSOLUTE OVERRIDE] Eğer doğrudan '/user/personel' gibi tam bir path verildiyse doğrudan döndür:
        if (is_string($subPath) && str_starts_with($subPath, '/')) {
            return $subPath;
        }


        // 🎼 RBN 3.5: [SOVEREIGN HIERARCHY DISCOVERY] 🏹🛰️⚓
        // Priority: Explicit $subPath > Detected $sub_module > Null
        $subPath = $subPath ?? ($this->sub_module ?? null);

        // Priority: Explicit $path property > Implicit $module root
        $base = (property_exists($this, 'path') && !empty($this->path)) ? $this->path : ($this->module ?? null);

        if ($base && $subPath !== false) {
            $path = $subPath ? rtrim((string) $base, '/') . '/' . ltrim($subPath, '/') : $base;


            // 🛡️ RBN 3.5 Masterpiece: Case-Insensitivity Armor 🛰️⚓
            $path = is_string($path) ? strtolower($path) : $path;

            // 📡 [RBN 3.5] Sovereign URL Discovery Hub
            // Detect the active role/panel prefix (e.g. admin or developer) directly from the URI.
            // 🛡️ B-43: ham `$_SERVER['REQUEST_URI']` yerine istek nesnesi (Anayasa §7).
            // `Request::path()` sorgu dizesini zaten atar; CLI'de `$_SERVER` yoksa
            // eski kod uyarI üretiyordu, yeni kod "/" varsayılanına düşüyor.
            $istekYolu = $this->request?->path();
            $uri = trim((string) parse_url(is_string($istekYolu) ? $istekYolu : '/', PHP_URL_PATH), '/');
            $segments = $uri === '' ? [] : explode('/', $uri);

            $prefix = $this->service('route')->getBasePrefix(); // e.g. 'rbn'
            $pIdx = array_search($prefix, $segments);

            // Capture the 'role' part immediately following the prefix
            $detectedRole = ($pIdx !== false && isset($segments[$pIdx + 1])) ? $segments[$pIdx + 1] : null;

            // 🎻 RBN 3.5: Sovereign Panel Overwrite
            $finalRole = $this->panel ?? ($detectedRole ?? 'admin');

            $url = $this->Route->url($path, $finalRole);
            // 🛡️ B-44: `active_project_key()` global yardımcısı CLI'da/erken boot'ta
            // TANIMLI DEĞİL; satır 116'daki `project_data()` ile tutarsız biçimde
            // guard'sız çağrılıyordu ve `Error` fırlatıyordu. Guard eklendi.
            // NOT: `$projectKey` şu an bu blokta KULLANILMIYOR (hesaplanan URL
            // `$url` üzerinden döner); yine de kaldırılmadı — çağıranların
            // beklediği sözleşme bozulmasın diye yalnızca güvenli hale getirildi.
            $projectKey = function_exists('active_project_key') ? active_project_key() : null;
            $cleanUrl = (string) parse_url($url, PHP_URL_PATH);

            // 🎼 Dynamic Admin Prefix Detection (No Hardcoding) 🛡️
            $dashPrefix = function_exists('project_data') ? project_data('dashboard_prefix') : null;
            $dashPrefix = !empty($dashPrefix) ? trim((string) $dashPrefix, '/') : null;
            $basePrefix = $this->service('route') ? trim((string) $this->service('route')->getBasePrefix(), '/') : null;

            $hasDashboardPrefix = false;

            if ($dashPrefix && $dashPrefix !== 'user') {
                $hasDashboardPrefix = str_starts_with($cleanUrl, '/' . $dashPrefix);
            }

            if (!$hasDashboardPrefix && $basePrefix && $basePrefix !== 'user') {
                $hasDashboardPrefix = str_starts_with($cleanUrl, '/' . $basePrefix);
            }

            return $url;
        }

        // Eğer $subPath doğrudan '/user/personel' gibi mutlak bir string olarak verildiyse onu dön
        if (is_string($subPath) && !empty($subPath)) {
            return $subPath;
        }

        return false;
    }


    /**
     * Sovereign Result Dispatcher 🏹🛰️⚓
     * Professional Message Architect & Autonomous Path Resolver.
     */
    protected function handleResult($result, ?string $message = null, mixed $path = null, mixed $context = null, array $data = [])
    {
        // 🕵️‍♂️ RBN 3.5: [HEURISTIC ACTION DISCOVERY] 🧠🛰️⚓
        $caller = $context ?? (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'update');

        // Map common functional intents to professional verbs
        $action = 'update';
        if (is_string($caller)) {
            if (preg_match('/store/i', $caller))
                $action = 'store';
            elseif (preg_match('/(create|add|new|insert)/i', $caller))
                $action = 'create';
            elseif (preg_match('/destroy/i', $caller))
                $action = 'destroy';
            elseif (preg_match('/(delete|remove|clear)/i', $caller))
                $action = 'delete';
            elseif (preg_match('/(status|toggle|active|passive)/i', $caller))
                $action = 'status';
            elseif (preg_match('/(order|sort|rank)/i', $caller))
                $action = 'bulkOrder';
            elseif (preg_match('/(bulkUpdate|bulkSet|bulkValue)/i', $caller))
                $action = 'bulkValueUpdate';
        } else {
            $action = $caller; // Explicit context passed as string
        }

        // 🎼 Verb Mapping (Professional Masterpiece Standards)
        $verbs = [
            'create' => ['success' => 'başarıyla eklendi. ✅', 'error' => 'eklenirken bir hata oluştu! ❌'],
            'store' => ['success' => 'başarıyla kaydedildi. ✅', 'error' => 'kaydedilirken bir hata oluştu! ❌'],
            'update' => ['success' => 'başarıyla güncellendi. ✨', 'error' => 'güncellenirken bir hata oluştu! ❌'],
            'status' => ['success' => 'durumu güncellendi. 🚥', 'error' => 'durumu değiştirilirken bir hata oluştu! ❌'],
            'delete' => ['success' => 'başarıyla silindi. 🗑️', 'error' => 'silinirken bir hata oluştu! ❌'],
            'destroy' => ['success' => 'sistemden kaldırıldı. 🗑️', 'error' => 'kaldırılırken bir hata oluştu! ❌'],
            'bulkValueUpdate' => ['success' => 'başarıyla güncellendi. ✨', 'error' => 'güncellenirken bir hata oluştu! ❌'],
            'bulkOrder' => ['success' => 'sıralaması güncellendi. ↕️', 'error' => 'sıralanırken bir hata oluştu! ❌'],
        ];

        $contextMap = $verbs[$action] ?? $verbs['update'];

        // 🎼 RBN 3.5: [MESSAGE ARCHITECT] Sentezleyici
        if (!empty($message)) {
            $trimmed = trim($message);
            $hasPunctuation = preg_match('/[.!?]$/u', $trimmed);

            if ($hasPunctuation) {
                $success = $trimmed . ' ✨';
                $error = $trimmed . ' sırasında bir hata oluştu! ❌';
            } else {
                $success = $trimmed . ' ' . $contextMap['success'];
                $error = $trimmed . ' ' . $contextMap['error'];
            }
        } else {
            // 🎼 RBN 3.5: [SOVEREIGN FALLBACK]
            $entityName = (isset($this->entityName) && !empty($this->entityName)) ? ucfirst($this->entityName) : ucfirst($this->getDetectedEntityName());
            $success = $this->getCrudMessage('success', "{$entityName} işlemi {$contextMap['success']}");
            $error = $this->getCrudMessage('error', "{$entityName} {$contextMap['error']}");
        }

        // 🎼 RBN 3.5: Override error message if service returned specific error messages 🚨
        if (is_array($result) && isset($result['success']) && !$result['success']) {
            if (!empty($result['errors'])) {
                $error = implode('; ', (array)$result['errors']);
            } elseif (!empty($result['message'])) {
                $error = $result['message'];
            }
        }

        return $this->Route->handleResult($result, [
            'success_message' => $success,
            'error_message' => $error,
            'path' => $this->returnPath($path),
            'data' => $data
        ]);
    }

    /**
     * Alert Proxy for Masterpiece Controllers 🔔🛰️
     * Simplified access to the RouteHandle alert system.
     */
    protected function alert(string $type, string $message, string|array $redirectOrData = []): void
    {
        if (is_string($redirectOrData)) {
            $this->Route->alert($type, $message, $redirectOrData);
        } else {
            $this->Route->alert($type, $message, null, null, $redirectOrData);
        }
    }

    /**
     * Standard Generic Modal Renderer (Sovereign Architecture) 🪟🛰️⚓
     * 
     * RBN 3.5: Masterpiece autonomous discovery logic.
     * Priority: Hook (getModalData) > ID Lookup (Provider/Service) > Empty Array.
     */
    public function modal($id = null, ?string $view = null): void
    {
        $id = $id ?: $this->request->input('id');
        $id = $id ? (int) $id : null;
        $service = $this->activeService ?? null;
        $entityName = (isset($this->entityName) && !empty($this->entityName)) ? $this->entityName : $this->getDetectedEntityName();

        // 🎻 1. Data Resolution Strategy
        $record = null;
        if (method_exists($this, 'getModalData')) {
            $record = $this->getModalData($id);
        } elseif ($id > 0 && $service) {
            // Priority: Provider (Array) > Service (Object)
            if (isset($service->provider) && method_exists($service->provider, 'find')) {
                $record = $service->provider->find($id);
            } elseif (method_exists($service, 'find')) {
                $record = $service->find($id);
            }
        }

        // 🛡️ Fail-safe: Always deliver array-compatible object/array for RBN 3.5 Views
        $record = $record ?: [];

        // 🏹 2. Autonomous View Path Discovery
        // Uses the sovereign identity (sub_module) extracted during DNA awakening. 🧬⚓
        $folderName = $this->sub_module ?? $this->getDetectedEntityName();
        $viewFile = $view ?: 'modal';

        // 🎼 RBN 3.5: [SOVEREIGN VIEW ARCHITECT] 🛰️⚓
        // Priority: Explicit modalView property > PascalCase Folder Discovery
        $viewPath = $this->modalView ?? ucfirst((string) $folderName) . "/Partials/{$viewFile}";

        // 📽️ 3. Execution (Sovereign Ajax Dispatch) ✂️🛰️⚓
        // RBN 3.5: [DUAL-BINDING & AUTONOMOUS AJAX] 🎻🛰️⚓
        $viewData = [
            $entityName => $record,
            'id' => $id,
            'entity' => $entityName,
            'isEdit' => ($id > 0),
            'view' => $viewFile,
            'ajax' => true // 🎯 Trigger Autonomous AjaxProvider detection!
        ];

        if (is_array($record)) {
            $viewData = array_merge($viewData, $record);
        }

        $this->render($viewPath, $viewData);
    }

    /* ==========================================================================
       [ API & MOBILE JSON RESPONSES ] - Sovereign JSON Contract Hub 🌐📱⚓
       ========================================================================== */

    /**
     * Standard Success JSON Response for Web & Mobile APIs 🚀
     */
    protected function apiSuccess(mixed $data = [], string $message = 'İşlem başarılı', int $code = 200, array $meta = []): void
    {
        $payload = [
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => time(),
                'version' => 'v1',
                'project' => function_exists('project_key') ? project_key() : null
            ], $meta)
        ];

        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Standard Error JSON Response for Web & Mobile APIs 🚨
     */
    protected function apiError(string $message = 'İşlem sırasında bir hata oluştu', int $code = 400, mixed $errors = null, array $meta = []): void
    {
        $payload = [
            'success' => false,
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
            'meta' => array_merge([
                'timestamp' => time(),
                'version' => 'v1',
                'project' => function_exists('project_key') ? project_key() : null
            ], $meta)
        ];

        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
