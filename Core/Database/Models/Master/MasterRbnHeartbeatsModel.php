<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Master;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * MasterRbnHeartbeatsModel - System Telemetry Hub 💓🛰️
 * 
 * RBN 3.5: Targeted model for framework heartbeat signaling on the master db.
 * 
 * @property string $heartbeat_key
 * @property string $last_beat
 * @property string $payload
 */
class MasterRbnHeartbeatsModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_master';
    protected $table = 'rbn_heartbeats';
    /**
     * FW-ALTYAPI-2 H / G1 - kiraci izolasyonu BEYANI (beyan zorunlulugu).
     *
     * Kapsam  : master yonetim duzlemi.
     * Gerekce: master yonetim duzlemi: satir bir KIRACI degil, sistem kaydidir; kiraci bazli kapsam bu duzlemde uygulanmaz.
     *
     * Varsayilan `BaseModel::$scoped` DEGISTIRILMEDI: kiraci izolasyonu
     * varsayilan olarak KAPALI kalir (acmak girisi 7 veritabaninda
     * kirar - FW-ALTYAPI-1 H 4.2 olculdu). G1 yalniz BEYAN ZORUNLULUGU
     * getirir: her model kararini kendi dosyasinda yazar.
     *
     * @tenant-scope master
     */
    protected bool $scoped = false;
    /**
     * [FW-ALTYAPI-2 / H] TİP DÜZELTMESİ — bu sınıf ÖNCEDEN YÜKLENEMİYORDU.
     *
     * SORUN: `BaseModel::$primaryKey` TİPSİZ bildirilmiştir
     * (`protected $primaryKey = 'id';`). Bu dosya onu `string` tipiyle
     * yeniden bildiriyordu; PHP bir üst sınıfın TİPSİZ alanını alt sınıfta
     * tipli bildirmek için **fatal** hata verir:
     * "Type of ...::$primaryKey must not be defined (as in class ...BaseModel)".
     * Sonuç: sınıf hiçbir zaman autoload olmuyor, `rbn_heartbeats` tablosu
     * için master telemetri modeli ÇALIŞMIYORDU.
     *
     * TESPİT: `rbn tenant:audit` (G2) beyan envanteri için model sınıflarını
     * yükler ve hatayı ortaya çıkardı. Kardeş model
     * `Project/RbnHeartbeatsModel.php:61` zaten TİPSİZ bildiriyor — bu
     * düzeltme o sözleşmeyi eşitler; davranış (anahtar adı) DEĞİŞMEZ.
     */
    protected $primaryKey = 'heartbeat_key';
    public bool $incrementing = false;
    protected string $keyType = 'string';
    protected bool $timestamps = false;
}
