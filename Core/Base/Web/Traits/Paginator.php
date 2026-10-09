<?php

namespace Rbn\Framework\Core\Base\Web\Traits;

/**
 * Paginator - Fluent, Context-Aware Pagination 🎻🎹
 * 
 * Automatically resolves current page and self-injects variables into the View Context.
 */
class Paginator
{
    private array $items;
    private $controller;
    private int $total;
    private int $perPage = 15;
    private int $currentPage = 1;
    private int $lastPage = 1;
    private ?string $baseUrl = null;
    private bool $executed = false;
    private bool $preSliced = false;

    /** Ek sayfa parametrelerinin okunacagi kaynak (kolaylik icin). */
    private const PAGE_PARAM = 'page';

    /**
     * @param array|null $items The raw list to paginate
     * @param mixed $controller Controller reference for Context & Request
     */
    public function __construct(?array $items, $controller = null)
    {
        $this->items = $items ?? [];
        $this->controller = $controller;

        // 🎯 RBN Framework: Auto-Detection for API/Wrapped Results
        if (isset($this->items['results']) && is_array($this->items['results'])) {
            $this->total = (int) ($this->items['total_results'] ?? $this->items['total'] ?? count($this->items['results']));
            $this->items = $this->items['results'];
            $this->preSliced = true;
        } else {
            // 🎯 Standard Array Logic
            $this->items = $this->applySearchFilter($this->items);
            $this->total = count($this->items);
        }
    }

    /**
     * Static Factory for Fluent Creation 🪐
     * RBN Framework: Supports both auto-slicing and manual (pre-sliced) data.
     */
    public static function make(?array $items = null, ?int $total = null, int $perPage = 15, ?string $url = null): self
    {
        $instance = new self($items);
        $instance->perPage($perPage);

        if ($total !== null) {
            $instance->total = $total;
            // 🛡️ B-40: `executed = true` idi. `execute()` hemen geri döndüğü
            // için `currentPage`/`lastPage` HİÇ hesaplanmıyor, `hasPages()`
            // hep false ve `links()` hep boş string dönüyordu (ölçüldü:
            // lastPage=1, linksUzunluk=0).
            // Doğrusu: veri DB'de `limit` ile ZATEN dilimlenmiş geldiği için
            // `preSliced = true` -> `execute()` çalışır (sayfa keşfi + link),
            // ama öğeler TEKRAR dilimlenmez (aksi halde veri kaybı).
            $instance->preSliced = true;
        }

        if ($url !== null) {
            $instance->baseUrl($url);
        }

        return $instance;
    }

    // ====================================================================
    // FLUENT CONFIGURATION ⛓️
    // ====================================================================

    public function perPage(int $n): self
    {
        $this->perPage = max(1, $n);
        return $this;
    }

    public function baseUrl(string $url): self
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * SELF-INJECTION MAGIC 🪄
     * Registers the items and pager into the controller's view data context.
     */
    public function to(string $key): self
    {
        $this->execute();

        if ($this->controller && method_exists($this->controller, 'set')) {
            $this->controller->set($key, $this->items());
            $this->controller->set($key . '_pager', $this);
        }

        return $this;
    }

    // ====================================================================
    // CORE LOGIC 🧠
    // ====================================================================

    /**
     * Executes the slicing and page discovery.
     */
    private function execute(): void
    {
        if ($this->executed)
            return;

        // 🎯 RBN Framework: Safe Request Discovery
        // B-39: Ham `$_GET` yerine guvenli kaynak (request nesnesi).
        $request = $this->resolveRequest();

        if ($request !== null) {
            $this->currentPage = (int) $request->input(self::PAGE_PARAM, 1);
        } else {
            $this->currentPage = 1;
        }

        $this->lastPage = (int) ceil($this->total / $this->perPage);
        $this->currentPage = max(1, min($this->currentPage, $this->lastPage > 0 ? $this->lastPage : 1));

        if (!$this->preSliced) {
            $offset = ($this->currentPage - 1) * $this->perPage;
            $this->items = array_slice($this->items, $offset, $this->perPage);
        }

        $this->executed = true;
    }

