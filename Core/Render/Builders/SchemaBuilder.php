<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Builders;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SchemaBuilder - Fluid builder to structure JSON-LD schemas 🛰️⚓
 */
class SchemaBuilder extends BaseComponent
{
    protected array $schemas = [];

    /**
     * Add a generic schema structure.
     */
    public function schema(string $type, array $data): self
    {
        $schema = array_merge([
            '@context' => 'https://schema.org',
            '@type' => $type
        ], $data);

        $this->schemas[] = $schema;
        return $this;
    }

    /**
     * Add a pre-built raw schema array.
     */
    public function addRaw(array $schema): self
    {
        $this->schemas[] = $schema;
        return $this;
    }

    /**
     * Reset the builder's state to start fresh.
     */
    public function reset(): self
    {
        $this->schemas = [];
        return $this;
    }

    /**
     * Retrieve all constructed schemas.
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * Add FAQ schema.
     */
    public function faq(array $faqs): self
    {
        if (empty($faqs)) {
            return $this;
        }

        $faqItems = [];
        foreach ($faqs as $f) {
            $faqItems[] = [
                '@type' => 'Question',
                'name' => $f['q'] ?? $f['question'] ?? '',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $f['a'] ?? $f['answer'] ?? ''
                ]
            ];
        }

        return $this->schema('FAQPage', [
            'mainEntity' => $faqItems
        ]);
    }

    /**
     * Add Breadcrumb list schema.
     */
    public function breadcrumbs(array $steps): self
    {
        if (empty($steps)) {
            return $this;
        }

        $items = [];
        $position = 1;
        foreach ($steps as $step) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $step['name'],
                'item' => $step['url'] ?? $step['item'] ?? ''
            ];
        }

        return $this->schema('BreadcrumbList', [
            'itemListElement' => $items
        ]);
    }

    /**
     * Add Product schema with offers and ratings.
     */
    public function product(array $data): self
    {
        $offers = [];
        if (!empty($data['price'])) {
            $offers = [
                '@type' => 'Offer',
                'price' => $data['price'],
                'priceCurrency' => $data['currency'] ?? 'TRY',
                'availability' => $data['availability'] ?? 'https://schema.org/InStock',
                'url' => $data['url'] ?? '',
                'shippingDetails' => [
                    '@type' => 'OfferShippingDetails',
                    'shippingRate' => [
                        '@type' => 'MonetaryAmount',
                        'value' => '0.00',
                        'currency' => $data['currency'] ?? 'TRY'
                    ],
                    'shippingDestination' => [
                        '@type' => 'DefinedRegion',
                        'addressCountry' => 'TR'
                    ],
                    'deliveryTime' => [
                        '@type' => 'ShippingDeliveryTime',
                        'handlingTime' => [
                            '@type' => 'QuantitativeValue',
                            'minValue' => '0',
                            'maxValue' => '2',
                            'unitCode' => 'DAY'
                        ],
                        'transitTime' => [
                            '@type' => 'QuantitativeValue',
                            'minValue' => '1',
                            'maxValue' => '3',
                            'unitCode' => 'DAY'
                        ]
                    ]
                ],
                'hasMerchantReturnPolicy' => [
                    '@type' => 'MerchantReturnPolicy',
                    'applicableCountry' => 'TR',
                    'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnPeriod',
                    'merchantReturnDays' => '14',
                    'returnMethod' => 'https://schema.org/ReturnByMail',
                    'returnFees' => 'https://schema.org/FreeReturn'
                ]
            ];
        }

        $rating = [];
        if (!empty($data['rating_value'])) {
            $rating = [
                '@type' => 'AggregateRating',
                'ratingValue' => $data['rating_value'],
                'reviewCount' => $data['review_count'] ?? 1
            ];
        }

        return $this->schema('Product', array_filter([
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'image' => $data['image'] ?? '',
            'sku' => $data['sku'] ?? '',
            'brand' => !empty($data['brand']) ? ['@type' => 'Brand', 'name' => $data['brand']] : null,
            'offers' => $offers ?: null,
            'aggregateRating' => $rating ?: null
        ]));
    }

    /**
     * Add Event schema with location and ticket offers.
     */
    public function event(array $data): self
    {
        $location = [];
        if (!empty($data['location_name'])) {
            $location = [
                '@type' => 'Place',
                'name' => $data['location_name'],
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $data['location_address'] ?? '',
                    'addressLocality' => $data['location_locality'] ?? '',
                    'addressCountry' => $data['location_country'] ?? 'TR'
                ]
            ];
        }

        $offers = [];
        if (!empty($data['price'])) {
            $offers = [
                '@type' => 'Offer',
                'price' => $data['price'],
                'priceCurrency' => $data['currency'] ?? 'TRY',
                'url' => $data['url'] ?? ''
            ];
        }

        return $this->schema('Event', array_filter([
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'startDate' => $data['start_date'] ?? date('c'),
            'endDate' => $data['end_date'] ?? null,
            'image' => $data['image'] ?? '',
            'location' => $location ?: null,
            'offers' => $offers ?: null
        ]));
    }

    /**
     * Add JobPosting schema.
     */
    public function jobPosting(array $data): self
    {
        return $this->schema('JobPosting', array_filter([
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'datePosted' => $data['date_posted'] ?? date('Y-m-d'),
            'validThrough' => $data['valid_through'] ?? null,
            'employmentType' => $data['employment_type'] ?? 'FULL_TIME',
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $data['company_name'] ?? '',
                'sameAs' => $data['company_url'] ?? ''
            ],
            'jobLocation' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $data['locality'] ?? '',
                    'addressRegion' => $data['region'] ?? '',
                    'addressCountry' => $data['country'] ?? 'TR'
                ]
            ],
            'baseSalary' => !empty($data['salary']) ? [
                '@type' => 'MonetaryAmount',
                'currency' => $data['currency'] ?? 'TRY',
                'value' => [
                    '@type' => 'QuantitativeValue',
                    'value' => $data['salary'],
                    'unitText' => $data['salary_unit'] ?? 'MONTH'
                ]
            ] : null
        ]));
    }

    /**
     * Add LocalBusiness / Organization schema from raw company details.
     */
    public function localBusiness(array $company): self
    {
        $geo = [];
        if (!empty($company['mapLocation'])) {
            $geo = $this->geo($company['mapLocation']);
        }

        $rating = [];
        if (!empty($company['ratingScore'])) {
            $rating = [
                '@type' => 'AggregateRating',
                'ratingValue' => $company['ratingScore'],
                'reviewCount' => $company['ratingCount'] ?? 1
            ];
        }

        $openingHours = [];
        if (!empty($company['workDays'])) {
            $openingHours = [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $company['workDays'],
                    'opens' => $company['workOpens'] ?? '09:00',
                    'closes' => $company['workCloses'] ?? '18:00'
                ]
            ];
        }

        // Person veya Organization type: skip business-specific fields (PostalAddress, priceRange, slogan, logo)
        $isNonPhysical = in_array($company['type'] ?? '', ['Person', 'ProfilePage', 'Organization']);

        $schemaData = [
            'name' => $company['name'] ?? '',
            'telephone' => $company['telephone'] ?? '',
            'email' => $company['email'] ?? '',
            'url' => $company['url'] ?? '',
            'image' => $company['image'] ?? '',
            'sameAs' => $company['socialLinks'] ?? null,
            'aggregateRating' => $rating ?: null,
            'openingHoursSpecification' => $openingHours ?: null,
        ];

        if (!$isNonPhysical) {
            // Business-only fields
            $schemaData['slogan'] = $company['slogan'] ?? '';
            $schemaData['logo'] = $company['logo'] ?? '';
            $schemaData['priceRange'] = $company['priceRange'] ?? '$$';
            $schemaData['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $company['address'] ?? '',
                'addressLocality' => $company['locality'] ?? '',
                'addressRegion' => $company['region'] ?? '',
                'addressCountry' => $company['country'] ?? 'TR'
            ];
            $schemaData['geo'] = $geo ?: null;
        }

        // Merge any additional custom keys passed (like areaServed, knowsAbout, etc.)
        $exclude = ['mapLocation', 'ratingScore', 'ratingCount', 'workDays', 'workOpens', 'workCloses', 'address', 'locality', 'region', 'country', 'socialLinks', 'type', 'faqs'];
        foreach ($company as $key => $val) {
            if (!in_array($key, $exclude) && !isset($schemaData[$key])) {
                $schemaData[$key] = $val;
            }
        }

        return $this->schema($company['type'] ?? 'LocalBusiness', array_filter($schemaData));
    }

    /**
     * Add Course schema.
     */
    public function course(array $data): self
    {
        return $this->schema('Course', array_filter([
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'provider' => [
                '@type' => 'Organization',
                'name' => $data['provider_name'] ?? '',
                'sameAs' => $data['provider_url'] ?? ''
            ]
        ]));
    }

    /**
     * Add dynamic custom schema mappings (compatibility/fallback).
     */
    public function custom(array $data): self
    {
        if (!empty($data['type']) && !empty($data['schema'])) {
            $schemaData = $data['schema'];
            if (!empty($data['geo_coordinates'])) {
                $schemaData['geo'] = $this->geo($data['geo_coordinates']);
            }
            $this->schema($data['type'], $schemaData);
        }

        if (!empty($data['breadcrumbs'])) {
            $this->breadcrumbs($data['breadcrumbs']);
        }

        if (!empty($data['faqs'])) {
            $this->faq($data['faqs']);
        }

        return $this;
    }

    /**
     * Parse coordinates/maps URL into GeoCoordinates schema structure.
     */
    public function geo(?string $coordinates): array
    {
        if (!$coordinates) {
            return [];
        }

        $lat = null;
        $lng = null;

        if (str_contains($coordinates, 'google.com/maps')) {
            preg_match('/!3d([0-9.-]+)/', $coordinates, $latMatch);
            preg_match('/!2d([0-9.-]+)/', $coordinates, $lngMatch);

            $lat = $latMatch[1] ?? null;
            $lng = $lngMatch[1] ?? null;
        }

        if (!$lat || !$lng) {
            $parts = explode(',', $coordinates);
            if (count($parts) === 2) {
                $lat = trim($parts[0]);
                $lng = trim($parts[1]);
            }
        }

        if (!$lat || !$lng) {
            return [];
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => $lat,
            'longitude' => $lng
        ];
    }

    /**
     * Add ContactPage schema 📄
     */
    public function contact(array $data): self
    {
        return $this->schema('ContactPage', [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'url' => $data['url'] ?? ''
        ]);
    }

    /**
     * Add BlogPosting schema 📄
     */
    public function blogPost(array $data): self
    {
        return $this->schema('BlogPosting', [
            'headline' => $data['headline'] ?? '',
            'description' => $data['description'] ?? '',
            'image' => $data['image'] ?? '',
            'datePublished' => $data['datePublished'] ?? date('Y-m-d'),
            'author' => ['@type' => 'Organization', 'name' => $data['authorName'] ?? '']
        ]);
    }

    /**
     * Add TVSeries schema 📺
     */
    public function seriesDetail(array $data): self
    {
        return $this->schema('TVSeries', array_filter([
            'name' => $data['name'] ?? '',
            'alternativeHeadline' => $data['alternativeHeadline'] ?? '',
            'description' => $data['description'] ?? '',
            'image' => $data['image'] ?? '',
            'datePublished' => $data['datePublished'] ?? '',
            'genre' => $data['genre'] ?? '',
            'aggregateRating' => $data['aggregateRating'] ?? null,
            'numberOfSeasons' => $data['numberOfSeasons'] ?? null,
            'numberOfEpisodes' => $data['numberOfEpisodes'] ?? null
        ]));
    }

    /**
     * Add Movie schema 🎬
     */
    public function movieDetail(array $data): self
    {
        return $this->schema('Movie', array_filter([
            'name' => $data['name'] ?? '',
            'alternativeHeadline' => $data['alternativeHeadline'] ?? '',
            'description' => $data['description'] ?? '',
            'image' => $data['image'] ?? '',
            'datePublished' => $data['datePublished'] ?? '',
            'genre' => $data['genre'] ?? '',
            'aggregateRating' => $data['aggregateRating'] ?? null,
            'duration' => $data['duration'] ?? null
        ]));
    }

    /**
     * Add Product Review (Pros & Cons) schema 📈
     */
    public function prosCons(array $data, array $meta = []): self
    {
        $prosCons = is_string($data['pros_cons']) ? json_decode($data['pros_cons'], true) : $data['pros_cons'];
        if (!is_array($prosCons) || (empty($prosCons['pros']) && empty($prosCons['cons']))) {
            return $this;
        }

        $posNotes = [];
        if (!empty($prosCons['pros']) && is_array($prosCons['pros'])) {
            $posElements = [];
            foreach ($prosCons['pros'] as $idx => $pro) {
                $posElements[] = [
                    '@type' => 'ListItem',
                    'position' => $idx + 1,
                    'name' => trim(strip_tags((string) $pro))
                ];
            }
            $posNotes = [
                '@type' => 'ItemList',
                'itemListElement' => $posElements
            ];
        }

        $negNotes = [];
        if (!empty($prosCons['cons']) && is_array($prosCons['cons'])) {
            $negElements = [];
            foreach ($prosCons['cons'] as $idx => $con) {
                $negElements[] = [
                    '@type' => 'ListItem',
                    'position' => $idx + 1,
                    'name' => trim(strip_tags((string) $con))
                ];
            }
            $negNotes = [
                '@type' => 'ItemList',
                'itemListElement' => $negElements
            ];
        }

        return $this->schema('Product', [
            'name' => $data['title'] ?? ($meta['title'] ?? 'Urun Kiyaslamasi'),
            'image' => $data['image'] ?? '',
            'review' => array_filter([
                '@type' => 'Review',
                'name' => $prosCons['title'] ?? 'Degerlendirme Analizi',
                'author' => ['@type' => 'Organization', 'name' => $meta['appName'] ?? ''],
                'positiveNotes' => !empty($posNotes) ? $posNotes : null,
                'negativeNotes' => !empty($negNotes) ? $negNotes : null
            ])
        ]);
    }
}
