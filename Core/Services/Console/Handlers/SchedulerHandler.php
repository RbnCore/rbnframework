<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SchedulerHandler - Akıllı Gün ve Kota Doğrulayıcı 🕒🎯
 * 
 * Sadece gün ve kota uygunluğunu doğrular, hiçbir DB güncellemesi yapmaz.
 */
class SchedulerHandler extends BaseComponent
{
    /**
     * Görevin bugün çalıştırılmaya uygun olup olmadığını doğrular ve sebebini açıkça söyler.
     * 
     * @param array $allowedDays DB'den gelen izin verilen günler [1,2,3..]
     * @param array $targetHours DB'den gelen hedef saatler [7, 15..]
     * @param int $todayPublishedCount Task'tan gelen bugün yapılan yayın sayısı
     * @return array ['is_due' => bool, 'reason' => string]
     */
    public function isRunDue(array $allowedDays, array $targetHours, int $todayPublishedCount): array
    {
        $now = new \DateTime();
        $todayWeekDay = (int) $now->format('N'); // 1 (Pzt) - 7 (Paz)

        // 1. KURAL: Bugün yayın günü mü?
        if (!in_array($todayWeekDay, $allowedDays, true)) {
            return [
                'is_due' => false,
                'reason' => "Yayın günü uygun değil (Bugünün gün kodu: {$todayWeekDay}, İzin verilen günler: [" . implode(',', $allowedDays) . "])"
            ];
        }

        // 2. KURAL: Şu anki saat izin verilen hedef saatlerden biri mi? 🕒
        $currentHour = (int) $now->format('G');
        $targetHours = array_map('intval', $targetHours);
        if (!in_array($currentHour, $targetHours, true)) {
            return [
                'is_due' => false,
                'reason' => "Şu anki saat ({$currentHour}:00) hedef çalışma saatlerinden [" . implode(',', $targetHours) . "] biri değil."
            ];
        }

        // 3. KURAL: Bugünkü hedef kota dolmuş mu?
        $dailyQuota = count($targetHours); // Hedef saat sayısı = Günlük kota
        if ($todayPublishedCount >= $dailyQuota) {
            return [
                'is_due' => false,
                'reason' => "Bugünkü yayın kotası tamamlanmış (Yapılan: {$todayPublishedCount}, Hedef Kota: {$dailyQuota})"
            ];
        }

        // Gün de uygun, saat de uygun, kota da dolmamış -> ÇALIŞTIR!
        return [
            'is_due' => true,
            'reason' => "Gün, saat ({$currentHour}:00) ve kota uygun (Yapılan: {$todayPublishedCount}, Hedef Kota: {$dailyQuota})"
        ];
    }

    /**
     * DB'deki `days` ve `hour` listesine göre BİR SONRAKİ KESİN ÇALIŞMA SAATİNİ hesaplar.
     */
    public function calculateNextRunTime(array $allowedDays, array $targetHours): string
    {
        $targetHours = array_map('intval', $targetHours);
        sort($targetHours); // [7, 11, 15, 19, 23]

        $now = new \DateTime();
        $currentHour = (int) $now->format('G');

        // Bugünden başlayarak 8 günü tara
        for ($i = 0; $i <= 8; $i++) {
            $checkDate = (clone $now)->modify("+{$i} days");
            $dayOfWeek = (int) $checkDate->format('N'); // 1 (Pzt) - 7 (Paz)

            if (in_array($dayOfWeek, $allowedDays, true)) {
                foreach ($targetHours as $h) {
                    $h = (int) $h;

                    // Bugünse, şu anki saatten STRICTLY BÜYÜK olan ilk saati seç
                    if ($i === 0 && $currentHour >= $h) {
                        continue;
                    }

                    $checkDate->setTime($h, 0, 0);
                    return $checkDate->format('Y-m-d H:i:00');
                }
            }
        }

        return date('Y-m-d 07:00:00', strtotime('+1 day'));
    }
}
