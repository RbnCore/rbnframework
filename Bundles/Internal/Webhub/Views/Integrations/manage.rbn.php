<!-- TABLO ÜSTÜ: YENİ EKLE -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1">
    <h6 class="fw-bolder mb-0 text-dark">
        <i class="bi bi-layers-half me-2 text-primary"></i>Entegrasyon Mimarisi
        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill ms-1 small">{{ count($settings) }}</span>
    </h6>
    <button type="button" class="btn btn-sm btn-primary rounded-pill shadow-sm px-4 d-flex align-items-center"
        data-rbn-modal="true" data-title="Yeni Entegrasyon Alanı Tanımla" data-theme="primary" data-size="lg"
        data-type="webhub/integrations">
        <i class="bi bi-plus-lg me-1"></i> Yeni Alan Tanımla
    </button>
</div>

<!-- TABLO -->
<div class="rbn-table-container mb-5 shadow-sm border-0">
    <div class="table-responsive">
        <table class="rbn-table align-middle mb-0" id="integrations-manage-table" data-rbn-sortable="true"
            data-sort-url="{{ $Route->url('webhub/integrations/reorder', 'developer') }}">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th data-sort="string">GÖRÜNÜR AD (TR / EN)</th>
                    <th data-sort="string">ANAHTAR (KEY)</th>
                    <th data-sort="string">GİRİŞ TİPİ</th>
                    <th data-sort="string">YETKİ</th>
                    <th data-sort="string">DURUM</th>
                    <th data-sort="date">GÜNCELLEME</th>
                    <th class="pe-4 text-end" style="width: 120px;">İŞLEMLER</th>
                </tr>
            </thead>
            <tbody>
                @if (empty($settings))
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted border-0">
                        <div class="py-4">
                            <i class="bi bi-layers fs-1 opacity-25 d-block mb-3"></i>
                            <h6 class="fw-bold">Henüz mimari bir tanım yapılmamış</h6>
                        </div>
                    </td>
                </tr>
                @else
                @foreach ($settings as $item)
                <tr class="rbn-card-technic" data-id="{{ $item['id'] }}">
                    <td class="ps-4" style="width: 40px;">
                        <i class="bi bi-grip-vertical text-muted sortable-handle opacity-50 fs-5"></i>
                    </td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-dark small">{{ $item['label_tr'] ?? '---' }}</span>
                            <span class="text-muted" style="font-size: 0.7rem;">{{ $item['label_en'] ??
                                $item['setting_key'] }}</span>
                        </div>
                    </td>
                    <td><code class="small text-primary fw-bold">{{ $item['setting_key'] }}</code></td>
                    <td>
                        <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">
                            <i class="bi bi-input-cursor-text me-1 opacity-50"></i>
                            {{ !empty($item['field_type']) ? $item['field_type'] : 'empty' }}
                        </span>
                    </td>
                    <td>
                        <?php
                        $roleClass = $item['required_role'] === 'developer' ? 'bg-danger text-white' : 'bg-info text-dark';
                        ?>
                        <span class="badge {{ $roleClass }} rounded-pill px-2 py-1 small" style="font-size: 0.65rem;">
                            {{ strtoupper($item['required_role'] ?? 'user') }}
                        </span>
                    </td>
                    <td>
                        <div class="form-check form-switch text-center">
                            <input class="form-check-input ajax-status-toggle" type="checkbox" role="switch"
                                data-id="{{ $item['id'] }}"
                                data-url="{{ $Route->url('webhub/integrations/toggle', 'developer') }}" data-reload="false"
                                {{ $item['is_active'] ? 'checked' : '' }}>
                        </div>
                    </td>
                    <td class="text-muted small font-monospace">{{ now('d.m.Y H:i', strtotime($item['updated_at'])) }}
                    </td>
                    <td class="pe-4 text-end">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-icon btn-light rounded-pill shadow-sm border me-1"
                                data-rbn-modal="true" data-title="Alan Yapılandırmasını Düzenle" data-theme="dark"
                                data-size="lg" data-type="webhub/integrations" data-id="{{ $item['id'] }}"
                                data-bs-toggle="tooltip" title="Yapılandırmayı Düzenle">
                                <i class="bi bi-pencil-square text-primary"></i>
                            </button>
                            <button
                                class="btn btn-sm btn-icon btn-light text-danger rounded-pill shadow-sm border action-confirm"
                                data-url="{{ $Route->url('webhub/integrations/delete/' . $item['id'], 'developer') }}"
                                data-method="POST" data-ajax="true" data-title="Entegrasyon Alanını Mimari'den Sil?"
                                data-text="{{ $item['label_tr'] ?? $item['setting_key'] }} alanı tamamen silinecek. Emin misiniz?"
                                data-bs-toggle="tooltip" title="Sil">
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
