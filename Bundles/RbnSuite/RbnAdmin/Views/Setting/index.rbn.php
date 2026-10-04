<!-- Hallmark · component: rbn-settings-management · genre: terracotta-editorial · theme: rbn-theme -->
<form action="{{ $Route->url('settings/save', 'admin', 'dashboard') }}" method="POST" data-ajax="true" data-rbn-form="true">
    @csrf
    <input type="hidden" name="type" value="{{ $currentType }}">
    <input type="hidden" name="project" value="{{ active_project_key() }}">

    <!-- 1. TOP STATS METRICS & ACTION BAR (Terracotta ra-stat-* Standardı) 📊 -->
    <div class="row g-3 mb-4">
        <!-- Kategori Sayısı -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-navy h-100 p-3 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-grid-fill"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Kategori Havuzu</div>
                        <div class="fw-bold text-dark fs-5 mt-1">
                            {{ count($groups ?? []) }}
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-sound-module-line me-1 text-warning"></i>Modül Grubu</span>
                    <strong class="text-dark">Sistem Geneli</strong>
                </div>
            </div>
        </div>

        <!-- Seçili Grup -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-warning h-100 p-3 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-equalizer-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Aktif Kategori</div>
                        <div class="fw-bold text-dark small text-truncate mt-1" title="{{ $activeGroup['group_label'] ?? 'Genel' }}">
                            {{ $activeGroup['group_label'] ?? 'Genel' }}
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-price-tag-3-line me-1 text-warning"></i>Grup Kodu</span>
                    <strong class="text-dark">{{ strtoupper((string) $currentType) }}</strong>
                </div>
            </div>
        </div>

        <!-- Parametre Sayısı -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-success h-100 p-3 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-check-double-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Parametre Sayısı</div>
                        <div class="fw-bold text-success fs-5 mt-1">
                            {{ count($settings ?? []) }}
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-shield-check-line me-1 text-success"></i>Aktif Ayar</span>
                    <strong class="text-success fw-bold">Doğrulanmış</strong>
                </div>
            </div>
        </div>

        <!-- Hızlı Kaydet Aksiyon Kartı -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-terracotta h-100 p-3 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-save-3-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Değişiklikleri İşle</div>
                        <div class="fw-bold text-dark small mt-1">Ayarları Kaydet</div>
                    </div>
                </div>
                <div class="border-top mt-auto pt-2">
                    <button type="submit"
                        class="btn rbn-btn-taupe btn-sm w-100 py-1 fw-semibold d-flex align-items-center justify-content-center gap-2">
                        <i class="ri-checkbox-circle-line"></i> Ayarları Güncelle
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. AYAR PANELI VE KATEGORİ GEZİNTİSİ ⚙️ -->
    <div class="row g-4">
        <!-- MODÜL NAVİGASYONU (SOL) -->
        <div class="col-lg-4 col-xl-3">
            <div class="card border-0 shadow-sm overflow-hidden mb-4">
                <div class="p-4 border-bottom border-light">
                    <h6 class="fw-bold mb-0 text-dark text-uppercase d-flex align-items-center gap-2">
                        <i class="ri-list-settings-line text-warning fs-5"></i> Ayar Kategorileri
                    </h6>
                </div>
                <div class="p-3">
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($groups as $group): ?>
                            <?php $isActive = ($currentType === $group['group_key']); ?>
                            <a href="{{ $Route->url('settings/' . $group['group_key'], 'admin', 'dashboard') }}"
                                class="p-3 rounded-3 d-flex align-items-center justify-content-between text-decoration-none rbn-settings-nav-item {{ $isActive ? 'active' : '' }}">
                                <div class="d-flex align-items-center me-2">
                                    <i class="<?= str_replace(['bi bi-', 'bi-'], 'ri-', $group['group_icon'] ?? 'ri-settings-3-line') ?> fs-5 me-3 {{ $isActive ? 'text-warning' : 'text-muted' }}"></i>
                                    <div>
                                        <span class="small d-block {{ $isActive ? 'fw-bold text-dark' : 'fw-semibold text-dark' }}">{{ $group['group_label'] }}</span>
                                        <?php if (!empty($badgeCounts[$group['group_key']])): ?>
                                            <span class="rbn-badge {{ $isActive ? 'rbn-badge-terracotta' : 'rbn-badge-secondary' }} rbn-badge-xs mt-1">
                                                {{ $badgeCounts[$group['group_key']] }} Parametre
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <i class="ri-arrow-right-s-line {{ $isActive ? 'text-warning fw-bold' : 'text-muted opacity-50' }} small"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- İÇERİK FORMU (SAĞ) -->
        <div class="col-lg-8 col-xl-9">
            <div class="card border-0 shadow-sm overflow-hidden mb-4">
                <div class="p-4 border-bottom border-light d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">{{ $activeGroup['group_label'] ?? 'Ayar Grubu' }}</h5>
                        <p class="text-muted small mb-0">{{ ($activeGroup['group_description'] ?? null) ?: 'Gruba ait temel yapılandırma parametreleri.' }}</p>
                    </div>
                    <span class="rbn-badge rbn-badge-terracotta px-3 py-2 fw-semibold">
                        <i class="ri-price-tag-3-line me-1"></i> {{ strtoupper((string) $currentType) }}
                    </span>
                </div>

                <div class="p-4">
                    <div class="settings-list d-flex flex-column gap-3">
                        <?php foreach ($settings as $item): ?>
                            <div class="p-4 rounded-4 rbn-setting-row-box">
                                <div class="row align-items-center g-3">
                                    <div class="col-md-4">
                                        <div class="d-flex flex-column">
                                            <h6 class="fw-bold text-dark mb-1">{{ $item['label_tr'] }}</h6>
                                            <div class="mt-1">
                                                <span class="rbn-badge rbn-badge-secondary rbn-badge-square font-monospace rbn-badge-xs">
                                                    {{ $item['setting_key'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="setting-field-control">
                                            {!! $this->helper('renderField')->renderSetting($item, ['class' => 'rbn-form-input form-control shadow-none']) !!}

                                            <?php if (!empty($item['help_text_tr'])): ?>
                                                <div class="small text-muted mt-2 d-flex align-items-start gap-1">
                                                    <i class="ri-information-line text-warning me-1 mt-1 opacity-75"></i>
                                                    <span>{{ $item['help_text_tr'] }}</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($settings)): ?>
                            <div class="text-center py-5 text-muted">
                                <div class="rbn-stat-icon mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.5rem;">
                                    <i class="ri-inbox-line"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Ayar Bulunamadı</h6>
                                <p class="small text-muted mb-0">Bu kategoride henüz tanımlanmış bir ayar parametresi bulunmuyor.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>