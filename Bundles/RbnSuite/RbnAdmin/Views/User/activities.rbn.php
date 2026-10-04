<!-- Hallmark · component: rbn-user-activities · genre: terracotta-editorial · theme: rbn-theme -->
<div class="container-fluid px-0">

    <!-- 1. TOP STATS METRICS & ACTION BAR (Terracotta ra-stat-* Standardı) 📊 -->
    <div class="row g-3 mb-4">
        <!-- Toplam Kayıt -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-navy h-100 p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-list-check-2"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Toplam Kayıt</div>
                        <h4 class="ra-stat-value fs-5 mb-0 text-dark">
                            {{ number_format($stats['total'] ?? 0) }}
                        </h4>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-database-2-line me-1 text-warning"></i>Kayıt Havuzu</span>
                    <strong class="text-dark">Sistem Geneli</strong>
                </div>
            </div>
        </div>

        <!-- Bugün -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-success h-100 p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-calendar-check-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Bugün</div>
                        <h4 class="ra-stat-value fs-5 mb-0 text-success">
                            {{ number_format($stats['today'] ?? 0) }}
                        </h4>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-time-line me-1 text-success"></i>Son 24 Saat</span>
                    <strong class="text-success fw-bold">Canlı Akış</strong>
                </div>
            </div>
        </div>

        <!-- Sistem Hatası -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-warning h-100 p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-error-warning-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Sistem / Hata</div>
                        <h4 class="ra-stat-value fs-5 mb-0 text-warning">
                            {{ number_format($stats['system_errors'] ?? 0) }}
                        </h4>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-shield-cross-line me-1 text-danger"></i>Hata Takibi</span>
                    <strong class="text-dark">Kayıt Altında</strong>
                </div>
            </div>
        </div>

        <!-- Hızlı Aksiyon: Logları Temizle -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-terracotta h-100 p-3 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-delete-bin-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Geçmişi Sıfırla</div>
                        <div class="fw-bold text-dark small">Logları Temizle</div>
                    </div>
                </div>
                <div class="border-top mt-auto">
                    <button type="button"
                        class="btn rbn-btn-secondary btn-sm w-100 py-1 fw-semibold d-flex align-items-center justify-content-center gap-2 action-confirm"
                        data-url="{{ $Route->url('users/activities/clear', 'admin', null) }}"
                        data-method="POST"
                        data-title="Tüm Kayıtlar Silinsin mi?"
                        data-text="Tüm aktivite geçmişi kalıcı olarak temizlenecektir. Bu işlem geri alınamaz.">
                        <i class="ri-flashlight-line"></i> Tümünü Sil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. FİLTRELER & ARAMA ÇUBUĞU (Orijinal rbn-table-filters Standardı) 🔍 -->
    <div class="row g-3 mb-4 rbn-table-filters align-items-center">
        <!-- Arama -->
        <div class="col-md-6">
            <form method="GET" class="m-0">
                <div class="position-relative">
                    <input type="text" name="search" class="rbn-form-input form-control" placeholder="E-posta veya IP adresi ile ara..."
                        value="{{ $search }}" data-rbn-table-search="activities-table">
                    <i class="ri-search-line search-icon"></i>
                </div>
            </form>
        </div>

        <!-- İşlem Türü Filtresi -->
        <div class="col-md-3">
            @php
            $typeNames = [
                'all' => 'Tüm Aktiviteler',
                'successful_login' => 'Başarılı Giriş',
                'failed_login' => 'Hatalı Giriş',
                'successful_logout' => 'Çıkış Yapıldı',
                'system_error' => 'Sistem Hatası'
            ];
            $currentTypeName = $typeNames[$currentType] ?? 'Tüm Aktiviteler';
            @endphp
            <div class="rbn-dropdown dropdown w-100 rbn-filter-dropdown">
                <button class="rbn-btn rbn-btn-outline w-100 justify-content-between dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span>
                        <i class="ri-filter-3-line me-2 opacity-50"></i>
                        {{ $currentTypeName }}
                    </span>
                    <i class="ri-arrow-down-s-line opacity-50"></i>
                </button>
                <ul class="rbn-dropdown-menu dropdown-menu shadow-sm py-2 w-100 mt-2">
                    @foreach($typeNames as $key => $name)
                    <li>
                        <a class="rbn-dropdown-item dropdown-item py-2 {{ $currentType === $key ? 'active' : '' }}"
                            data-rbn-table-filter="activities-table" data-filter-param="type" data-filter-value="{{ $key }}"
                            href="javascript:void(0)">
                            {{ $name }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Sıralama Filtresi -->
        <div class="col-md-3">
            <div class="rbn-dropdown dropdown w-100 rbn-filter-dropdown">
                <button class="rbn-btn rbn-btn-outline w-100 justify-content-between dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span>
                        <i class="ri-sort-desc me-2 opacity-50"></i>
                        Sıralama
                    </span>
                    <i class="ri-arrow-down-s-line opacity-50"></i>
                </button>
                <ul class="rbn-dropdown-menu dropdown-menu shadow-sm py-2 w-100 mt-2">
                    <li><a class="rbn-dropdown-item dropdown-item py-2" href="#" data-rbn-table-sort="activities-table" data-sort-col="4" data-sort-type="date" data-sort-dir="desc"><i class="ri-arrow-down-line me-2 opacity-50"></i>En Yeni Tarih</a></li>
                    <li><a class="rbn-dropdown-item dropdown-item py-2" href="#" data-rbn-table-sort="activities-table" data-sort-col="4" data-sort-type="date" data-sort-dir="asc"><i class="ri-arrow-up-line me-2 opacity-50"></i>En Eski Tarih</a></li>
                    <li><hr class="rbn-dropdown-divider dropdown-divider"></li>
                    <li><a class="rbn-dropdown-item dropdown-item py-2" href="#" data-rbn-table-sort="activities-table" data-sort-col="0" data-sort-type="string" data-sort-dir="asc"><i class="ri-text me-2 opacity-50"></i>E-posta (A-Z)</a></li>
                    <li><a class="rbn-dropdown-item dropdown-item py-2" href="#" data-rbn-table-sort="activities-table" data-sort-col="2" data-sort-type="string" data-sort-dir="asc"><i class="ri-global-line me-2 opacity-50"></i>IP Adresi</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 3. AKTİVİTELER TABLOSU (Sovereign .rbn-table-wrap Mimarisi) 📋 -->
    <div class="rbn-table-wrap" id="activities-table-wrap">
        <table class="rbn-table rbn-table-hover" id="activities-table"
            data-rbn-table="true"
            data-url="{{ $Route->url('users/activities', 'admin') }}"
            data-current-page="{{ isset($pager) ? $pager->currentPage() : 1 }}"
            data-total-pages="{{ isset($pager) ? $pager->lastPage() : 1 }}"
            data-per-page="15">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 28%;">Kullanıcı / E-posta</th>
                    <th class="text-center" style="width: 18%;">İşlem Türü</th>
                    <th class="text-center" style="width: 14%;">IP Adresi</th>
                    <th class="text-center" style="width: 22%;">Cihaz / Tarayıcı</th>
                    <th class="pe-4 text-end" style="width: 18%;">Tarih</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($activities))
                    @foreach($activities as $act)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rbn-avatar rbn-avatar-sm rbn-avatar-terracotta flex-shrink-0">
                                    {{ mb_strtoupper(mb_substr($act['email'] ?? 'S', 0, 1)) }}
                                </div>
                                <div class="fw-bold text-dark text-truncate" title="{{ $act['email'] ?? '' }}">{{ $act['email'] ?? 'Sistem / Anonim' }}</div>
                            </div>
                        </td>
                        <td class="text-center">
                            @php
                            $badgeClass = 'rbn-badge-secondary';
                            $statusDot = 'rbn-status-dot-slate';
                            $label = $act['activity_type'] ?? 'Genel';
                            if ($act['activity_type'] === 'successful_login') {
                                $badgeClass = 'rbn-badge-success';
                                $statusDot = 'rbn-status-dot-emerald';
                                $label = 'Giriş Başarılı';
                            } elseif ($act['activity_type'] === 'failed_login') {
                                $badgeClass = 'rbn-badge-danger';
                                $statusDot = 'rbn-status-dot-red';
                                $label = 'Hatalı Giriş';
                            } elseif ($act['activity_type'] === 'successful_logout') {
                                $badgeClass = 'rbn-badge-secondary';
                                $statusDot = 'rbn-status-dot-amber';
                                $label = 'Çıkış Yapıldı';
                            } elseif ($act['activity_type'] === 'system_error') {
                                $badgeClass = 'rbn-badge-warning';
                                $statusDot = 'rbn-status-dot-amber';
                                $label = 'Sistem Hatası';
                            }
                            @endphp
                            <span class="rbn-badge {{ $badgeClass }} rbn-badge-sm">
                                <span class="rbn-status-dot {{ $statusDot }} me-1"></span> {{ $label }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="rbn-badge rbn-badge-secondary rbn-badge-square font-monospace">
                                {{ $act['ip_address'] ?? '127.0.0.1' }}
                            </span>
                        </td>
                        <td class="text-center">
                            @php
                            $uaInfo = $this->helper('format')->parseUserAgent($act['user_agent'] ?? '');
                            @endphp
                            <div class="d-inline-flex align-items-center gap-2 text-muted small" title="{{ $uaInfo['raw'] }}">
                                <i class="{{ $uaInfo['icon'] }} text-dark opacity-75"></i>
                                <span class="text-dark fw-medium">{{ $uaInfo['browser'] }}</span>
                                <span class="opacity-50">&bull;</span>
                                <span>{{ $uaInfo['os'] }}</span>
                            </div>
                        </td>
                        <td class="pe-4 text-end text-muted small font-monospace">
                            <i class="ri-time-line me-1 text-warning"></i>
                            {{ !empty($act['created_at']) ? date('d.m.Y H:i', strtotime($act['created_at'])) : '-' }}
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="ri-inbox-line fs-1 d-block mb-2 text-warning opacity-50"></i>
                            Aktivite kaydı bulunamadı.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

</div>