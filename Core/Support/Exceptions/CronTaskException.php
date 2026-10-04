<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Exceptions;

/**
 * CronTaskException - Single Unified Domain Exception Authority for All Cron & Pipeline Tasks 🤖🛰️⚓
 * 
 * Provides standardized static factory methods for common cron status messages,
 * lifecycle skips, task class validation, model resolution failures, and configuration errors zero-boilerplate.
 */
class CronTaskException extends \RuntimeException
{
    /** @var string Task execution status ('skipped', 'failed', 'retry') */
    protected string $cronStatus = 'failed';

    /**
     * Factory: Generic skipped cron exception ⏭️
     */
    public static function skipped(string $message = "Görev pas geçildi veya zamanı değil."): self
    {
        $e = new self($message);
        $e->cronStatus = 'skipped';
        return $e;
    }

    /**
     * Factory: Already published in current time window / daily limit reached 🛡️
     */
    public static function alreadyPublished(string $detail = ''): self
    {
        $msg = "Bugün veya bu zaman diliminde zaten yayın yapılmış. Tekrar yayınlama atlandı.";
        if (!empty($detail)) {
            $msg .= " ({$detail})";
        }
        return static::skipped($msg);
    }

    /**
     * Factory: Current time does not match target schedule hours/days ⏳
     */
    public static function notScheduledTime(string $detail = ''): self
    {
        $msg = "Yayın zamanı değil.";
        if (!empty($detail)) {
            $msg .= " ({$detail})";
        }
        return static::skipped($msg);
    }

    /**
     * Factory: Candidate or draft not found skipped exception 🔍
     */
    public static function candidateNotFound(string $message = "İşlenecek yeni veya uygun içerik adayı/taslağı bulunamadı."): self
    {
        return static::skipped($message);
    }

    /**
     * Factory: Generic configuration failure exception ⚙️
     */
    public static function configuration(string $message): self
    {
        $e = new self("Kritik Yapılandırma Hatası: {$message} ❌");
        $e->cronStatus = 'failed';
        return $e;
    }

    /**
     * Factory: Required parameter missing 🔑
     */
    public static function missingParameter(string $paramKey, string $projectKey = ''): self
    {
        $suffix = $projectKey ? " (Proje: {$projectKey})" : '';
        return static::configuration("'{$paramKey}' parametresi tanımlanmalıdır!{$suffix}");
    }

    /**
     * Factory: Database model not found error 🗄️
     */
    public static function modelNotFound(string $modelAlias, string $projectKey = ''): self
    {
        $suffix = $projectKey ? " (Proje: {$projectKey})" : '';
        $e = new self("Kritik Hata: Veritabanı Modeli [{$modelAlias}] çözümlenemedi veya sınıfa erişilemedi!{$suffix} ❌");
        $e->cronStatus = 'failed';
        return $e;
    }

    /**
     * Factory: Missing or unresolvable task class 🧩
     */
    public static function invalidTaskClass(string $className = ''): self
    {
        $msg = empty($className) 
            ? "Görev sınıfı (task_class) belirtilmemiş." 
            : "Görev sınıfı bulunamadı veya yüklenemedi: [{$className}] ❌";
        $e = new self($msg);
        $e->cronStatus = 'failed';
        return $e;
    }

    /**
     * Factory: Task class does not extend AbstractCronTask 🏛️
     */
    public static function invalidTaskInheritance(string $className): self
    {
        $e = new self("Görev sınıfı [{$className}] AbstractCronTask sınıfından türetilmelidir. ❌");
        $e->cronStatus = 'failed';
        return $e;
    }

    /**
     * Factory: Task file missing on disk 📁
     */
    public static function taskFileNotFound(string $taskClass, string $taskKey = ''): self
    {
        $prefix = $taskKey ? "[{$taskKey}] " : '';
        $e = new self("Kritik Hata: {$prefix}görevine ait sınıf dosyası diskte bulunamadı ({$taskClass}). Görev pasif konuma alındı. ❌");
        $e->cronStatus = 'failed';
        return $e;
    }

    /**
     * Factory: Undefined or invalid task_key 🔑
     */
    public static function undefinedTaskKey(string $taskKey = ''): self
    {
        $keyText = $taskKey ?: 'NULL';
        $e = new self("Hata: Tanımsız veya geçersiz task_key [{$keyText}] ❌");
        $e->cronStatus = 'failed';
        return $e;
    }

    /**
     * Get the attached cron execution status 🎯
     */
    public function getCronStatus(): string
    {
        return $this->cronStatus;
    }

    /**
     * Check if this exception represents a safe skip 🛡️
     */
    public function isSkipped(): bool
    {
        return $this->cronStatus === 'skipped';
    }
}
