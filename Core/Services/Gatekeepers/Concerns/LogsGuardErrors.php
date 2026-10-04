<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers\Concerns;

/**
 * Gatekeeper katmanlarının teknik hata kaydı için ortak yardımcı. 📜
 *
 * [G-18 · 2026-10-03 · zeki-6eb7f5] NEDEN AYRI DOSYA:
 * `DatabaseGuardHandler` kabul testi `fw_licence_kapi.php` K6 tarafından
 * **250 satır** sınırında tutuluyor (`satir <= 250`). G-18'in log yardımcısı
 * handler'ın içine yazılsaydı o sınır aşılırdı; testi GEVŞETMEK yerine
 * (kural: kodu teste uydurma) kodu sınıra uyduruldu.
 *
 * [G-13] fail-open kalıbının bu katmandaki tehdidi: "sessiz yutma". Burada
 * hiçbir şey yutulmaz — log kanalı yoksa `error_log()`a düşülür; ham mesaj
 * (`exception`, `message`, `file:satır`) **yalnızca sunucu günlüğüne** yazılır,
 * kullanıcıya gösterilen metin genel kalır.
 *
 * Kullanım: `class X extends BaseComponent { use LogsGuardErrors; ... }`
 * `$this->logs()` erişimi `BaseComponent` üzerinden sağlanır.
 */
trait LogsGuardErrors
{
    /**
     * Ham DB/teknik hata metnini günlüğe yazar; istisnayı YUTMAZ. 📜
     *
     * @param string               $rule     Kısa olay kodu (log ayrımı için)
     * @param string               $category Bağlam (örn. `database_project`)
     * @param array<string,mixed>  $context  Ek bağlam (asla gizli değer)
     */
    protected function logGuardError(
        string $rule,
        string $category,
        \Throwable $e,
        array $context = []
    ): void {
        $kayit = array_merge([
            'rule'      => $rule,
            'category'  => $category,
            'exception' => get_class($e),
            'message'   => $e->getMessage(),
            'file'      => $e->getFile() . ':' . $e->getLine(),
            'not'       => 'ham mesaj YALNIZCA burada; kullanici metni genel kalir',
        ], $context);

        try {
            $this->logs()?->channel('databaseguard')->error('Veritabanı koruma kontrolü başarısız.', $kayit);
            return;
        } catch (\Throwable $kanalHatasi) {
            // Kanal/logger yoksa aşağıda error_log'a düşülür (sessiz yutma yok).
        }

        error_log('[databaseguard] ' . $rule . ' ' . $category . ' :: ' . get_class($e) . ': ' . $e->getMessage());
    }
}
