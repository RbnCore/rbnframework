<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Controllers\DbConsole;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Bundles\Internal\Syshub\Controllers\SyshubController;

/**
 * SqlConsoleController - Interactive SQL Engine 🖥️🛰️⚓
 * RBN Framework Standard.
 */
#[SubModule(
    entity: 'database',
    service: 'dbConsole'
)]
class SqlConsoleController extends SyshubController
{
    /**
     * SQL Console View 🧪
     */
    public function index(): void
    {
        // 🖥️ Terminal tasarım sistemini panele yükle
        $this->service('asset')->prepare('@fw/RbnCommon/css/rbn-terminal.css', 'panel');

        $info = $this->service->getInfo();
        $tables = $this->service->getTables();

        $this->render('DbConsole/console', [
            'database_info' => $info,
            'tables' => $tables
        ]);
    }

    /**
     * AJAX: SQL sorgusu çalıştır. ⚡
     */
    public function executeQuery(): void
    {
        $form = $this->request->form([
            'query' => 'required|string'
        ], ['action' => 'sql_console_execute']);

        $result = $this->service->executeQuery($form['query']);

        $this->Route->handleResult($result);
    }

    /**
     * AJAX: Veritabanı genel bilgisini döner. 📊
     */
    public function getDatabaseInfo(): void
    {
        $info = $this->service->getInfo();
        $this->Route->handleResult([
            'success' => true,
            'data' => $info
        ]);
    }

    /**
     * AJAX: Tablo listesini döner. 📋
     */
    public function getTableList(): void
    {
        $tables = array_column($this->service->getTables(), 'name');
        $this->Route->handleResult([
            'success' => true,
            'data' => ['tables' => $tables]
        ]);
    }

    /**
     * AJAX: Tablo verilerini hızlıca getirir. 🔍
     */
    public function getTableData(): void
    {
        $form = $this->request->form(['table' => 'required|string']);
        $result = $this->service->executeQuery("SELECT * FROM `{$form['table']}` LIMIT 100");

        $this->Route->handleResult($result);
    }
}
