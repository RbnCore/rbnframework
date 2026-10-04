<!-- Ambient Glow & Stage Background -->
<div class="rbn-auth-stage-bg"></div>

<!-- Floating Top Brand Header -->
<div class="rbn-auth-stage-brand">
    <a href="/" class="rbn-auth-brand-pill">
        <div class="rbn-auth-brand-icon text-warning">
            <i class="fas fa-user-shield"></i>
        </div>
        <span class="rbn-auth-brand-name fw-bold"><?= htmlspecialchars((string)($userName ?? 'Kullanıcı')) ?></span>
        <span class="rbn-auth-version-tag badge bg-warning text-dark font-monospace fw-bold"><?= strtoupper(htmlspecialchars((string)($userRole ?? 'USER'))) ?></span>
    </a>
</div>

<!-- Extra Large Studio Card -->
<div class="rbn-auth-stage-card text-start" style="max-width: 960px; padding: 3rem; background: #0f172a; border: 1px solid #334155; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">

    <!-- Top Identity Banner -->
    <div class="d-flex align-items-center justify-content-between pb-4 mb-4 border-bottom" style="border-color: #334155 !important;">
        <div>
            <div class="font-monospace text-uppercase mb-2 text-warning fw-bold small tracking-wider">
                <i class="fas fa-globe me-2"></i><?= strtoupper((string) $domainName) ?> // USER DASHBOARD
            </div>
            <h1 class="display-6 fw-bold text-white mb-2">
                Hoş Geldiniz, <?= htmlspecialchars((string) ($userName ?? 'Kullanıcı')) ?>
            </h1>
            <p class="fs-6 mb-0" style="color: #cbd5e1;">
                <?= htmlspecialchars((string) ($pageDesc ?? 'Sisteme rbnAuth doğrulaması ile başarıyla giriş yapıldı.')) ?>
            </p>
        </div>
        <div class="text-end">
            <span class="badge bg-success text-white px-3 py-2 fs-6 font-monospace shadow-sm">
                <i class="fas fa-check-circle me-1"></i> OTURUM AKTİF
            </span>
        </div>
    </div>

    <!-- Multi-Column Info Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background: #1e293b; border: 1px solid #334155;">
                <div class="small font-monospace mb-1" style="color: #94a3b8;">KULLANICI ID</div>
                <div class="fs-4 fw-bold text-white font-monospace">#<?= htmlspecialchars((string) ($userId ?? 1)) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background: #1e293b; border: 1px solid #334155;">
                <div class="small font-monospace mb-1" style="color: #94a3b8;">ERİŞİM ROLÜ</div>
                <div class="fs-4 fw-bold text-warning font-monospace"><?= strtoupper(htmlspecialchars((string) ($userRole ?? 'USER'))) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background: #1e293b; border: 1px solid #334155;">
                <div class="small font-monospace mb-1" style="color: #94a3b8;">AKTİF ROTA</div>
                <div class="fs-4 fw-bold text-info font-monospace">/user/<?= htmlspecialchars((string) ($userPath ?? 'dashboard')) ?></div>
            </div>
        </div>
    </div>

    <!-- Central High-Contrast Info Box -->
    <div class="p-4 rounded-3 mb-4" style="background: #1e293b; border: 1px solid #475569;">
        <div class="d-flex align-items-start gap-3">
            <div class="p-3 rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); min-width: 52px; min-height: 52px;">
                <i class="fas fa-shield-alt fs-3 text-warning"></i>
            </div>
            <div class="w-100">
                <h3 class="fs-4 fw-bold text-white mb-2"><?= htmlspecialchars((string) ($pageTitle ?? 'Frontend Kontrol Paneli & Modül Rehberi')) ?></h3>
                <p class="fs-6 mb-3 lh-lg" style="color: #e2e8f0;">
                    Burası RBN Framework kullanıcı tarafı (<code class="bg-dark text-warning px-2 py-1 rounded font-monospace">/user/*</code>) varsayılan görünüm alanıdır. Kendi projenize özel kontrolör bağlamak için <code class="bg-dark text-warning px-2 py-1 rounded font-monospace">project-routemap.php</code> dosyanıza <code class="bg-dark text-warning px-2 py-1 rounded font-monospace">'user_dash_controller'</code> anahtarını ekleyebilirsiniz.
                </p>
                <div class="p-3 rounded font-monospace fs-6" style="background: #090d16; border: 1px solid #334155; color: #f59e0b;">
                    <span class="text-white fw-bold me-2"><i class="fas fa-code me-1 text-warning"></i>Örnek Yapılandırma:</span>
                    <code class="text-warning fw-bold">'user_dash_controller' => 'App\Controllers\UserDashboardController@index'</code>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions & Footer -->
    <div class="d-flex align-items-center justify-content-between pt-3 border-top" style="border-color: #334155 !important;">
        <div class="font-monospace small" style="color: #94a3b8;">
            <?= strtoupper((string) $domainName) ?> © <?= date('Y') ?>
        </div>
        <div>
            <a href="/logout" class="btn btn-outline-danger px-4 py-2 font-monospace fw-bold fs-6 text-decoration-none">
                <i class="fas fa-sign-out-alt me-2"></i>[ OTURUMU KAPAT ]
            </a>
        </div>
    </div>

</div>