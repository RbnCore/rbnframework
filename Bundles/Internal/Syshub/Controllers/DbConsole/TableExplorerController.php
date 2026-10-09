<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Controllers\DbConsole;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Bundles\Internal\Syshub\Controllers\SyshubController;

/**
 * TableExplorerController - Database Schema & Data Explorer 📊🛰️⚓
 * RBN Framework Standard.
 */
#[SubModule(
    entity: 'database',
    service: 'dbConsole'
)]
class TableExplorerController extends SyshubController
{
    /**
     * Tablo listesini görüntüler 📋
     */
    public function index(): void
    {
        $allTables = $this->service->getTables();
        $dbInfo = $this->service->getInfo();

        $pagination = $this->paginate($allTables, 15);

        $this->render('DbConsole/tables', [
            'tables' => $pagination->items(),
            'database_info' => $dbInfo,
            'total' => $pagination->total(),
            'search' => $this->request->input('search', ''),
            'pagination' => $pagination->links()
        ]);
    }

    /**
     * Tablo içindeki verileri listeler 🔍
     */
    public function browse(): void
    {
        $form = $this->request->form(['table' => 'required|string']);
        $table = $form['table'];

        $allRows = $this->service->getRows($table);
        $pagination = $this->paginate($allRows, 20);
        $meta = $this->service->getMetadata($table);
        $dbInfo = $this->service->getInfo();

        $this->render('DbConsole/browse', [
            'table' => $table,
            'items' => $pagination->items(),
            'total' => $pagination->total(),
            'search' => $this->request->input('search', ''),
            'pagination' => $pagination->links(),
            'table_metadata' => $meta['metadata'],
            'database_info' => $dbInfo,
            'primary_key' => $meta['primaryKey']
        ]);
    }

    /**
     * Tablo collation değiştirme işlemi 🔠
     */
    public function changeCollation(): void
    {
        $form = $this->request->form([
            'table' => 'required|string',
            'collation' => 'required|string'
        ]);

        $result = $this->service->changeCollation($form['table'], $form['collation']);
        $this->handleResult($result, "{$form['table']} tablosu başarıyla {$form['collation']} olarak dönüştürüldü.", $this->returnPath('browse') . '?table=' . urlencode($form['table']));
    }

    /**
     * Tekil satır silme işlemi 🗑️
     */
    public function deleteRow(): void
    {
        $form = $this->request->form([
            'table' => 'required|string',
            'id' => 'required'
        ]);

        $result = $this->service->deleteRows($form['table'], (array) $form['id']);
        $this->handleResult($result, 'Kayıt', $this->returnPath('browse') . '?table=' . urlencode($form['table']));
    }

    /**
     * Toplu satır silme işlemi 🚜
     */
    public function bulkDeleteRows(): void
    {
        $form = $this->request->form([
            'table' => 'required|string',
            'ids' => 'required|array'
        ]);

        $result = $this->service->deleteRows($form['table'], $form['ids']);
        $this->handleResult($result, count($form['ids']) . ' kayıt', $this->returnPath('browse') . '?table=' . urlencode($form['table']));
    }

    /**
     * Seçili satırları SQL olarak dışa aktarır 💾
     */
    public function bulkExportRows(): void
    {
        $form = $this->request->form([
            'table' => 'required|string',
            'ids' => 'required|array'
        ]);

        $content = $this->service->exportSql($form['table'], $form['ids']);
        $file = $this->service->prepareTempFile("{$form['table']}_rows_" . date('Ymd_His') . ".sql", $content);

        $this->respondDownload($file, count($form['ids']) . ' satır SQL dökümü hazırlandı.');
    }

    /**
     * Tabloyu tamamen siler 🔥
     */
    public function dropTable(): void
    {
        $form = $this->request->form(['table' => 'required|string']);
        $result = $this->service->dropTable($form['table']);
        $this->handleResult($result, "{$form['table']} tablosu", $this->returnPath('tables'));
    }

    /**
     * Seçili tabloları toplu siler 🌪️
     */
    public function bulkDropTables(): void
    {
        $this->handleBulkAction(
            $this->service,
            'dropTable',
            [],
            [
                'success_message' => 'Seçili tablolar başarıyla silindi.',
                'path' => $this->returnPath('tables')
            ]
        );
    }

    /**
     * SQL Dışa Aktarma 💾
     */
    public function exportSQL(): void
    {
        $table = $this->request->input('table');
        if (!$table) {
            $this->handleResult(false, "Tablo belirtilmedi.");
            return;
        }

        $content = $this->service->exportSql($table);
        $file = $this->service->prepareTempFile("{$table}_export_" . date('Ymd_His') . ".sql", $content);

        $this->respondDownload($file, 'SQL dökümü hazırlandı.');
    }

    /**
     * Toplu SQL İndirme (Ayrı Ayrı) 📥📥
     */
    public function bulkExportSQL(): void
    {
        $form = $this->request->form(['ids' => 'required|array']);
        $downloads = [];

        foreach ($form['ids'] as $table) {
            $content = $this->service->exportSql($table);
            $file = $this->service->prepareTempFile("{$table}_export_" . date('Ymd_His') . ".sql", $content);

            $downloads[] = [
                'url' => $this->Route->url($this->indexRoute . '/download-temp', 'developer') . '?file=' . urlencode($file),
                'name' => $file
            ];
        }

        $this->respondMultiDownload($downloads, count($form['ids']) . ' tablo SQL dökümü indiriliyor.');
    }

    /**
     * Toplu SQL İndirme (ZIP Arşivi) 📦
     */
    public function bulkExportZip(): void
    {
        $form = $this->request->form(['ids' => 'required|array']);
        $files = [];

        foreach ($form['ids'] as $name) {
            $files[$name . '.sql'] = $this->service->exportSql($name);
        }

        $file = $this->service->prepareTempZip('db_pack_' . date('Ymd_His') . '.zip', $files);
        $this->respondDownload($file, 'Seçili tablolar ZIP arşivi içinde hazırlandı.');
    }
}
