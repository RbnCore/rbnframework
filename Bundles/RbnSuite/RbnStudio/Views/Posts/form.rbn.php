<form action="{{ $Route->url('admin.studio.posts.save', isset($post['id']) ? ['id' => $post['id']] : []) }}"
    method="POST" enctype="multipart/form-data" data-rbn-form="true">

    @csrf
    <input type="hidden" name="id" value="{{ $post['id'] ?? '' }}">
    <input type="hidden" name="project_key" value="{{ $projectKey ?? active_project_key() }}">

    @if(!empty($post['id']))
    <!-- 🤖 AI REWRITE BANNER (v1 / v2) -->
    <div class="rbn-table-wrap mb-4 border-start border-4 border-warning">
        <div class="p-4 d-flex align-items-center justify-content-between gap-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 d-flex align-items-center justify-content-center text-warning fs-3 flex-shrink-0"
                    style="background: rgba(217, 119, 6, 0.1); width: 44px; height: 44px;">
                    <i class="ri-sparkling-fill"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h6 class="fw-bold mb-0 text-dark fs-6">Yapay Zeka İle İçerik İnsanlaştırma (v2)</h6>
                        @if(!empty($post['is_rewritten']))
                            <span class="rbn-badge rbn-badge-emerald px-2.5 py-0.5 font-monospace fw-bold" style="font-size: 0.72rem;">v2 Yeniden Üretildi</span>
                        @else
                            <span class="rbn-badge px-2.5 py-0.5 font-monospace fw-semibold" style="font-size: 0.72rem; background: rgba(60,50,40,0.08); color: var(--rbn-text-muted);">v1 Orijinal</span>
                        @endif
                    </div>
                    <div class="text-muted small" style="font-size: 0.8125rem;">
                        Yapay Zeka Engine ile bu makaleyi E-E-A-T uyumlu, zengin HTML tablolu, SSS (FAQ) ve vaka analizli insansı içeriğe dönüştürün.
                    </div>
                </div>
            </div>
            <div class="ms-auto flex-shrink-0">
                <button type="button" data-rbn-ai="rewrite"
                    data-url="{{ $Route->url('admin.studio.posts.rewrite', ['id' => $post['id']]) }}"
                    data-confirm-title="Makale Yeniden Üretilsin mi?"
                    data-confirm="Bu makale yapay zeka ile E-E-A-T uyumlu olarak baştan yazılacak ve güncellenecektir. Görseliniz ve URL adresiniz korunacaktır. Devam etmek istiyor musunuz?"
                    class="rbn-btn rbn-btn-primary px-3.5 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-1.5"
                    style="font-size: 0.825rem;">
                    <i class="ri-magic-line"></i> <span>MAKALEYİ YENİDEN ÜRET (v2)</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- RBN MASTERPIECE FORM CARD -->
            <div class="rbn-table-wrap mb-4">
                <div class="ra-traffic-card-header">
                    <h6 class="ra-traffic-card-title mb-0">
                        <i class="ri-edit-2-line text-warning fs-5"></i>
                        <span>{{ isset($post['id']) && $post['id'] > 0 ? 'Yazıyı Düzenle' : 'Yeni Blog Yazısı Tanımla' }}</span>
                    </h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="{{ !empty($hasCategory) ? 'col-md-9' : 'col-md-12' }}">
                            <label class="form-label fw-bold text-muted small">Yazı Başlığı <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rbn-form-input rounded-3 py-2 shadow-sm"
                                placeholder="Örn: Ev Tipi Su Arıtma Cihazı Alırken Nelere Dikkat Edilmeli?" value="{{ $post['title'] ?? '' }}" required>
                        </div>

                        @if(!empty($hasCategory))
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-muted small">Kategori <span class="text-danger">*</span></label>
                            @php
                                $selectedCatId = $post['category_id'] ?? '';
                                $selectedCatName = 'Seçiniz...';
                                foreach($categories as $cat) {
                                    if ($cat['id'] == $selectedCatId) {
                                        $selectedCatName = $cat['name'];
                                        break;
                                    }
                                }
                            @endphp
                            <div class="rbn-dropdown dropdown rbn-modal-dropdown w-100">
                                <input type="hidden" name="category_id" id="post_category_id" value="{{ $selectedCatId }}" required>
                                <button class="rbn-form-input form-control w-100 d-flex justify-content-between align-items-center dropdown-toggle text-start" 
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="selected-text font-weight-500 text-truncate">
                                        {{ $selectedCatName }}
                                    </span>
                                    <i class="ri-arrow-down-s-line opacity-50 flex-shrink-0"></i>
                                </button>
                                <ul class="rbn-dropdown-menu dropdown-menu shadow-lg py-2 w-100 mt-1">
                                    <li>
                                        <a class="rbn-dropdown-item dropdown-item py-2 d-flex align-items-center justify-content-between {{ empty($selectedCatId) ? 'active' : '' }}"
                                            href="javascript:void(0)" data-value="" data-label="Seçiniz...">
                                            <span>Seçiniz...</span>
                                        </a>
                                    </li>
                                    @foreach($categories as $cat)
                                    <li>
                                        <a class="rbn-dropdown-item dropdown-item py-2 d-flex align-items-center justify-content-between {{ ($selectedCatId == $cat['id']) ? 'active' : '' }}"
                                            href="javascript:void(0)" data-value="{{ $cat['id'] }}" data-label="{{ $cat['name'] }}">
                                            <span>
                                                <i class="{{ !empty($cat['icon']) ? $cat['icon'] : 'ri-folder-line' }} text-warning me-1.5"></i>
                                                {{ $cat['name'] }}
                                            </span>
                                            @if($selectedCatId == $cat['id'])
                                                <i class="ri-check-line text-warning"></i>
                                            @endif
                                        </a>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Özet (Yazı listesinde görünür)</label>
                            <textarea name="summary" class="form-control rbn-form-input rounded-3 p-3 shadow-sm"
                                rows="2" placeholder="Yazının kısa bir özeti...">{{ $post['summary'] ?? '' }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Detaylı İçerik</label>
                            <textarea name="content" class="form-control rbn-form-input rounded-3 p-3 shadow-sm"
                                rows="8" placeholder="Blog yazısının detaylı içeriği...">{{ $post['content'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEO METADATA CARD -->
            <div class="rbn-table-wrap mb-4">
                <div class="ra-traffic-card-header">
                    <h6 class="ra-traffic-card-title mb-0">
                        <i class="ri-search-eye-line text-warning fs-5"></i>
                        <span>SEO Meta Ayarları</span>
                    </h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">SEO Başlığı (Meta Title)</label>
                            <input type="text" name="seo_title" class="form-control rbn-form-input rounded-3 py-2 shadow-sm"
                                placeholder="Boş bırakılırsa yazı başlığı kullanılır." value="{{ $post['seo_title'] ?? '' }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">SEO Açıklaması (Meta Description)</label>
                            <textarea name="seo_description" class="form-control rbn-form-input rounded-3 p-3 shadow-sm"
                                rows="2" placeholder="Arama motorlarında listelenecek açıklama...">{{ $post['seo_description'] ?? '' }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">SEO Anahtar Kelimeleri (Keywords)</label>
                            <input type="text" name="seo_keywords" class="form-control rbn-form-input rounded-3 py-2 shadow-sm"
                                placeholder="Virgülle ayırarak yazın (örn: su arıtma, sağlıklı su, filtre değişimi)" value="{{ $post['seo_keywords'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($hasSocial))
            <!-- SOCIAL MEDIA CARD -->
            <div class="rbn-table-wrap mb-4">
                <div class="ra-traffic-card-header">
                    <h6 class="ra-traffic-card-title mb-0">
                        <i class="ri-share-forward-line text-warning fs-5"></i>
                        <span>Sosyal Medya Paylaşım Metinleri</span>
                    </h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Sosyal Medya Paylaşım Metni (Çok Paragraflı Gönderi Açıklaması)</label>
                            <textarea name="social_summary" class="form-control rbn-form-input rounded-3 p-3 shadow-sm"
                                rows="5" style="line-height: 1.5;" placeholder="Sosyal medyada paylaşılacak merak uyandırıcı, çok paragraflı ve etkileşim odaklı metin...">{{ $post['social_summary'] ?? '' }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small">Sosyal Medya Etiketleri (Hashtags)</label>
                            <input type="text" name="social_hashtags" class="form-control rbn-form-input rounded-3 py-2 shadow-sm"
                                placeholder="#etiket1 #etiket2 #etiket3" value="{{ $post['social_hashtags'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- RBN IMAGE UPLOAD & PREVIEW CARD -->
            <div class="rbn-table-wrap mb-4" data-rbn-image-preview="post-image">
                <div class="ra-traffic-card-header">
                    <h6 class="ra-traffic-card-title mb-0">
                        <i class="ri-image-line text-warning fs-5"></i>
                        <span>Yazı Görseli</span>
                    </h6>
                </div>
                <div class="p-4 text-center">
                    <div class="image-preview-area mb-3 rounded-3 d-flex align-items-center justify-content-center overflow-hidden position-relative"
                        style="height: 200px; background: var(--rbn-admin-surface, #fbf9f5); border: 1px dashed var(--rbn-admin-border-subtle, #e5ded3);">
                        <img id="post-image-preview"
                            src="{{ !empty($post['image']) ? $post['image'] : '' }}"
                            class="w-100 h-100 object-fit-cover {{ empty($post['image']) ? 'd-none' : '' }}">

                        <div id="post-image-placeholder" class="{{ !empty($post['image']) ? 'd-none' : '' }}">
                            <i class="ri-upload-cloud-line fs-1 text-muted opacity-25"></i>
                            <p class="text-muted small mt-2">Önerilen: 800x450px (16:9)<br>PNG, JPG veya WEBP</p>
                        </div>
                    </div>

                    <div class="input-group d-flex flex-column gap-2">
                        <input type="hidden" name="image_url" id="post-image-url-field" value="{{ $post['image'] ?? '' }}">
                        <input type="hidden" name="image_prompt" id="post-image-prompt-field" value="{{ $post['image_prompt'] ?? '' }}">
                        <input type="file" name="image" class="form-control d-none" id="post-image-file"
                            accept="image/*">
                        <button type="button"
                            class="rbn-btn rbn-btn-outline w-100 shadow-sm py-2 d-flex align-items-center justify-content-center gap-2"
                            onclick="document.getElementById('post-image-file').click()">
                            <i class="ri-upload-2-line"></i> {{ !empty($post['image']) ? 'Görseli Değiştir' : 'Görsel Yükle' }}
                        </button>
                        <button type="button" id="btn-generate-ai-image"
                            class="rbn-btn rbn-btn-outline w-100 shadow-sm py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold"
                            data-rbn-ai="image"
                            data-url="{{ $Route->url('admin.studio.posts.generate-image') }}"
                            data-source-id="input[name='id']"
                            data-source-title="input[name='title']"
                            data-source-category="#post_category_id"
                            data-source-project="input[name='project_key']"
                            data-source-prompt="#post-image-prompt-field"
                            data-target-input="#post-image-url-field"
                            data-target-preview="#post-image-preview"
                            data-target-placeholder="#post-image-placeholder">
                            <i class="ri-sparkling-fill text-warning"></i> Yapay Zeka ile Görsel Üret
                        </button>
                    </div>
                </div>
            </div>

            <!-- SETTINGS CARD -->
            <div class="rbn-table-wrap mb-4">
                <div class="ra-traffic-card-header">
                    <h6 class="ra-traffic-card-title mb-0">
                        <i class="ri-settings-4-line text-warning fs-5"></i>
                        <span>Yayın Ayarları</span>
                    </h6>
                </div>
                <div class="p-4">
                    <label class="form-label fw-bold text-muted small">Yayın Durumu</label>
                    @php
                        $isActive = ($post['is_active'] ?? 1) == 1;
                    @endphp
                    <div class="rbn-dropdown dropdown rbn-modal-dropdown w-100">
                        <input type="hidden" name="is_active" id="post_is_active" value="{{ $isActive ? '1' : '0' }}" required>
                        <button class="rbn-form-input form-control w-100 d-flex justify-content-between align-items-center dropdown-toggle text-start" 
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="selected-text font-weight-500">
                                {{ $isActive ? '🟢 Aktif (Yayında)' : '⚪ Pasif (Taslak)' }}
                            </span>
                            <i class="ri-arrow-down-s-line opacity-50"></i>
                        </button>
                        <ul class="rbn-dropdown-menu dropdown-menu shadow-lg py-2 w-100 mt-1">
                            <li>
                                <a class="rbn-dropdown-item dropdown-item py-2 d-flex align-items-center justify-content-between {{ $isActive ? 'active' : '' }}"
                                    href="javascript:void(0)" data-value="1" data-label="🟢 Aktif (Yayında)">
                                    <span>🟢 Aktif (Yayında)</span>
                                    @if($isActive)
                                        <i class="ri-check-line text-warning"></i>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a class="rbn-dropdown-item dropdown-item py-2 d-flex align-items-center justify-content-between {{ !$isActive ? 'active' : '' }}"
                                    href="javascript:void(0)" data-value="0" data-label="⚪ Pasif (Taslak)">
                                    <span>⚪ Pasif (Taslak)</span>
                                    @if(!$isActive)
                                        <i class="ri-check-line text-warning"></i>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex flex-column gap-2">
                        <button type="submit" class="rbn-btn rbn-btn-primary w-100 py-2.5 shadow-sm fw-bold d-flex align-items-center justify-content-center gap-2">
                            <i class="ri-check-line"></i> {{ isset($post['id']) && $post['id'] > 0 ? 'Güncellemeleri Kaydet' : 'Yazıyı Yayınla' }}
                        </button>

                        <a href="{{ $Route->url('admin.studio.posts.index') }}" class="rbn-btn rbn-btn-outline w-100 py-2 shadow-sm text-decoration-none text-center d-flex align-items-center justify-content-center gap-2">
                            <i class="ri-arrow-left-line"></i> Geri Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
