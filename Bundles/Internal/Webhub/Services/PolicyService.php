<?php

namespace Rbn\Framework\Bundles\Internal\Webhub\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * PolicyService - Mastering Polymorphic Logic 🎻⚖️⚓
 * RBN Framework Standard: Pure Service Intelligence with zero model changes.
 */
class PolicyService extends BaseService
{
    /** @var string Primary Target Model Discovery 🎯 */
    protected $targetModel = 'Pages';

    /**
     * Standard CRUD: Record Creation 🆕
     */
    public function create(array $data): self
    {
        if (($data['entity'] ?? 'page') === 'page') {
            if (empty($data['slug']) && !empty($data['title'])) {
                $data['slug'] = $this->rbn->helper('text')->turkishSlug($data['title']);
            }

            if (!$this->isSlugUnique($data['slug'])) {
                throw new \Exception('Bu slug zaten kullanılıyor.');
            }
        }

        return parent::create($data);
    }

    /**
     * Standard CRUD: Record Update 📝
     */
    public function update(array $data): self
    {
        $id = (int) ($this->targetId ?? ($data['id'] ?? 0));

        if (($data['entity'] ?? 'page') === 'page' && isset($data['slug'])) {
            if (!$this->isSlugUnique($data['slug'], $id)) {
                throw new \Exception('Bu slug zaten kullanılıyor.');
            }
        }

        return parent::update($data);
    }

    /* --- [ DOMAIN EXPERTISE ] --- */

    public function isSlugUnique(string $slug, ?int $excludeId = null): bool
    {
        return $this->provider->PagesModel->where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first() === null;
    }

    /* --- [ DATA PROXY ENGINE ] --- */

    public function getPages(): array
    {
        return $this->provider->getPages();
    }


    public function getTemplates(): array
    {
        return $this->provider->getTemplates();
    }

    public function findRecord(int $id, string $entity = 'page'): ?array
    {
        return $this->provider->model('Pages')->find($id);
    }
}
