<!-- Hallmark · component: rbn-user-management · genre: modern-minimal · theme: rbn-theme -->
<div class="container-fluid px-0">

    <!-- 1. TOP STATS METRICS & ACTION BAR (Terracotta ra-stat-* Standardı) 📊 -->
    <div class="row g-3 mb-4">
        <!-- Toplam Kullanıcı -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-navy h-100 p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-team-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Toplam Kullanıcı</div>
                        <h4 class="ra-stat-value fs-5 mb-0 text-dark">
                            {{ number_format($stats['total'] ?? 0) }}
                        </h4>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-database-2-line me-1 text-warning"></i>Kayıtlı Havuz</span>
                    <strong class="text-dark">Sistem Geneli</strong>
                </div>
            </div>
        </div>

        <!-- Aktif Kullanıcılar -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-success h-100 p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-user-follow-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Aktif Hesaplar</div>
                        <h4 class="ra-stat-value fs-5 mb-0 text-success">
                            {{ number_format($stats['active'] ?? 0) }}
                        </h4>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-checkbox-circle-line me-1 text-success"></i>Erişilebilir</span>
                    <strong class="text-success fw-bold">Sorunsuz</strong>
                </div>
            </div>
        </div>

        <!-- Pasif / Yasaklı -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-terracotta h-100 p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-user-forbid-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Pasif / Yasaklı</div>
                        <h4 class="ra-stat-value fs-5 mb-0 text-danger">
                            {{ number_format(($stats['inactive'] ?? 0) + ($stats['banned'] ?? 0) + ($stats['pending'] ?? 0)) }}
                        </h4>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-shield-cross-line me-1 text-danger"></i>Kısıtlanan</span>
                    <strong class="text-dark">Askıda</strong>
                </div>
            </div>
        </div>

        <!-- Hızlı Aksiyon: Yeni Kullanıcı -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-warning h-100 p-3 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-user-add-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Yeni Kayıt</div>
                        <div class="fw-bold text-dark small">Hesap Oluştur</div>
                    </div>
                </div>
                <div class="border-top mt-auto pt-2">
                    <button type="button"
                        class="btn rbn-btn-taupe btn-sm w-100 py-1 fw-semibold d-flex align-items-center justify-content-center gap-2"
                        data-rbn-modal="true" data-type="add" data-title="Yeni Kullanıcı Ekle"
                        data-endpoint="{{ $Route->url('users/modal', 'admin', '') }}">
                        <i class="ri-add-line"></i> Kullanıcı Ekle
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. FİLTRELER & ARAMA ÇUBUĞU (Orijinal rbn-table-filters Standardı) 🔍 -->
    <div class="row g-3 mb-4 rbn-table-filters align-items-center">
        <!-- Arama -->
        <div class="col-md-6">
            <form action="{{ $Route->url('users', 'admin') }}" method="GET" class="m-0">
                <div class="position-relative">
                    <input type="text" name="search" class="rbn-form-input form-control" placeholder="İsim, kullanıcı adı veya e-posta ile ara..."
                        value="{{ $search }}" data-rbn-table-search="users-table">
                    <i class="ri-search-line search-icon"></i>
                </div>
            </form>
        </div>

        <!-- Durum Filtresi -->
        <div class="col-md-3">
            @php
            $statusNames = [
                '' => 'Tüm Durumlar',
                'active' => 'Aktif Üyeler',
                'inactive' => 'Pasif Üyeler',
                'banned' => 'Yasaklılar'
            ];
            $currentStatusName = $statusNames[$currentStatus] ?? 'Tüm Durumlar';
            @endphp
            <div class="rbn-dropdown dropdown w-100 rbn-filter-dropdown">
                <button class="rbn-btn rbn-btn-outline w-100 justify-content-between dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span>
                        <i class="ri-filter-3-line me-2 opacity-50"></i>
                        {{ $currentStatusName }}
                    </span>
                    <i class="ri-arrow-down-s-line opacity-50"></i>
                </button>
                <ul class="rbn-dropdown-menu dropdown-menu shadow-sm py-2 w-100 mt-2">
                    @foreach($statusNames as $key => $name)
                    <li>
                        <a class="rbn-dropdown-item dropdown-item py-2 {{ (string)$currentStatus === (string)$key ? 'active' : '' }}"
                            data-rbn-table-filter="users-table" data-filter-param="status" data-filter-value="{{ $key }}"
                            href="javascript:void(0)">
                            {{ $name }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Rol Filtresi -->
        <div class="col-md-3">
            @php
            $roleNames = [
                '' => 'Tüm Roller',
                'admin' => 'Yöneticiler',
                'editor' => 'Editörler',
                'user' => 'Kullanıcılar'
            ];
            $currentRoleName = $roleNames[$currentRole] ?? 'Tüm Roller';
            @endphp
            <div class="rbn-dropdown dropdown w-100 rbn-filter-dropdown">
                <button class="rbn-btn rbn-btn-outline w-100 justify-content-between dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span>
                        <i class="ri-shield-user-line me-2 opacity-50"></i>
                        {{ $currentRoleName }}
                    </span>
                    <i class="ri-arrow-down-s-line opacity-50"></i>
                </button>
                <ul class="rbn-dropdown-menu dropdown-menu shadow-sm py-2 w-100 mt-2">
                    @foreach($roleNames as $key => $name)
                    <li>
                        <a class="rbn-dropdown-item dropdown-item py-2 {{ (string)$currentRole === (string)$key ? 'active' : '' }}"
                            data-rbn-table-filter="users-table" data-filter-param="role" data-filter-value="{{ $key }}"
                            href="javascript:void(0)">
                            {{ $name }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- 3. KULLANICILAR TABLOSU (.rbn-table-wrap Mimarisi) 📋 -->
    <div class="rbn-table-wrap" id="users-table-wrap">
        <table class="rbn-table rbn-table-hover" id="users-table"
            data-rbn-table="true"
            data-url="{{ $Route->url('users', 'admin') }}"
            data-current-page="{{ isset($pager) ? $pager->currentPage() : 1 }}"
            data-total-pages="{{ isset($pager) ? $pager->lastPage() : 1 }}"
            data-per-page="15">
            <thead>
                <tr>
                    <th class="ps-4">Kullanıcı</th>
                    <th>Rol</th>
                    <th>Durum</th>
                    <th>Kayıt Tarihi</th>
                    <th class="text-end pe-4">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($users))
                    @foreach($users as $u)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rbn-avatar rbn-avatar-sm rbn-avatar-terracotta">
                                    {{ mb_strtoupper(mb_substr($u['name'] ?? $u['username'], 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 0.875rem;">{{ $u['name'] ?? $u['username'] }}</div>
                                    <div class="text-muted small" style="font-size: 0.75rem;"><span class="text-dark opacity-75">@{{ $u['username'] }}</span> &bull; {{ $u['email'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="rbn-badge rbn-badge-secondary rbn-badge-sm">
                                {{ ucfirst($u['role'] ?? 'user') }}
                            </span>
                        </td>
                        <td>
                            @if(($u['status'] ?? 'active') === 'active')
                                <span class="rbn-badge rbn-badge-success rbn-badge-sm">
                                    <span class="rbn-status-dot rbn-status-dot-emerald me-1"></span> Aktif
                                </span>
                            @elseif(($u['status'] ?? '') === 'banned')
                                <span class="rbn-badge rbn-badge-danger rbn-badge-sm">
                                    <span class="rbn-status-dot rbn-status-dot-red me-1"></span> Yasaklı
                                </span>
                            @else
                                <span class="rbn-badge rbn-badge-warning rbn-badge-sm">
                                    <span class="rbn-status-dot rbn-status-dot-amber me-1"></span> Pasif
                                </span>
                            @endif
                        </td>
                        <td class="text-muted small font-monospace">
                            <i class="ri-calendar-line me-1 text-warning"></i>
                            {{ !empty($u['created_at']) ? date('d.m.Y H:i', strtotime($u['created_at'])) : '-' }}
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-inline-flex gap-2">
                                <button type="button" class="ra-table-btn"
                                    data-rbn-modal="true" data-type="edit" data-title="Kullanıcı Düzenle"
                                    data-endpoint="{{ $Route->url('users/modal/' . $u['id'], 'admin', '') }}"
                                    data-tooltip="Düzenle">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button type="button" class="ra-table-btn ra-table-btn-danger action-confirm"
                                    data-url="{{ $Route->url('users/delete/' . $u['id'], 'admin', '') }}"
                                    data-method="POST"
                                    data-title="Kullanıcı Silinsin mi?"
                                    data-text="{{ $u['name'] ?? $u['username'] }} kullanıcısı sistemden kalıcı olarak silinecektir."
                                    data-tooltip="Sil">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="ri-user-search-line fs-1 d-block mb-2 text-warning opacity-50"></i>
                            Kullanıcı bulunamadı.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

</div>