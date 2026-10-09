<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * SeoService - Public API Gateway for SEO Operations 🎼🛰️⚓
 * Part of RBN Framework.
 */
class SeoService extends BaseService
{
    /**
     * Prepare the SEO context and build the payload using SeoBuilder.
     */
    public function prepare(string $context = 'project', array $overrides = []): self
    {
        $builder = $this->handler('seoBuilder');
        if ($builder) {
            // Support passing string as overrides for backwards-compatibility or simple setups
            if (is_string($overrides)) {
                $overrides = ['title' => $overrides];
            }
            $builder->prepare($context, $overrides);
            $payload = $builder->payload();

            if (str_contains($payload['meta']['robots'] ?? '', 'noindex') && $this->response) {
                $this->response->header('X-Robots-Tag', $payload['meta']['robots']);
            }
        }

        return $this;
    }

    /**
     * Retrieve a setting group or key via SeoResolver.
     */
    public function setting(string $group, ?string $key = null)
    {
        $resolver = $this->resolver('seo');
        if ($resolver) {
            $hub = $resolver->resolve('project');
            $groupData = $hub[$group] ?? [];
            return $key === null ? $groupData : ($groupData[$key] ?? null);
        }
        return null;
    }

    /**
     * Render the prepared SEO payload using SeoProvider.
     */
    public function render(?string $view = 'meta', array $data = []): string
    {
        $builder = $this->handler('seoBuilder');
        if ($builder) {
            // If data is explicitly passed (like overrides), we prepare first
            if (!empty($data)) {
                $builder->prepare('project', $data);
            }

            // If payload is empty, auto-prepare defaults
            if (!$builder->hasPayload()) {
                $builder->prepare('project');
            }
        }

        $provider = $this->provider('seo');
        return ($provider && $builder) ? $provider->render($view, $builder->payload()) : '';
    }

    /**
     * State Access: Returns the prepared metadata array from SeoBuilder.
     */
    public function payload(): array
    {
        $builder = $this->handler('seoBuilder');
        return $builder ? $builder->payload() : [];
    }

    public function setTitle(string $title): self
    {
        $builder = $this->handler('seoBuilder');
        if ($builder) {
            $builder->setTitle($title);
        }
        return $this;
    }

    public function setDescription(string $description): self
    {
        $builder = $this->handler('seoBuilder');
        if ($builder) {
            $builder->setDescription($description);
        }
        return $this;
    }

    public function setCanonical(string $url): self
    {
        $builder = $this->handler('seoBuilder');
        if ($builder) {
            $builder->setCanonical($url);
        }
        return $this;
    }

    public function noIndex(string $directives = 'noindex, nofollow'): self
    {
        $builder = $this->handler('seoBuilder');
        if ($builder) {
            $builder->noIndex($directives);
        }
        if ($this->response) {
            $this->response->header('X-Robots-Tag', $directives);
        }
        return $this;
    }

    public function setImage(string $path): self
    {
        $builder = $this->handler('seoBuilder');
        if ($builder) {
            $builder->setImage($path);
        }
        return $this;
    }
}
