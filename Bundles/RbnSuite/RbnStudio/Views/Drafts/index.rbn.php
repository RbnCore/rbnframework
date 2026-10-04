<!-- RBN STUDIO DRAFTS INDEX VIEW -->
@php
    $hasFilter = !empty($request->query('search')) || !empty($request->query('category_id'));
@endphp

<!-- 1. SEARCH & FILTER BAR -->
<div class="row g-3 mb-4 align-items-center rbn-table-filters">
    <div class="col-md-6">
        <div class="position-relative h-100">
            <i class="ri-search-line search-icon"></i>
            <input type="text" class="rbn-form-input w-100" placeholder="Fikir veya taslak başlığı ara..."
                   value="{{ $request->query('search') }}"
                   data-rbn-table-search="draftTable">
        </div>
    </div>
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
                       href="#" data-rbn-table-filter="draftTable" data-filter-param="category_id" data-filter-value="" data-filter-label="Tüm Kategoriler">
                        <i class="ri-folder-line text-warning me-2"></i>
                        <span>Tüm Kategoriler</span>
                    </a>
                </li>
                @foreach($categories as $cat)
                <li>
                    <a class="dropdown-item py-2 fw-medium text-secondary {{ $request->query('category_id') == $cat['id'] ? 'active' : '' }}" 
                       href="#" data-rbn-table-filter="draftTable" data-filter-param="category_id" data-filter-value="{{ $cat['id'] }}" data-filter-label="{{ $cat['name'] }}">
                        <i class="{{ !empty($cat['icon']) ? $cat['icon'] : 'ri-folder-line' }} text-warning me-2"></i>
                        <span>{{ $cat['name'] }}</span>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
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
                <li><a class="dropdown-item py-2 fw-medium text-secondary" href="#" data-rbn-table-sort="draftTable" data-sort-col="1" data-sort-type="string" data-sort-dir="asc"><i class="ri-sort-asc me-2"></i>A'dan Z'ye (Fikir)</a></li>
                <li><a class="dropdown-item py-2 fw-medium text-secondary" href="#" data-rbn-table-sort="draftTable" data-sort-col="1" data-sort-type="string" data-sort-dir="desc"><i class="ri-sort-desc me-2"></i>Z'den A'ya (Fikir)</a></li>
                <li><hr class="dropdown-divider opacity-10"></li>
                <li><a class="dropdown-item py-2 fw-medium text-secondary" href="#" data-rbn-table-sort="draftTable" data-sort-col="2" data-sort-type="string" data-sort-dir="asc"><i class="ri-folder-line me-2"></i>Kategoriye Göre</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- 2. TABLE WRAPPER -->
<div class="rbn-table-wrap mb-5">
    <div class="ra-traffic-card-header d-flex justify-content-between align-items-center">
        <div>
            <h6 class="ra-traffic-card-title mb-0 d-flex align-items-center gap-2">
                <i class="ri-lightbulb-line text-warning fs-5"></i>
                <span class="fw-bold text-dark fs-6">İçerik Fikir & Taslakları</span>
                <span class="rbn-badge rbn-badge-terracotta px-2.5 py-0.5 font-monospace fw-bold" style="font-size: 0.72rem;"><?= $pager->total() ?> TASLAK</span>
            </h6>
            <div class="text-muted small mt-0.5">Yapay zeka için üretilmiş veya manuel eklenmiş blog taslak havuzu</div>
        </div>
        <div>
            <button type="button"
                class="rbn-btn rbn-btn-primary px-4 py-2 fw-bold d-flex align-items-center gap-1"
                data-rbn-modal="true" data-type="RbnStudio/Drafts" data-title="Yeni Fikir Tanımla"
                data-endpoint="<?= $Route->url('studio/drafts/modal', 'admin') ?>"
                data-size="lg">
                <i class="ri-add-line fs-5"></i> Yeni Fikir Ekle
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="rbn-table align-middle mb-0" id="draftTable" data-rbn-sortable="true" data-sort-url="{{ $Route->url('studio/drafts/reorder', 'admin') }}">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Blog Başlığı / Fikir</th>
                    <th>Kategori</th>
                    <th class="text-end pe-4" style="width: 280px;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @foreach($drafts as $draft)
                <tr class="transition-all" data-id="{{ $draft['id'] }}">
                    <td class="text-center">
                        <i class="ri-drag-move-2-line text-muted sortable-handle opacity-50 fs-5 cursor-pointer" data-tooltip="Sıralamak İçin Sürükleyin"></i>
                    </td>
                    <td>
                        <div class="fw-bold text-dark fs-6">{{ $draft['title'] }}</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 fw-semibold">
                            <i class="ri-folder-line text-warning me-1"></i>
                            {{ $categoryMap[$draft['category_id']] ?? 'Genel' }}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-inline-flex gap-2 align-items-center">
                            <button type="button" class="rbn-btn rbn-btn-primary btn-sm px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm"
                                data-rbn-ajax="true"
                                data-url="<?= $Route->url('admin.studio.drafts.generate', ['id' => $draft['id']]) ?>"
                                data-confirm="Bu taslak fikrinden Yapay Zeka ile otomatik içerik üretmek istediğinize emin misiniz?"
                                data-tooltip="Yapay Zeka Makale Üret">
                                <i class="ri-sparkling-fill text-warning"></i> Yapay Zeka ile Yaz
                            </button>
                            <button type="button" class="ra-table-btn"
                                data-rbn-modal="true" data-type="RbnStudio/Drafts" data-id="{{ $draft['id'] }}" data-title="Fikri Düzenle" data-size="lg"
                                data-endpoint="<?= $Route->url('studio/drafts/modal', ['id' => $draft['id']], 'admin') ?>"
                                data-tooltip="Düzenle">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button type="button" class="ra-table-btn ra-table-btn-danger action-confirm"
                                data-url="<?= $Route->url('admin.studio.drafts.delete', ['id' => $draft['id']]) ?>"
                                data-id="<?= $draft['id'] ?>"
                                data-method="POST"
                                data-title="Taslak Fikri Sil?"
                                data-text="Bu taslak fikri silinecektir. Onaylıyor musunuz?"
                                data-tooltip="Sil">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
                @if(empty($drafts))
                <tr>
                    <td colspan="4" class="text-center py-5 text-muted">
                        <i class="ri-inbox-line fs-1 d-block mb-2 text-muted opacity-50"></i>
                        Henüz kayıtlı taslak fikir bulunmuyor.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if(!empty($drafts))
    <div class="d-flex justify-content-between align-items-center p-4 border-top">
        <div class="text-muted small">
            Toplam <strong>{{ $pager->total() }}</strong> taslak fikir listeleniyor.
        </div>
        <div>
            <?= $pager->links() ?>
        </div>
    </div>
    @endif
</div>
