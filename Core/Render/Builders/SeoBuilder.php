<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Builders;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SeoBuilder - State accumulator and structural builder for SEO metadata 🛰️⚓
 * Part of RBN 3.5 Masterpiece.
 */
class SeoBuilder extends BaseComponent
{
    /** @var array Accumulated overrides */
    protected array $overrides = [];

    /** @var array Prepared payload cache */
    protected array $payload = [];

    /**
     * Reset the builder state.
     */
    public function reset(): self
    {
        $this->overrides = [];
        $this->payload = [];
        return $this;
    }

    /**
     * Set the page title override.
     */
    public function setTitle(?string $title): self
    {
        $title = $title ?? '';
        $this->overrides['title'] = $title;
        if (!empty($this->payload)) {
            $this->payload['meta']['title'] = $title;
            $this->payload['og']['og:title'] = $title;
        }
        return $this;
    }

    /**
     * Set the page description override.
     */
    public function setDescription(?string $description): self
    {
        $description = $description ?? '';
        $this->overrides['description'] = $description;
        if (!empty($this->payload)) {
            $this->payload['meta']['description'] = $description;
            $this->payload['og']['og:description'] = $description;
        }
        return $this;
    }

    /**
     * Set the page keywords override.
     */
    public function setKeywords(?string $keywords): self
    {
        $keywords = $keywords ?? '';
        $this->overrides['keywords'] = $keywords;
        if (!empty($this->payload)) {
            $this->payload['meta']['keywords'] = $keywords;
        }
        return $this;
    }

    /**
     * Set the page canonical URL override.
     */
    public function setCanonical(?string $url): self
    {
        $url = $url ?? '';
        $this->overrides['canonical'] = $url;
        if (!empty($this->payload)) {
            $this->payload['meta']['canonical-url'] = $url;
            $this->payload['og']['og:url'] = $url;
        }
        return $this;
    }

    /**
     * Set the robots directives override.
     */
    public function noIndex(string $directives = 'noindex, nofollow'): self
    {
        $this->overrides['robots'] = $directives;
        if (!empty($this->payload)) {
            $this->payload['meta']['robots'] = $directives;
        }
        return $this;
    }

    /**
     * Set the OG image override.
     */
    public function setImage(string $path): self
    {
        $resolver = $this->resolver('seo');
        $resolved = $resolver ? $resolver->resolveAsset($path) : $path;
        $this->overrides['og_image'] = $resolved;
        if (!empty($this->payload)) {
            $this->payload['og']['og:image'] = $resolved;
        }
        return $this;
    }

    /**
     * Get all accumulated overrides.
     */
    public function getOverrides(): array
    {
        return $this->overrides;
    }

    /**
     * Get the prepared payload.
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * Check if payload is already prepared.
     */
    public function hasPayload(): bool
    {
        return !empty($this->payload);
    }

    /**
     * Prepare the SEO context and build the payload.
     */
    public function prepare(string $context = 'project', array $overrides = []): self
    {
        foreach ($overrides as $key => $value) {
            if ($key === 'title') $this->setTitle($value);
            elseif ($key === 'description') $this->setDescription($value);
            elseif ($key === 'canonical') $this->setCanonical($value);
            elseif ($key === 'keywords') $this->setKeywords($value);
            elseif ($key === 'robots') $this->noIndex($value);
            elseif ($key === 'og_image') $this->setImage($value);
            elseif ($key === 'favicon') $this->overrides['favicon'] = $value;
            elseif ($key === 'virtual_favicon') $this->overrides['virtual_favicon'] = $value;
        }

        $resolver = $this->resolver('seo');
        $resolved = $resolver ? $resolver->resolve($context, $this->overrides) : [];

        $this->payload = $this->build($resolved);

        return $this;
    }

    /**
     * Build the final structured payload by merging resolved base data with overrides.
     */
    public function build(array $resolved): array
    {
        // Resolved meta values already processed the overrides and appended the project name.
        // We only fall back to raw overrides if they are not present in the resolved array.
        $meta = $resolved['meta'] ?? [];
        $og = $resolved['og'] ?? [];

        foreach (['title', 'description', 'keywords', 'canonical-url', 'robots'] as $key) {
            $overrideKey = $key === 'canonical-url' ? 'canonical' : $key;
            if (empty($meta[$key]) && !empty($this->overrides[$overrideKey])) {
                $meta[$key] = $this->overrides[$overrideKey];
            }
        }

        foreach (['og:title', 'og:description', 'og:url', 'og:image'] as $key) {
            $overrideKey = str_replace('og:', '', $key);
            if ($overrideKey === 'url') {
                $overrideKey = 'canonical';
            }
            if (empty($og[$key]) && !empty($this->overrides[$overrideKey])) {
                $og[$key] = $this->overrides[$overrideKey];
            }
        }

        return [
            'context' => $resolved['context'] ?? ($meta['context'] ?? 'frontend'),
            'meta' => $meta,
            'og' => $og,
            'label' => $meta['module-name'] ?? ($resolved['label'] ?? ''),
            'copyright' => $resolved['copyright'] ?? ''
        ];
    }
}