    /**
     * B-39: request nesnesini guvenle cozumle.
     *
     * Sira: controller -> hub. `BaseComponent` `__isset` tanimlamadigi icin
     * `isset($ctrl->request)` her zaman false donuyordu; bu yuzden once dogrudan
     * erisim denenir (magic `__get` cozumleyebiliyorsa calisir).
     */
    private function resolveRequest(): ?object
    {
        if ($this->controller !== null) {
            try {
                $req = $this->controller->request;
                if (is_object($req) && method_exists($req, 'input')) {
                    return $req;
                }
            } catch (\Throwable $e) {
                error_log('[RBN] Paginator: controller request okunamadi: ' . $e->getMessage());
            }
        }

        try {
            $req = \Rbn\Framework\Core\Base\Services\BaseService::get()?->request;
            if (is_object($req) && method_exists($req, 'input')) {
                return $req;
            }
        } catch (\Throwable $e) {
            error_log('[RBN] Paginator: hub request okunamadi: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * B-39: Mevcut istek yolunu dondurur (sorgu dizesi olmadan).
     * CLI'de `$_SERVER['REQUEST_URI']` yok -> bos taban adina dusulur.
     */
    private function currentPath(): string
    {
        $req = $this->resolveRequest();
        if ($req !== null && method_exists($req, 'path')) {
            $yol = $req->path();
            if (is_string($yol) && $yol !== '') {
                return $yol;
            }
        }
        return '';
    }

    /**
     * B-39: Sorgu parametrelerinin TAMAMINI request'ten oku (dizye zorla).
     */
    private function queryParams(): array
    {
        $req = $this->resolveRequest();
        if ($req !== null && method_exists($req, 'query')) {
            $q = $req->query();
            if (is_array($q)) {
                return array_filter($q, 'is_scalar');
            }
        }
        return [];
    }

    /**
     * B-39: HTML kacisi (href/HTML uretimi icin).
     */
    private static function e(?string $deger): string
    {
        return htmlspecialchars((string) $deger, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * B-39: Temel adresi taban + sorgu olarak ayir.
     *
     * @return array{0:string,1:array} [taban yol, sorgu parametreleri]
     */
    private function splitBaseUrl(): array
    {
        $base = $this->baseUrl ?? $this->currentPath();
        $base = (string) $base;

        $params = [];
        $soruYeri = strpos($base, '?');
        if ($soruYeri !== false) {
            $mevcut = substr($base, $soruYeri + 1);
            $base = substr($base, 0, $soruYeri);
            parse_str($mevcut, $params);
        }

        // Istek parametreleri taban adresdeki sorguyu EZMEZ, tamamlar.
        $params = array_merge($params, $this->queryParams());
        unset($params[self::PAGE_PARAM]);

        return [$base, $params];
    }

    /**
     * B-39: Tek bir sayfa URL'i uretir (page parametresi tam olarak bir kez).
     */
    private function pageUrl(int $page): string
    {
        [$base, $params] = $this->splitBaseUrl();
        if ($page > 1) {
            $params[self::PAGE_PARAM] = $page;
        }
        $query = http_build_query($params);
        return $base . ($query !== '' ? '?' . $query : '');
    }
    private function applySearchFilter(array $items): array
    {
        // B-39: `$_GET['search']` yerine request uzerinden guvenli okuma.
        $req = $this->resolveRequest();
        $hamSearch = null;
        if ($req !== null && method_exists($req, 'query')) {
            $hamSearch = $req->query('search');
        } elseif ($req !== null && method_exists($req, 'input')) {
            $hamSearch = $req->input('search');
        }

        $searchParam = (is_string($hamSearch) && trim($hamSearch) !== '')
            ? strtolower(trim($hamSearch))
            : null;
        if (!$searchParam || empty($items))
            return $items;

        return array_values(array_filter($items, function ($item) use ($searchParam) {
            $flatValues = [];
            if (is_array($item)) {
                array_walk_recursive($item, function ($v) use (&$flatValues) {
                    $flatValues[] = $v; });
            } elseif (is_object($item)) {
                $arr = json_decode(json_encode($item), true);
                if (is_array($arr)) {
                    array_walk_recursive($arr, function ($v) use (&$flatValues) {
                        $flatValues[] = $v; });
                }
            } elseif (is_scalar($item)) {
                $flatValues[] = $item;
            }

            foreach ($flatValues as $val) {
                if (is_scalar($val) && str_contains(strtolower((string) $val), $searchParam)) {
                    return true;
                }
            }
            return false;
        }));
    }

    // ====================================================================
    // ACCESSORS 🚪
    // ====================================================================

    public function items(): array
    {
        $this->execute();
        return $this->items;
    }
    public function total(): int
    {
        return $this->total;
    }
    public function currentPage(): int
    {
        // B-39: Sayfa kesfi `execute()` icinde yapilir; eristik erisimde deger guncel olsun.
        $this->execute();
        return $this->currentPage;
    }
    public function hasPages(): bool
    {
        $this->execute();
        return $this->lastPage > 1;
    }
    public function lastPage(): int
    {
        $this->execute();
        return $this->lastPage;
    }

    public function onFirstPage(): bool
    {
        return $this->currentPage() <= 1;
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage() < $this->lastPage();
    }

    public function previousPageUrl(): string
    {
        $this->execute();
        // B-39: ham $_GET / $_SERVER yerine taban + istek parametreleri.
        return $this->pageUrl(max(1, $this->currentPage() - 1));
    }

    public function nextPageUrl(): string
    {
        $this->execute();
        // B-39: ham $_GET / $_SERVER yerine taban + istek parametreleri.
        return $this->pageUrl(min($this->lastPage(), $this->currentPage() + 1));
    }

    /**
     * Bootstrap HTML Output
     */
    public function links(): string
    {
        $this->execute();

        // B-39: ham $_SERVER / $_GET yerine taban adres + istek parametreleri.
        [$baseUrl, $params] = $this->splitBaseUrl();
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null)
                unset($params[$k]);
        }

        $qs = http_build_query($params);
        $fullBaseUrl = !empty($qs) ? $baseUrl . '?' . $qs : $baseUrl;
        $pageParam = (strpos($fullBaseUrl, '?') !== false) ? '&page=' : '?page=';

        if (!$this->hasPages())
            return '';

        $html = '<nav aria-label="Sayfalar"><ul class="pagination rbn-pagination m-0">';

        // Prev
        $prevUrl = ($this->currentPage > 1) ? $fullBaseUrl . $pageParam . ($this->currentPage - 1) : '#';
        $prevDisabled = ($this->currentPage <= 1);
        $html .= '<li class="page-item rbn-page-item' . ($prevDisabled ? ' disabled' : '') . '">
            <a class="page-link rbn-page-link" href="' . self::e($prevUrl) . '" aria-label="Önceki Sayfa"' . (!$prevDisabled ? ' data-tooltip="Önceki Sayfa"' : '') . '>‹</a>
        </li>';

        // Numbers
        $start = max(1, $this->currentPage - 2);
        $end = min($this->lastPage, $this->currentPage + 2);

        if ($start > 1) {
            $isOneActive = ($this->currentPage == 1);
            $html .= '<li class="page-item rbn-page-item' . ($isOneActive ? ' active' : '') . '"><a class="page-link rbn-page-link' . ($isOneActive ? ' active' : '') . '" href="' . self::e($fullBaseUrl . $pageParam . '1') . '" data-tooltip="1. Sayfa">1</a></li>';
            if ($start > 2)
                $html .= '<li class="page-item rbn-page-item disabled"><span class="page-link rbn-page-link">…</span></li>';
        }

        for ($i = $start; $i <= $end; $i++) {
            $isActive = ($i == $this->currentPage);
            $activeClass = $isActive ? ' active' : '';
            $tooltipText = $isActive ? ($i . '. Sayfa (Aktif)') : ($i . '. Sayfa');
            $html .= '<li class="page-item rbn-page-item' . $activeClass . '">
                <a class="page-link rbn-page-link' . $activeClass . '" href="' . self::e($fullBaseUrl . $pageParam . $i) . '" data-tooltip="' . $tooltipText . '">' . $i . '</a>
            </li>';
        }

        if ($end < $this->lastPage) {
            if ($end < $this->lastPage - 1)
                $html .= '<li class="page-item rbn-page-item disabled"><span class="page-link rbn-page-link">…</span></li>';
            $html .= '<li class="page-item rbn-page-item"><a class="page-link rbn-page-link" href="' . self::e($fullBaseUrl . $pageParam . $this->lastPage) . '" data-tooltip="' . $this->lastPage . '. Sayfa">' . $this->lastPage . '</a></li>';
        }

        // Next
        $nextUrl = ($this->currentPage < $this->lastPage) ? $fullBaseUrl . $pageParam . ($this->currentPage + 1) : '#';
        $nextDisabled = ($this->currentPage >= $this->lastPage);
        $html .= '<li class="page-item rbn-page-item' . ($nextDisabled ? ' disabled' : '') . '">
            <a class="page-link rbn-page-link" href="' . self::e($nextUrl) . '" aria-label="Sonraki Sayfa"' . (!$nextDisabled ? ' data-tooltip="Sonraki Sayfa"' : '') . '>›</a>
        </li>';

        $html .= '</ul></nav>';
        return $html;
    }
}
