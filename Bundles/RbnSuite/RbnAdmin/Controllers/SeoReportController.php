<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * SeoReportController - RBN Suite Core SEO Reporting 📊🚀
 * Thin Controller Standard: Sadece trafiği ve request akışını yönetir.
 */
#[SubModule(
    entity: 'seo-report'
)]
class SeoReportController extends RbnAdminController
{
    /**
     * Displays the centralized SEO Intelligence Report.
     * 
     * @return void
     */
    public function index(): void
    {
        $report = $this->service('seoScanner')->getReport();

        $this->render('Seo/report', [
            'stats' => $report['stats'],
            'details' => $report['details']
        ]);
    }
}
