<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Bundles\Internal\Webhub\Models\SeoConfig;

/**
 * SeoController - Search Engine Orchestrator 🔍🛰️
 * Standard: RBN 3.5 Masterpiece - MIRROR OF IDENTITY
 */
#[SubModule(
    entity: 'seo',
    entityName: 'setting',
    bulkInputKey: 'settings'
)]
class SeoController extends WebhubController
{

    /**
     * Identity Dashboard (Value View) -> Mirrored for SEO
     */
    public function index(): void
    {
        $settings = $this->service('settings')
            ->withGroup('seo')
            ->withProject(active_project_key())
            ->active()
            ->all();

        $this->render('Seo/index', [
            'settings' => $settings
        ]);
    }

    /**
     * SEO Score Analyzer View 🚀
     */
    public function score(): void
    {
        $report = $this->service('seoScanner')->getReport();

        $this->render('Seo/score', [
            'seoScore' => $report['stats']->score,
            'lastScan' => $report['stats']->date,
            'display' => $report['display']
        ]);
    }

    /**
     * RBN 3.5: AI & Detailed SEO Report View 🤖
     */
    public function report(): void
    {
        $report = $this->service('seoScanner')->getReport();

        $this->render('Seo/report', [
            'seoScore' => $report['stats']->score,
            'lastScan' => $report['stats']->date,
            'reportData' => $report['details'],
            'advice' => $report['stats']->advice,
            'gemini' => $report['stats']->gemini,
            'display' => $report['display']
        ]);
    }

    /**
     * Run the Deep SEO Analyzer Engine 🚀🛰️
     */
    public function scan(): void
    {
        // 1. Gerçek SEO Analiz Pipeline'ını Çalıştır
        $result = $this->service('seoScanner')->scan();

        // 2. Otonom Kayıt & Cache Purge (Delegated to Provider) 🚀🛰️
        $this->provider('webhub')->actionScan('save_seo_scan', $result);

        // 3. Mükemmel bir bildirimle sayfaya geri dönüyoruz
        $redirectPath = 'seo/report';
        $this->handleResult(true, "Derin SEO taraması tamamlandı! Yapay Zeka raporu hazırlandı. ✨", $redirectPath);
    }


    /**
     * Identity Management (Structure View) -> Mirrored for SEO
     */
    public function manage(): void
    {
        $settings = $this->service('settings')
            ->withGroup('seo')
            ->withProject(active_project_key())
            ->all();

        $this->render('Seo/manage', [
            'settings' => $settings
        ]);
    }

    /**
     * Create New Structural Requirement (Override for Group ID)
     */
    public function create(): void
    {
        $data = $this->request->form([
            'label_tr' => 'required',
            'field_type' => 'required',
            'required_role' => 'developer'
        ]);

        $result = $this->service->action()->save(array_merge($data, [
            'group_id' => 2,
            'is_active' => 1
        ]));

        $this->handleResult($result, 'Yeni SEO alanı', 'seo/manage');
    }

    /**
     * Update Structural Requirement
     */
    public function update(): void
    {
        $data = $this->request->form([
            'id' => 'required|numeric',
            'label_tr' => 'required',
            'setting_key' => 'required',
        ]);

        $result = $this->service->action()->withId((int) $data['id'])->save($data);

        $this->handleResult($result, 'Alan yapılandırması', 'seo/manage');
    }

    /* Trait Handles: status(), delete(), bulkOrder(), modal() */
}
