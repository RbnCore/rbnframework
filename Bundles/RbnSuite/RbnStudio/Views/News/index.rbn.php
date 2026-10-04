<!-- RBN STUDIO NEWS INDEX VIEW -->
<div class="d-flex justify-content-between align-items-center mb-4 pb-2">
    <div class="d-flex align-items-center gap-4">
        <ul class="nav rbn-tab-pills gap-1">
            <li class="nav-item">
                <a class="nav-link {{ ($activeTab ?? 'active') == 'active' ? 'active' : '' }}" href="?tab=active">
                    <i class="ri-checkbox-circle-fill text-success me-1"></i> Aktif Haberler
                    <span class="badge ms-1">{{ $counts['active'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($activeTab ?? 'active') == 'rewritten' ? 'active' : '' }}" href="?tab=rewritten">
                    <i class="ri-sparkling-fill text-warning me-1"></i> Yeniden Üretilenler
                    <span class="badge ms-1">{{ $counts['rewritten'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($activeTab ?? 'active') == 'passive' ? 'active' : '' }}" href="?tab=passive">
                    <i class="ri-close-circle-fill text-danger me-1"></i> Pasif Haberler
                    <span class="badge ms-1">{{ $counts['passive'] ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($activeTab ?? 'active') == 'all' ? 'active' : '' }}" href="?tab=all">
                    <i class="ri-grid-fill me-1"></i> Tümü
                    <span class="badge ms-1">{{ $counts['all'] ?? 0 }}</span>
                </a>
            </li>
        </ul>
    </div>
</div>

@php
$hasCategory = !empty($hasCategory);
$hasFilter = !empty($request->query('search')) || ($hasCategory && !empty($request->query('category_id')));
@endphp

<!-- 1. SEARCH & FILTER BAR -->
<div class="row g-3 mb-4 align-items-center rbn-table-filters">
    <div class="{{ $hasCategory ? ($hasFilter ? 'col-md-5' : 'col-md-6') : ($hasFilter ? 'col-md-8' : 'col-md-9') }}">
        <div class="position-relative h-100">
            <i class="ri-search-line search-icon"></i>
            <input type="text" class="rbn-form-input w-100" placeholder="Haber başlığı veya özeti ara..." value="{{ $request->query('search') }}"
                data-rbn-table-search="newsTable">
        </div>
    </div>
    @if($hasCategory)
    <div class="col-md-3">
        <div class="dropdown w-100 h-100 rbn-filter-dropdown">
            <button class="btn btn-light dropdown-toggle w-100 h-100 d-flex justify-content-between align-items-center"
                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="opacity-75 fw-medium d-flex align-items-center gap-2">
                    <i class="ri-folder-line text-warning"></i>
                    <span data-filter-current-label>
                        @php
                        $selectedCategory = 'Tüm Kategoriler';
                        if (!empty($request->query('category_id'))) {
                            foreach ($categories as $cat) {
                                if ($cat['id'] == $request->query('category_id')) {
                                    $selectedCategory = $cat['name'];
                                    break;
                                }
                            }
                        }
                        @endphp
                        {{ $selectedCategory }}
                    </span>
                </span>
                <i class="ri-arrow-down-s-line opacity-50 pe-1"></i>
            </button>
            <ul class="dropdown-menu shadow-sm border-0 py-2 w-100 mt-2">
                <li>
                    <a class="dropdown-item py-2 fw-medium text-secondary {{ empty($request->query('category_id')) ? 'active' : '' }}"
                        href="#" data-rbn-table-filter="newsTable" data-filter-param="category_id" data-filter-value=""
                        data-filter-label="Tüm Kategoriler">
                        <i class="ri-folder-line text-warning me-2"></i>
                        <span>Tüm Kategoriler</span>
                    </a>
                </li>
                @foreach($categories as $cat)
                <li>
                    <a class="dropdown-item py-2 fw-medium text-secondary {{ $request->query('category_id') == $cat['id'] ? 'active' : '' }}"
                        href="#" data-rbn-table-filter="newsTable" data-filter-param="category_id"
                        data-filter-value="{{ $cat['id'] }}" data-filter-label="{{ $cat['name'] }}">
                        <i class="{{ !empty($cat['icon']) ? $cat['icon'] : 'ri-folder-line' }} text-warning me-2"></i>
                        <span>{{ $cat['name'] }}</span>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
    <div class="col-md-3">
        <div class="dropdown w-100 h-100 rbn-filter-dropdown">
            <button class="btn btn-light dropdown-toggle w-100 h-100 d-flex justify-content-between align-items-center"
                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="text-secondary fw-medium d-flex align-items-center gap-2" data-sort-label>
                    <i class="ri-sort-desc opacity-50"></i> Sıralama Ölçütü
                </span>
                <i class="ri-arrow-down-s-line opacity-50 pe-1"></i>
            </button>
            <ul class="dropdown-menu shadow-sm border-0 py-2 w-100 mt-2">
                <li>
                    <h6 class="dropdown-header text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">Sıralama Seçenekleri</h6>
                </li>
                <li><a class="dropdown-item py-2 fw-medium text-secondary" href="#" data-rbn-table-sort="newsTable" data-sort-col="2" data-sort-type="string" data-sort-dir="asc"><i class="ri-sort-asc me-2"></i>A'dan Z'ye (Başlık)</a></li>
                <li><a class="dropdown-item py-2 fw-medium text-secondary" href="#" data-rbn-table-sort="newsTable" data-sort-col="2" data-sort-type="string" data-sort-dir="desc"><i class="ri-sort-desc me-2"></i>Z'den A'ya (Başlık)</a></li>
                <li><hr class="dropdown-divider opacity-10"></li>
                <li><a class="dropdown-item py-2 fw-medium text-secondary" href="#" data-rbn-table-sort="newsTable" data-sort-col="1" data-sort-type="number" data-sort-dir="desc"><i class="ri-eye-fill me-2"></i>En Çok Okunan</a></li>
                <li><a class="dropdown-item py-2 fw-medium text-secondary" href="#" data-rbn-table-sort="newsTable" data-sort-col="1" data-sort-type="number" data-sort-dir="asc"><i class="ri-eye-line me-2"></i>En Az Okunan</a></li>
            </ul>
        </div>
    </div>
    @if($hasFilter)
    <div class="col-md-1 text-end">
        <a href="?tab={{ $activeTab }}"
            class="btn btn-sm btn-light text-danger border rounded-pill shadow-sm px-2 d-inline-flex align-items-center justify-content-center gap-1 text-decoration-none w-100"
            style="height: 46px;" data-tooltip="Filtreyi Temizle">
            <i class="ri-close-circle-fill fs-5"></i>
        </a>
    </div>
    @endif
</div>

<!-- 2. TABLE WRAPPER -->
<div class="rbn-table-wrap mb-5">
    <div class="ra-traffic-card-header d-flex justify-content-between align-items-center">
        <div>
            <h6 class="ra-traffic-card-title mb-0 d-flex align-items-center gap-2">
                <i class="ri-newspaper-line text-warning fs-5"></i>
                <span class="fw-bold text-dark fs-6">Kayıtlı Haberler</span>
                <span class="rbn-badge rbn-badge-terracotta px-2.5 py-0.5 font-monospace fw-bold" style="font-size: 0.72rem;">{{ $pager->total() }} HABER</span>
            </h6>
            <div class="text-muted small mt-0.5">Sistemdeki yayınlanmış ve taslak haber içerikleri havuzu</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if(!empty($rewrittenCount))
            <span class="rbn-badge rbn-badge-emerald px-3 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.78rem;">
                <i class="ri-sparkling-2-fill text-success fs-6"></i>
                <span><strong>{{ $rewrittenCount }}</strong> Yeniden Üretilen</span>
            </span>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="rbn-table align-middle mb-0" id="newsTable">
            <thead>
                <tr>
                    <th style="width: 50px;">Görsel</th>
                    <th style="width: 70px;" class="text-center">Sürüm</th>
                    <th>Haber Başlığı / İçerik</th>
                    <th>{{ $hasCategory ? 'Kategori & Tarih' : 'Yayın Tarihi' }}</th>
                    <th class="text-end pe-4" style="width: 140px;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @foreach($posts as $post)
                <tr class="transition-all" data-id="{{ $post['id'] }}">
                    <td>
                        @if(!empty($post['image']) || !empty($post['image_url']))
                        <img src="{{ !empty($post['image']) ? $post['image'] : $post['image_url'] }}"
                            class="rounded-3 shadow-sm" style="width: 44px; height: 44px; object-fit: cover;"
                            alt="{{ $post['title'] ?? 'Görsel' }}">
                        @else
                        <div class="rounded-3 shadow-sm bg-light d-flex align-items-center justify-content-center text-muted"
                            style="width: 44px; height: 44px; border: 1px dashed var(--rbn-admin-border-subtle, #e5ded3);">
                            <i class="ri-image-line fs-5 opacity-50"></i>
                        </div>
                        @endif
                    </td>
                    <td class="text-center" style="width: 70px;" data-sort-val="{{ (int)($post['views'] ?? 0) }}">
                        <div class="rbn-metric-tile" data-tooltip="Versiyon: {{ !empty($post['is_rewritten']) ? 'v2 (AI Yeniden Üretim)' : 'v1 (Orijinal)' }} | Okunma: {{ number_format((int)($post['views'] ?? 0)) }}">
                            <div class="tile-version {{ !empty($post['is_rewritten']) ? 'v2' : 'v1' }}">
                                {{ !empty($post['is_rewritten']) ? 'v2' : 'v1' }}
                            </div>
                            <div class="tile-divider"></div>
                            <div class="tile-views">
                                <i class="ri-eye-line"></i>{{ number_format((int)($post['views'] ?? 0)) }}
                            </div>
                        </div>
                    </td>
                    <td class="col-truncate">
                        <div class="rbn-table-title" title="{{ $post['main_title'] ?? $post['title'] }}">
                            @php
                                $catId = $post['category_id'] ?? null;
                                $cSlug = ($catId !== null && isset($categorySlugMap[$catId])) ? $categorySlugMap[$catId] : '';
                                $previewUrl = !empty($cSlug) ? "/haber/{$cSlug}/{$post['slug']}" : "/haber/{$post['slug']}";
                            @endphp
                            @if(($post['is_active'] ?? 1) == 1)
                            <a href="{{ $previewUrl }}"
                                target="_blank" class="fw-bold text-dark text-decoration-none hover-primary d-inline-flex align-items-center gap-1 w-100"
                                data-tooltip="Sitede Canlı İncele">
                                <span class="text-truncate d-inline-block">{{ $post['main_title'] ?? $post['title'] }}</span>
                                <i class="ri-external-link-line small text-muted flex-shrink-0" style="font-size: 0.8rem;"></i>
                            </a>
                            @else
                            <span class="fw-bold text-secondary opacity-75 text-truncate d-block">
                                {{ $post['main_title'] ?? $post['title'] }}
                            </span>
                            @endif
                        </div>
                        <small class="text-muted rbn-table-slug" style="font-size: 0.75rem;" title="{{ $post['slug'] }}">
                            <i class="ri-link me-0.5"></i>{{ $post['slug'] }}
                        </small>
                    </td>
                    <td>
                        @if($hasCategory)
                        <div class="fw-semibold text-secondary small">
                            {{ ($catId !== null && isset($categoryMap[$catId])) ? $categoryMap[$catId] : 'Kategorisiz' }}
                        </div>
                        @endif
                        @if(!empty($post['published_date']))
                        <div class="text-muted mt-0.5" style="font-size: 0.75rem;">
                            {{ $post['published_date'] }}{{ !empty($post['published_time']) ? ' - ' . $post['published_time'] : '' }}
                        </div>
                        @endif
                        @if(!empty($post['published_relative']))
                        <small class="text-muted d-block opacity-75" style="font-size: 0.7rem;">
                            ({{ $post['published_relative'] }})
                        </small>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-inline-flex gap-2 align-items-center">
                            @if(!empty($hasSocial) && !empty($socialPlatforms) && (($post['is_active'] ?? 1) == 1) && !empty($post['social_summary']) && !empty($post['social_hashtags']))
                            <button type="button"
                                class="ra-table-btn text-primary"
                                style="color: #4f46e5 !important; background: rgba(79, 70, 229, 0.08);"
                                data-rbn-modal="true"
                                data-type="RbnStudio/News/SocialShare"
                                data-id="{{ $post['id'] }}"
                                data-title="Sosyal Medyada Paylaş"
                                data-size="lg"
                                data-endpoint="{{ $Route->url('admin.studio.news.social-modal', ['id' => $post['id']]) }}"
                                data-tooltip="Sosyal Medyada Paylaş">
                                <i class="ri-share-forward-line"></i>
                            </button>
                            @endif
                            <a href="{{ $Route->url('admin.studio.news.edit', ['id' => $post['id']]) }}"
                                class="ra-table-btn"
                                data-tooltip="Haberi Düzenle">
                                <i class="ri-edit-line"></i>
                            </a>
                            <button type="button"
                                class="ra-table-btn ra-table-btn-danger action-confirm"
                                data-url="{{ $Route->url('admin.studio.news.delete', ['id' => $post['id']]) }}"
                                data-id="{{ $post['id'] }}"
                                data-method="POST"
                                data-title="Haberi Sil?"
                                data-text="Bu haberi silmek istediğinize emin misiniz? Bu işlem geri alınamaz!"
                                data-tooltip="Haberi Sil">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach

                @if(empty($posts))
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="ri-inbox-line fs-1 d-block mb-2 text-muted opacity-50"></i>
                        Henüz bu kriterde kayıtlı haber bulunmuyor.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center p-4 border-top">
        <div class="text-muted small">
            Toplam <strong class="text-dark">{{ $pager->total() }}</strong> haber listeleniyor.
        </div>
        <div id="newsTable-pagination-wrapper">
            @if(isset($pager) && $pager->hasPages())
            <div>
                {!! $pager->links() !!}
            </div>
            @endif
        </div>
    </div>
</div>