<?php

namespace Rbn\Framework\Core\Base\Patterns;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Base\Web\Traits\Paginator;
use Rbn\Framework\Core\Database\Engine\Collection;


/**
 * BaseChannel - Shared logic for all communication channels 🕊️🏛️⚓
 * RBN Framework Standard.
 * 
 * Akıcı (Fluent) sorgu arayüzleri için temel iskeleti sağlar.
 * BaseComponent DNA'sı sayesinde cache ve context erişimine sahiptir.
 */
abstract class BaseChannel extends BaseComponent
{
    /** --- State Management (RBN Framework Engine) 🪐 --- */
    protected $targetModel = null;
    protected string $status = 'unread';
    protected int $limit = 50;
    protected int $offset = 0;
    protected array $filters = [];
    protected array $orders = [];
    protected ?string $searchTerm = null;
    protected ?int $cacheTtl = null; // null: use default ('short')
    protected string $hydrationMode = 'array'; // array, object, collection

    /** @var string Primary cache prefix for the channel */
    protected string $cachePrefix;

    /**
     * Constructor: Initializes the channel with a cache prefix.
     */
    public function __construct(string $cachePrefix)
    {
        parent::__construct();
        $this->cachePrefix = $cachePrefix;
    }

    /* ==========================================================================
       [ STANDARD FILTERS ] 🧪🕊️
       ========================================================================== */

    /**
     * Set the status to filter (unread, read, all, etc.)
     */
    public function status(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Shortcut for unread status
     */
    public function unread(): self
    {
        return $this->status('unread');
    }

    /**
     * Order by newest first
     */
    public function latest(string $column = 'id'): self
    {
        $this->orders[$column] = 'DESC';
        return $this;
    }

    /**
     * Order by oldest first
     */
    public function oldest(string $column = 'id'): self
    {
        $this->orders[$column] = 'ASC';
        return $this;
    }

    /**
     * Filter by active status (standard field: status=1)
     */
    public function active(): self
    {
        return $this->where('status', 1);
    }

    /**
     * Set limit for results
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set offset for results
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /* ==========================================================================
       [ SEARCH & FILTER ENGINE ] 🔍🏗️
       ========================================================================== */

    /**
     * Unified where filter
     */
    public function where(string $column, $value, string $operator = '='): self
    {
        $this->filters[] = [
            'column'   => $column,
            'value'    => $value,
            'operator' => $operator
        ];
        return $this;
    }

    /**
     * Global search term
     */
    public function search(string $term): self
    {
        $this->searchTerm = $term;
        return $this;
    }

    /* ==========================================================================
       [ HYDRATION & CACHE CONTROL ] 🧩🎭
       ========================================================================== */

    /**
     * Set custom cache TTL (seconds)
     * Pass 0 or null to use default/disable depending on storage behavior.
     */
    public function cache(?int $ttl = null): self
    {
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * Return results as raw array
     */
    public function asArray(): self
    {
        $this->hydrationMode = 'array';
        return $this;
    }

    /**
     * Return results as objects
     */
    public function asObject(): self
    {
        $this->hydrationMode = 'object';
        return $this;
    }

    /**
     * Return results as a framework Collection
     */
    public function asCollection(): self
    {
        $this->hydrationMode = 'collection';
        return $this;
    }

    /**
     * [HYDRATOR] Synchronize data with the selected mode 🎭
     * RBN Framework: level flexibility.
     */
    protected function hydrate(mixed $data): mixed
    {
        $isArray = is_array($data);
        $isCollection = ($data instanceof Collection);

        if ($this->hydrationMode === 'collection') {
            return $isCollection ? $data : collect($isArray ? $data : (array) $data);
        }

        if ($this->hydrationMode === 'array') {
            return $isArray ? $data : ($isCollection ? $data->toArray() : (array) $data);
        }

        if ($this->hydrationMode === 'object') {
            return json_decode(json_encode($data), false);
        }

        return $data;
    }

    /* ==========================================================================
       [ PAGINATION BRIDGE ] 🛫📋
       ========================================================================== */

    /**
     * Bridge to the Framework Paginator 🌉
     */
    public function paginate(int $perPage, ?string $url = null): object
    {
        $this->limit($perPage);
        $items = $this->get();
        $total = $this->count();

        // RBN Framework Paginator Bridge 🌉
        return Paginator::make($items, $total, $perPage, $url);
    }

    /**
     * Get records (Implemented by child)
     */
    abstract public function get(): mixed;

    /**
     * Count records (Implemented by child)
     */
    abstract public function count(): int;

    /**
     * Get stats (Implemented by child)
     */
    abstract public function stats(): array;

    /** @var string The key used for data items in the view package */
    protected string $dataKey = 'items';

    /**
     * Package data for view rendering.
     * Returns a standardized array with items and count.
     */
    public function toPackage(): array
    {
        $items = $this->get();
        return [
            $this->dataKey => $items,
            'count' => is_countable($items) ? count($items) : 0,
            'stats' => $this->stats(),
            'meta'  => [
                'status' => $this->status,
                'limit'  => $this->limit,
                'hydration' => $this->hydrationMode
            ]
        ];
    }

    protected function getFromCache(string $key, callable $callback)
    {
        // 🎼 Generate sophisticated cache key based on state
        // 🛡️ B-67: `json_encode` BAŞARISIZ olursa `false` döner ve
        // `md5(false)` = `md5("")` → TÜM bozuk filtreler TEK anahtara düşer
        // (ölçüldü: INF / NAN / geçersiz UTF-8 → 3 durum, 1 anahtar).
        // Yedek karma `print_r` ile üretilir (`print_r` bu değerlerde de
        // başarısız olmaz). Encode edilebilen durumlarda anahtar BİREBİR AYNIDIR
        // (cache invalidation tutarlılığı bozulmaz).
        $state = [
            'filters' => $this->filters,
            'orders'  => $this->orders,
            'search'  => $this->searchTerm,
            'mode'    => $this->hydrationMode
        ];

        $stateJson = json_encode($state);
        $stateKey = ($stateJson === false || $stateJson === '')
            ? 'raw-' . substr(md5(print_r($state, true)), 0, 16)
            : md5($stateJson);

        // 🛡️ B-66: kalıp (kanal) katmanı doğrudan HTTP'ye çıkıyordu ve hem
        // `request()` hem `project_key()` guard'sızdı → CLI'de kanal kullanılamaz
        // durumdaydı. Artık ikisi de güvenli çözülür. Proje anahtarı yine
        // anahtarın parçasıdır (davranış DEĞİŞTİRİLMEDİ).
        $projectKey = '';
        if (function_exists('request')) {
            try {
                $req = request();
                if (is_object($req) && method_exists($req, 'query')) {
                    $q = $req->query('project');
                    $projectKey = is_string($q) ? $q : '';
                }
            } catch (\Throwable $e) {
                // HTTP bağlamı yoksa sessizce aktif projeye düşüyoruz.
                $projectKey = '';
            }
        }

        if ($projectKey === '' && function_exists('project_key')) {
            $pk = project_key();
            $projectKey = is_string($pk) ? $pk : '';
        }

        $projectKey = $projectKey !== '' ? $projectKey : 'default';

        $fullKey = "{$this->cachePrefix}_{$projectKey}_{$this->status}_{$this->limit}_{$this->offset}_{$stateKey}_{$key}";
        
        $ttl = $this->cacheTtl ?? $this->storage->cache()->getTtl('short');
        
        return $this->storage->cache()->remember($fullKey, $callback, $ttl);
    }
}
