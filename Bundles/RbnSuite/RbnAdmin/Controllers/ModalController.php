<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine;
use Throwable;

/**
 * ModalController - The Traffic Cop for Universal Modals 👮‍♂️🛰️⚓
 * 
 * RBN 3.5: Masterpiece dispatcher for legacy and shared modal components.
 */
class ModalController extends BaseController
{
    /**
     * AJAX Endpoint for modal content
     */
    public function content(): void
    {
        if (!$this->request->isAjax()) {
            $this->response->json(['error' => 'Forbidden Access'], 403);
            return;
        }

        $type = $this->request->input('type');
        $view = $this->request->input('view'); // Direct view name (e.g. add, edit, detail)
        $id = (int) $this->request->input('id', 0);

        // 🎻 RBN 3.5: [SOVEREIGN DISPATCHER] 🛰️🏹⚓
        try {
            // Phase 1: Direct Autonomous Resolution (The Sovereign Way)
            $controllerClass = $this->discover()->controller($type);

            // Phase 2: Heuristic Fallback & Segment Analysis 🛰️🎯
            $segments = str_contains((string)$type, '/') ? explode('/', $type) : (str_contains((string)$type, '_') ? explode('_', $type) : [$type]);

            if (!$controllerClass && count($segments) > 1) {
                // Try resolving via the first segment if the full type fails
                $controllerClass = $this->discover()->controller($segments[0]);
            }

            // 🚀 Phase 3: Delegation Execution
            if ($controllerClass) {
                // 🎼 RBN 3.5: [POLYMORPHIC DELEGATION] 🎻🛰️⚓
                $controller = is_object($controllerClass) ? $controllerClass : (class_exists($controllerClass) ? new $controllerClass() : null);

                if ($controller && is_object($controller)) {
                    // 🎼 RBN 3.5: [SOVEREIGN MODULE ALIGNMENT] 💉🛰️⚓
                    // Controller identity is autonomously handled by SubModule attributes during construction.
                    if (method_exists($controller, 'modal')) {
                        $controller->modal($id, $view);

                        // 🎻 RBN 3.5: [ACTIVE VIEW CAPTURE] 🛰️⚓
                        // If the controller didn't explicitly echo, catch the buffered view.
                        if (method_exists($controller, 'getActiveView')) {
                            $activeView = $controller->getActiveView();
                            if ($activeView) {
                                echo (string) $activeView;
                            }
                        }
                        return;
                    }
                }
            }
        } catch (Throwable $e) {
            $this->renderErrorMessage("Kritik Teşhis Hatası", $e->getMessage(), $e->getFile() . " (Line: " . $e->getLine() . ")");
            return;
        }

        // 🛡️ Phase 4: Failure Grace - Autonomous Error Handling
        echo '<div class="alert alert-warning m-3 border-dashed border-2 bg-light">';
        echo '<div class="d-flex align-items-center">';
        echo '<i class="bi bi-robot fs-2 me-3 text-warning"></i>';
        echo '<div>';
        echo '<h6 class="mb-1 fw-bold text-dark">Otonom Keşif Hatası (RBN 3.5 Discovery)</h6>';
        echo '<span class="small opacity-75"><code>' . htmlspecialchars($type) . '</code> tipi için geçerli bir kontrolcü veya modal bileşeni bulunamadı.</span>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Renders a professional, RBN 3.5 styled error message 🎨🛡️⚓
     */
    private function renderErrorMessage(string $title, string $message, ?string $subText = null): void
    {
        echo '<div class="alert alert-danger m-3 border-danger border-2 shadow-sm rounded-3">';
        echo '  <div class="d-flex align-items-center mb-2">';
        echo '    <i class="bi bi-exclamation-octagon-fill fs-3 me-3 text-danger"></i>';
        echo '    <h6 class="mb-0 fw-bold">' . htmlspecialchars($title) . '</h6>';
        echo '  </div>';
        echo '  <div class="ms-5">';
        echo '    <p class="mb-1 small opacity-75">' . htmlspecialchars($message) . '</p>';
        if ($subText) {
            echo '    <hr class="my-2 opacity-25 text-danger">';
            echo '    <code class="x-small text-danger opacity-75">' . htmlspecialchars($subText) . '</code>';
        }
        echo '  </div>';
        echo '</div>';
    }
}
