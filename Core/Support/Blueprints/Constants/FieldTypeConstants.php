<?php

namespace Rbn\Framework\Core\Support\Blueprints\Constants;

class FieldTypeConstants
{
    public const FIELD_TYPES = [
        'text' => [
            'icon' => '📝',
            'label' => 'Metin',
            'description' => 'Tek satır metin girişi',
            'input_type' => 'text',
            'validation' => 'string|max:255'
        ],
        'textarea' => [
            'icon' => '📄',
            'label' => 'Çok Satırlı Metin',
            'description' => 'Çok satır metin girişi',
            'input_type' => 'textarea',
            'validation' => 'string'
        ],
        'tel' => [
            'icon' => '📞',
            'label' => 'Telefon',
            'description' => 'Telefon numarası (Otomatik formatlı)',
            'input_type' => 'tel',
            'validation' => 'string|max:20'
        ],
        'whatsapp' => [
            'icon' => '💬',
            'label' => 'WhatsApp Link',
            'description' => 'WhatsApp direct link (https://wa.me/ prefix)',
            'input_type' => 'text',
            'validation' => 'string|max:100'
        ],
        'email' => [
            'icon' => '📧',
            'label' => 'E-posta',
            'description' => 'E-posta adresi',
            'input_type' => 'email',
            'validation' => 'email'
        ],
        'url' => [
            'icon' => '🔗',
            'label' => 'URL',
            'description' => 'Web adresi',
            'input_type' => 'url',
            'validation' => 'url'
        ],
        'number' => [
            'icon' => '🔢',
            'label' => 'Sayı',
            'description' => 'Sayısal değer',
            'input_type' => 'number',
            'validation' => 'numeric'
        ],
        'select' => [
            'icon' => '📋',
            'label' => 'Seçim',
            'description' => 'Açılır liste',
            'input_type' => 'select',
            'validation' => 'string'
        ],
        'checkbox' => [
            'icon' => '☑️',
            'label' => 'Onay Kutusu',
            'description' => 'Evet/Hayır seçimi',
            'input_type' => 'checkbox',
            'validation' => 'boolean'
        ],
        'file' => [
            'icon' => '📎',
            'label' => 'Dosya',
            'description' => 'Dosya yükleme',
            'input_type' => 'file',
            'validation' => 'file'
        ],
        'image' => [
            'icon' => '🖼️',
            'label' => 'Resim',
            'description' => 'Resim yükleme',
            'input_type' => 'file',
            'validation' => 'image'
        ],
        'color' => [
            'icon' => '🎨',
            'label' => 'Renk',
            'description' => 'Renk seçici',
            'input_type' => 'color',
            'validation' => 'string'
        ],
        'theme_swatch' => [
            'icon' => '🌈',
            'label' => 'Tema Paleti',
            'description' => 'Tema seçim paleti',
            'input_type' => 'theme_swatch',
            'validation' => 'string'
        ]
    ];

    public const FIELD_DISPLAY_NAMES = [
        'name' => 'Ad Soyad',
        'firstName' => 'Ad',
        'lastName' => 'Soyad',
        'email' => 'E-posta',
        'phone' => 'Telefon',
        'address' => 'Adres',
        'identity' => 'T.C. Kimlik No',
        'message' => 'Mesaj',
        'title' => 'Başlık',
        'content' => 'İçerik',
        'password' => 'Şifre',
        'confirm_password' => 'Şifre Tekrar',
        'company' => 'Şirket',
        'service' => 'Hizmet',
        'budget' => 'Bütçe',
        'link' => 'Link',
        'url' => 'URL',
        'subject' => 'Konu',
        'description' => 'Açıklama',
        'privacy_accepted' => 'Gizlilik Sözleşmesi'
    ];

    public const FIELD_LENGTH_LIMITS = [
        'name' => 100,
        'email' => 254,                 // RFC 5321 standard
        'phone' => 11,                  // Türkiye için 11 haneli telefon numarası
        'identity' => 11,               // Türkiye için 11 haneli T.C. Kimlik Numarası
        'service' => 100,
        'subject' => 200,
        'message' => 2000,              // Mesaj alanı
        'contact_message' => 5000,      // İletişim formu Mesaj alanı
        'address' => 500,
        'company' => 200,
        'password' => 128,              // Güvenli şifre uzunluğu
        'title' => 150,
        'description' => 1000,
        'comment' => 500,
        'url' => 2048,                  // URL max length
        'city' => 100,
        'country' => 100
    ];

    /**
     * Tüm field tiplerini getir
     */
    public static function getFieldTypes(): array
    {
        return self::FIELD_TYPES;
    }
}
