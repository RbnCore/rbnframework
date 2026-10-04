<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Master\Data;

/**
 * MasterLicenceConfig - Merkezi Lisans Sabitleri 🔑🏛️
 * RBN 3.5 Masterpiece Standard.
 *
 * KURAL: Bu dosya YALNIZCA sabit (public const) tutar; METOT İÇERMEZ.
 * Tüm değerler veritabanı şemasıyla birebir aynıdır (tek gerçek = DB).
 */
class MasterLicenceConfig
{
    /**
     * Lisans Durumları (licences.status) 📿
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Lisans Kademeleri (licences.tier) 💎
     */
    public const TIER_FREE = 'FREE';
    public const TIER_LIFETIME = 'LIFETIME';
    public const TIER_PRO = 'PRO';

    /**
     * Lisansın Bağlı Olduğu Özne Türleri (licences.subject_type) 🎯
     */
    public const SUBJECT_PROJECT = 'project';
    public const SUBJECT_APPLICATION = 'application';

    /**
     * Anahtar Üretim Önekidir 🔤
     */
    public const KEY_PREFIX = 'RBN';

    /**
     * Cihaz Bağlama (activation) varsayılan üst sınırı 📱
     */
    public const DEFAULT_MAX_ACTIVATIONS = 1;

    /**
     * Doğrulama Sonuç Sebepleri (fail-closed) 🧯
     */
    public const REASON_UNKNOWN_KEY = 'unknown_key';
    public const REASON_EXPIRED = 'expired';
    public const REASON_REVOKED = 'revoked';
    public const REASON_SUSPENDED = 'suspended';
    public const REASON_DEVICE_MISMATCH = 'device_mismatch';
    public const REASON_STORAGE_ERROR = 'storage_error';
    /** Kayıt alanları bozuk: status/tier NULL, boş veya tanınmayan değer (fail-closed). */
    public const REASON_INVALID_RECORD = 'invalid_record';
}