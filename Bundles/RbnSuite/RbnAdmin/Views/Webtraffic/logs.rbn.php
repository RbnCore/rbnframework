<!-- Detailed Webtraffic Logs Component (Terracotta Craft Mimarisi) 📋📊 -->
<div class="rbn-table-wrap mb-5">
    <!-- Header & Filtre Alanı -->
    <div class="ra-traffic-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h6 class="ra-traffic-card-title mb-0">
                <i class="ri-history-line text-warning fs-5"></i> Ziyaretçi Hareketleri:
                <span class="text-dark fw-bold">
                    {{ $selectedDate == now('Y-m-d') ? 'Bugün' : $this->helper('format')->formatDateTurkish($selectedDate, 'medium') }}
                </span>
            </h6>
            <span class="rbn-badge rbn-badge-terracotta px-2 py-1 small">
                {{ number_format($paginator->total()) }} Ziyaret
            </span>
        </div>

        <!-- Filtre Kontrolleri (Evrensel rbnTable & rbn-btn-outline Mimarisi) -->
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <!-- Trafik Türü Dropdown -->
            @php
            $typeNames = [
                'all' => 'Tüm Trafik',
                'organic' => 'Sadece Organik',
                'bot' => 'Sadece Botlar'
            ];
            $currentTypeName = $typeNames[$visitorType ?? 'all'] ?? 'Tüm Trafik';
            @endphp
            <div class="d-flex align-items-center gap-2">
                <label class="ra-stat-label mb-0">Trafik:</label>
                <div class="rbn-dropdown dropdown rbn-filter-dropdown">
                    <button class="rbn-btn rbn-btn-outline btn-sm dropdown-toggle d-flex align-items-center justify-content-between gap-2 px-3 py-1"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 140px;">
                        <span data-filter-current-label>{{ $currentTypeName }}</span>
                        <i class="ri-arrow-down-s-line opacity-50"></i>
                    </button>
                    <ul class="rbn-dropdown-menu dropdown-menu shadow-lg py-2 mt-2">
                        @foreach($typeNames as $key => $name)
                        <li>
                            <a class="rbn-dropdown-item dropdown-item py-2 {{ ($visitorType ?? 'all') === $key ? 'active' : '' }}"
                                data-rbn-table-filter="visitor-logs-table" data-filter-param="type" data-filter-value="{{ $key }}"
                                data-filter-label="{{ $name }}"
                                href="javascript:void(0)">
                                {{ $name }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Tarih Filtresi Dropdown (Terracotta Craft Hızlı Tarih Seçici) -->
            @php
            $datePresets = [
                now('Y-m-d') => 'Bugün',
                date('Y-m-d', strtotime('-1 day')) => 'Dün',
                date('Y-m-d', strtotime('-2 days')) => $this->helper('format')->formatDateTurkish(date('Y-m-d', strtotime('-2 days')), 'medium'),
                date('Y-m-d', strtotime('-3 days')) => $this->helper('format')->formatDateTurkish(date('Y-m-d', strtotime('-3 days')), 'medium'),
                date('Y-m-d', strtotime('-7 days')) => '1 Hafta Önce'
            ];
            $currentDateLabel = $selectedDate == now('Y-m-d') ? 'Bugün' : $this->helper('format')->formatDateTurkish($selectedDate, 'medium');
            @endphp
            <div class="d-flex align-items-center gap-2">
                <label class="ra-stat-label mb-0">Tarih:</label>
                <div class="rbn-dropdown dropdown rbn-filter-dropdown">
                    <button class="rbn-btn rbn-btn-outline btn-sm dropdown-toggle d-flex align-items-center justify-content-between gap-2 px-3 py-1"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 155px;">
                        <span data-filter-current-label>
                            <i class="ri-calendar-event-line me-1 text-warning"></i>
                            {{ $currentDateLabel }}
                        </span>
                        <i class="ri-arrow-down-s-line opacity-50"></i>
                    </button>
                    <ul class="rbn-dropdown-menu dropdown-menu dropdown-menu-end shadow-lg py-2 mt-2" style="min-width: 220px;">
                        <li class="dropdown-header text-uppercase fs-xs fw-bold text-muted px-3 py-1">Hızlı Tarih Seçimi</li>
                        @foreach($datePresets as $dVal => $dLabel)
                        <li>
                            <a class="rbn-dropdown-item dropdown-item py-2 {{ $selectedDate === $dVal ? 'active' : '' }}"
                                data-rbn-table-filter="visitor-logs-table" data-filter-param="date" data-filter-value="{{ $dVal }}"
                                data-filter-label="{{ $dLabel }}"
                                href="javascript:void(0)">
                                {{ $dLabel }}
                            </a>
                        </li>
                        @endforeach
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="px-3 py-2">
                            <label class="ra-stat-label d-block mb-1">Özel Tarih Belirle:</label>
                            <input type="date"
                                class="form-control form-control-sm font-monospace"
                                value="{{ $selectedDate }}" max="{{ now('Y-m-d') }}"
                                data-rbn-table-filter="visitor-logs-table" data-filter-param="date">
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablo Alanı -->
    <div class="table-responsive">
        <table class="rbn-table rbn-table-hover rbn-table-fixed align-middle mb-0" id="visitor-logs-table">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 140px;">Tarih</th>
                    <th style="width: 100px;">Saat</th>
                    <th style="width: 180px;">IP / Lokasyon</th>
                    <th>Ziyaret Edilen URL</th>
                    <th class="pe-4" style="width: 220px;">Cihaz / Tarayıcı</th>
                </tr>
            </thead>
            <tbody>
                @if($paginator && $paginator->total() > 0)
                @foreach($paginator->items() as $hit)
                @php
                $rawTime = $hit['time'] ?? '';
                if (strlen($rawTime) > 8) {
                    $datePart = date('Y-m-d', strtotime($rawTime));
                    $timePart = date('H:i:s', strtotime($rawTime));
                } else {
                    $datePart = $selectedDate;
                    $timePart = $rawTime;
                }
                $formattedDate = $this->helper('format')->formatDateTurkish($datePart, 'medium');
                $uaInfo = $this->helper('format')->parseUserAgent($hit['ua'] ?? '');
                @endphp
                <tr>
                    <td class="ps-4">
                        <span class="small fw-bold text-dark">{{ $formattedDate }}</span>
                    </td>
                    <td>
                        <span class="ra-traffic-time-badge">{{ $timePart }}</span>
                    </td>
                    <td>
                        <span class="font-monospace text-dark fw-bold d-block text-truncate" title="{{ $hit['ip'] }}">{{ $hit['ip'] }}</span>
                        @if(!empty($hit['location']))
                            @if(str_starts_with($hit['location'], 'Bot'))
                            <span class="rbn-badge rbn-badge-secondary rbn-badge-xs mt-1 d-inline-block">
                                <i class="ri-robot-2-line me-1"></i>{{ $hit['location'] }}
                            </span>
                            @else
                            <span class="rbn-badge rbn-badge-terracotta rbn-badge-xs mt-1 d-inline-block">
                                @if(!empty($hit['flagClass']))
                                    <span class="{{ $hit['flagClass'] }} me-1"></span>
                                @else
                                    <i class="ri-map-pin-2-fill me-1"></i>
                                @endif
                                {{ $hit['location'] }}
                            </span>
                            @endif
                        @endif
                    </td>
                    <td class="ra-traffic-url-cell">
                        <span class="font-monospace small text-dark ra-traffic-url-text" title="{{ htmlspecialchars($hit['url']) }}">{{ $hit['url'] }}</span>
                    </td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="ra-traffic-browser-pill" title="{{ htmlspecialchars($uaInfo['raw']) }}">
                                <i class="{{ $uaInfo['icon'] }} text-warning"></i>
                                <span>{{ $uaInfo['browser'] }}</span>
                            </span>
                            <span class="badge bg-light text-muted border small">{{ $uaInfo['os'] }}</span>
                        </div>
                    </td>
                </tr>
                @endforeach
                @else
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="ri-pulse-line fs-1 text-warning opacity-50 d-block mb-2"></i>
                        <span class="small">Bu tarihte hiç veri bulunamadı.</span>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Sayfalama (Pagination) -->
    @if($paginator && $paginator->hasPages())
    <div class="p-3 border-top border-light d-flex justify-content-center">
        {!! $paginator->links() !!}
    </div>
    @endif
</div>