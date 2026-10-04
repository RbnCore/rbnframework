<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFile\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * FileImageHandler - GD Resim İşleme ve Manipülasyon İşleyicisi (Worker) 🎨🖼️
 * 
 * RBN 3.5: Masterpiece Standard (BaseComponent Actor).
 * Location: Packages\RbnFile\Handlers\
 */
class FileImageHandler extends BaseComponent
{
    /**
     * Resim boyutlandır (Performans Odaklı) 🖼️
     */
    public function resize(string $imagePath, int $width, int $height, bool $maintainAspect = true, ?string $suffix = null): array
    {
        try {
            $cleanPath = preg_replace('#^/?uploads/#', '', $imagePath);
            $fullPath = Paths::project()->uploads($cleanPath);

            if (!file_exists($fullPath))
                return $this->sendError('Resim bulunamadı.');

            $imageInfo = getimagesize($fullPath);
            if (!$imageInfo)
                return $this->sendError('Geçersiz resim.');

            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];

            if ($maintainAspect) {
                $ratio = min($width / $originalWidth, $height / $originalHeight);
                $newWidth = (int) ($originalWidth * $ratio);
                $newHeight = (int) ($originalHeight * $ratio);
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }

            $sourceImage = $this->createImage($fullPath, $mimeType);
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Transparency
            if ($mimeType === 'image/png') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                imagefill($resizedImage, 0, 0, imagecolorallocatealpha($resizedImage, 255, 255, 255, 127));
            }

            imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

            $pathInfo = pathinfo($imagePath);
            $suffix = $suffix ?? ($newWidth . 'x' . $newHeight);
            $resizedFileName = $pathInfo['filename'] . '_' . $suffix . '.' . $pathInfo['extension'];
            $resizedPath = trim(dirname($cleanPath), '/') . '/' . $resizedFileName;
            $resizedFullPath = Paths::project()->uploads($resizedPath);

            $saveResult = $this->saveImage($resizedImage, $resizedFullPath, $mimeType);

            return $saveResult ? $this->sendSuccess('Boyutlandırma başarılı', ['path' => $resizedPath]) : $this->sendError('Kaydedilemedi.');

        } catch (\Exception $e) {
            return $this->sendError('Resize hatası: ' . $e->getMessage());
        }
    }

    /**
     * Evrensel Resim Dönüştürücü ✂️🖼️
     */
    public function transform(string $imagePath, array $opts = []): array
    {
        try {
            $mode = $opts['mode'] ?? 'crop';
            $width = (int) ($opts['width'] ?? 600);
            $height = (int) ($opts['height'] ?? $width);

            if (str_starts_with(ltrim($imagePath, '/'), 'uploads/')) {
                $cleanPath = preg_replace('#^/?uploads/#', '', $imagePath);
                $fullPath = Paths::project()->uploads($cleanPath);
            } else {
                $fullPath = rtrim(Paths::publicRoot(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath), DIRECTORY_SEPARATOR);
            }

            if (!file_exists($fullPath))
                return $this->sendError('Resim bulunamadı: ' . $imagePath);

            $info = getimagesize($fullPath);
            if (!$info)
                return $this->sendError('Geçersiz resim.');

            $origW = $info[0];
            $origH = $info[1];
            $mime = $info['mime'];
            $source = $this->createImage($fullPath, $mime);

            if ($mode === 'resize') {
                $ratio = min($width / $origW, $height / $origH);
                $newW = (int) round($origW * $ratio);
                $newH = (int) round($origH * $ratio);
                $canvas = imagecreatetruecolor($newW, $newH);
                imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            } else {
                $canvas = imagecreatetruecolor($width, $height);

                if ($mime === 'image/png') {
                    imagealphablending($canvas, false);
                    imagesavealpha($canvas, true);
                    imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
                }

                if ($mode === 'crop') {
                    $ratio = max($width / $origW, $height / $origH);
                    $srcW = (int) round($width / $ratio);
                    $srcH = (int) round($height / $ratio);
                    $srcX = (int) round(($origW - $srcW) / 2);
                    $srcY = (int) round(($origH - $srcH) / 2);
                    imagecopyresampled($canvas, $source, 0, 0, $srcX, $srcY, $width, $height, $srcW, $srcH);
                } elseif ($mode === 'fit') {
                    $ratio = min($width / $origW, $height / $origH);
                    $newW = (int) round($origW * $ratio);
                    $newH = (int) round($origH * $ratio);
                    $dstX = (int) round(($width - $newW) / 2);
                    $dstY = (int) round(($height - $newH) / 2);
                    $fill = $opts['fill'] ?? [255, 255, 255];
                    if ($mime !== 'image/png') {
                        $bg = imagecolorallocate($canvas, $fill[0], $fill[1], $fill[2]);
                        imagefill($canvas, 0, 0, $bg);
                    }
                    imagecopyresampled($canvas, $source, $dstX, $dstY, 0, 0, $newW, $newH, $origW, $origH);
                } elseif ($mode === 'stretch') {
                    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $origW, $origH);
                }
            }

            $saved = $this->saveImage($canvas, $fullPath, $mime);

            return $saved
                ? $this->sendSuccess('Transform başarılı', ['path' => $imagePath])
                : $this->sendError('Kaydedilemedi.');

        } catch (\Exception $e) {
            return $this->sendError('Transform hatası: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Yerel bir görseli Google botlarını şaşırtacak şekilde özgünleştirir ve filigran ekler 🎨⚖️
     * 
     * Özgünleştirme Aşamaları:
     * 1. Resmin dış kenarlarından %3 otonom kırpar (Boyut ve MD5 değişimi).
     * 2. Hafif kontrast filtresi uygular (Renk piksel RGB imzalarını değiştirir).
     * 3. Alt kısıma yumuşak bir karartma gradyanı atar (Filigranı premium gösterir).
     * 4. Sağ alt köşeye markalanmış filigran metnini yazar.
     */
    public function watermark(string $imagePath, string $text, ?array $options = []): bool
    {
        try {
            if (!file_exists($imagePath)) {
                return false;
            }

            $info = getimagesize($imagePath);
            if (!$info) {
                return false;
            }

            $mime = $info['mime'];
            $source = $this->createImage($imagePath, $mime);
            if (!$source) {
                return false;
            }

            $origW = imagesx($source);
            $origH = imagesy($source);

            // --- 🧪 1. ADIM: YAZISIZ ARKA PLANDAN DİKEY AFİŞ ÇIKARMA (Smart Center Crop) ---
            if ($origW > $origH) {
                $newW = (int) ($origH * (2 / 3)); // 2:3 dikey afiş oranı
                $newH = $origH;
                $cropX = (int) (($origW - $newW) / 2);
                $cropY = 0;
            } else {
                $cropMarginW = (int) ($origW * 0.15);
                $cropMarginH = (int) ($origH * 0.15);
                $newW = $origW - ($cropMarginW * 2);
                $newH = $origH - ($cropMarginH * 2);
                $cropX = $cropMarginW;
                $cropY = $cropMarginH;
            }

            $canvas = imagecreatetruecolor($newW, $newH);

            if ($mime === 'image/png') {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            }

            imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, $newW, $newH, $newW, $newH);
            // --- 🧪 2. ADIM: HAFİF KONTRAST FİLTRESİ ---
            imagefilter($canvas, IMG_FILTER_CONTRAST, -3);

            // --- 🧪 3. ADIM: SİNEMATİK SCANLINE (ÇİZGİ) DESENİ ---
            $lineColor = imagecolorallocatealpha($canvas, 0, 0, 0, 118);
            for ($y = 0; $y < $newH; $y += 3) {
                imageline($canvas, 0, $y, $newW, $y, $lineColor);
            }

            // --- 🧪 4. ADIM: ALT KISMA SİNEMATİK GRADYAN KARARTMA (Vignette Shadow) ---
            $gradientHeight = (int) ($newH * 0.30);
            $startY = $newH - $gradientHeight;
            for ($y = 0; $y < $gradientHeight; $y++) {
                $alpha = (int) (127 - (112 * ($y / $gradientHeight)));
                $gradColor = imagecolorallocatealpha($canvas, 8, 11, 17, $alpha);
                imagefilledrectangle($canvas, 0, $startY + $y, $newW, $startY + $y + 1, $gradColor);
            }

            // Dışarıdan gelen parametreleri options içinden çözümlüyoruz
            $options = $options ?? [];
            $logoPath = $options['logo_path'] ?? null;
            $title = $options['title'] ?? null;

            // --- 🧪 5. ADIM: PROJE FAVICON / LOGO VE BAŞLIK OVERLAY (Sol Üst Köşe) ---
            $logoMargin = 14;
            $logoTargetW = 0;
            $logoTargetH = 0;

            if ($logoPath && file_exists($logoPath)) {
                $favicon = @imagecreatefrompng($logoPath);
                if ($favicon) {
                    imagealphablending($canvas, true);
                    $favW = imagesx($favicon);
                    $favH = imagesy($favicon);

                    $logoTargetW = (int) max(60, min(100, $newW * 0.14));
                    $logoTargetH = (int) ($logoTargetW * ($favH / $favW));

                    imagecopyresampled($canvas, $favicon, $logoMargin, $logoMargin, 0, 0, $logoTargetW, $logoTargetH, $favW, $favH);
                }
            }

            // Dizi/Film ismini afişin alt-ortasına sinematik bir şekilde yazalım
            if (!empty($title)) {
                // Dinamik Yazı Tipi Seçimi (RbnCommon assets veya framework fonts)
                $fontName = $options['font'] ?? 'Poppins-Bold';
                $fontPaths = [
                    Paths::framework()->assets("RbnCommon/fonts/{$fontName}.ttf"),
                    Paths::framework()->assets("RbnCommon/fonts/Poppins-Bold.ttf"),
                    Paths::framework()->assets("RbnCommon/fonts/Montserrat-Bold.ttf"),
                    Paths::framework()->root("Resources/Assets/RbnCommon/fonts/{$fontName}.ttf"),
                    Paths::framework()->root("Resources/Assets/RbnCommon/fonts/Poppins-Bold.ttf"),
                    Paths::framework()->root("Resources/Assets/RbnCommon/fonts/Montserrat-Bold.ttf")
                ];

                $fontFile = null;
                foreach ($fontPaths as $candidatePath) {
                    if (!empty($candidatePath) && file_exists($candidatePath)) {
                        $fontFile = $candidatePath;
                        break;
                    }
                }

                if ($fontFile && file_exists($fontFile)) {
                    imagealphablending($canvas, true);

                    // Dinamik Yazı Boyutu: Genişliğin %6.5'i kadar (Min: 20pt, Max: 36pt)
                    $fontSize = (int) max(20, min(36, $newW * 0.065));

                    // Akıllı Word Wrap (Kelime bütünlüğünü koruyarak satırlara bölme)
                    $words = explode(' ', $title);
                    $lines = [];
                    $currentLine = '';

                    foreach ($words as $word) {
                        $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
                        $bbox = imagettfbbox($fontSize, 0, $fontFile, $testLine);
                        $lineWidth = abs($bbox[4] - $bbox[0]);

                        if ($lineWidth > ($newW * 0.85) && $currentLine !== '') {
                            $lines[] = $currentLine;
                            $currentLine = $word;
                        } else {
                            $currentLine = $testLine;
                        }
                    }
                    if ($currentLine !== '') {
                        $lines[] = $currentLine;
                    }

                    $lineHeight = (int) ($fontSize * 1.5);
                    $totalTextHeight = count($lines) * $lineHeight;
                    $currentY = $newH - 120 - ($totalTextHeight / 2);

                    $titleTextColor = imagecolorallocate($canvas, 255, 255, 255);
                    $titleShadowColor = imagecolorallocatealpha($canvas, 0, 0, 0, 50);

                    foreach ($lines as $line) {
                        $bbox = imagettfbbox($fontSize, 0, $fontFile, $line);
                        $textWidth = abs($bbox[4] - $bbox[0]);
                        $titleX = (int) (($newW - $textWidth) / 2);

                        // Gölge ve Beyaz Metin
                        imagettftext($canvas, $fontSize, 0, $titleX + 2, (int) $currentY + 2, $titleShadowColor, $fontFile, $line);
                        imagettftext($canvas, $fontSize, 0, $titleX, (int) $currentY, $titleTextColor, $fontFile, $line);

                        $currentY += $lineHeight;
                    }
                } else {
                    // Fallback (imagestring için Türkçe karakter koruması)
                    $font = 5;
                    $textHeight = imagefontheight($font);
                    $titleX = ($logoTargetW > 0) ? ($logoMargin + $logoTargetW + 12) : $logoMargin;
                    $titleY = ($logoTargetH > 0) ? ($logoMargin + (int) (($logoTargetH - $textHeight) / 2)) : $logoMargin;

                    $fallbackText = iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $title);
                    if ($fallbackText === false) {
                        $fallbackText = $title;
                    }

                    imagestring($canvas, $font, $titleX + 1, $titleY + 1, $fallbackText, imagecolorallocatealpha($canvas, 0, 0, 0, 80));
                    imagestring($canvas, $font, $titleX, $titleY, $fallbackText, imagecolorallocate($canvas, 255, 255, 255));
                }
            }

            // --- 🧪 6. ADIM: ŞIK KOYU RENK DIŞ ÇERÇEVE (Border Frame) ---
            $borderColor = imagecolorallocate($canvas, 8, 11, 17);
            imagefilledrectangle($canvas, 0, 0, $newW, 8, $borderColor);
            imagefilledrectangle($canvas, 0, $newH - 8, $newW, $newH, $borderColor);
            imagefilledrectangle($canvas, 0, 0, 8, $newH, $borderColor);
            imagefilledrectangle($canvas, $newW - 8, 0, $newW, $newH, $borderColor);

            // --- 🧪 7. ADIM: FİLİGRAN METNİ ÇİZİMİ ---
            $textColor = imagecolorallocatealpha($canvas, 255, 255, 255, 40); // Yarı saydam beyaz
            $shadowColor = imagecolorallocatealpha($canvas, 0, 0, 0, 80);

            $font = 5;
            $textWidth = imagefontwidth($font) * strlen($text);
            $textHeight = imagefontheight($font);

            // Çerçevenin 8px sınırını hesaba katarak konumlandırıyoruz (20px marj)
            $x = $newW - $textWidth - 20;
            $y = $newH - $textHeight - 20;

            if ($x > 0 && $y > 0) {
                // Gölge
                imagestring($canvas, $font, $x + 1, $y + 1, $text, $shadowColor);
                // Metin
                imagestring($canvas, $font, $x, $y, $text, $textColor);
            }

            // Kaydet
            $result = $this->saveImage($canvas, $imagePath, $mime);

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Convert an image to WebP format dynamically to optimize PageSpeed & Disk space 🚀
     */
    public function convertToWebp(string $imagePath, int $quality = 85, bool $deleteOriginal = true): array
    {
        try {
            if (file_exists($imagePath)) {
                $fullPath = $imagePath;
            } elseif (str_starts_with(ltrim($imagePath, '/'), 'uploads/')) {
                $cleanPath = preg_replace('#^/?uploads/#', '', $imagePath);
                $fullPath = Paths::project()->uploads($cleanPath);
            } else {
                $fullPath = rtrim(Paths::publicRoot(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath), DIRECTORY_SEPARATOR);
            }

            if (!file_exists($fullPath)) {
                return $this->sendError('Kaynak resim bulunamadı: ' . $imagePath);
            }

            $info = @getimagesize($fullPath);
            if (!$info) {
                return $this->sendError('Geçersiz resim dosyası.');
            }

            $mime = $info['mime'];
            if ($mime === 'image/webp') {
                return $this->sendSuccess('Resim zaten WebP.', ['path' => $imagePath]);
            }

            $source = $this->createImage($fullPath, $mime);
            if (!$source) {
                return $this->sendError('Resim nesnesi oluşturulamadı.');
            }

            $pathInfo = pathinfo($fullPath);
            $newFileName = $pathInfo['filename'] . '.webp';
            $newFullPath = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $newFileName;

            // Transparanlık koruma (PNG ve WebP için)
            imagealphablending($source, false);
            imagesavealpha($source, true);

            $saved = imagewebp($source, $newFullPath, $quality);

            if ($saved && file_exists($newFullPath) && filesize($newFullPath) > 0) {
                if ($deleteOriginal && $fullPath !== $newFullPath && file_exists($fullPath)) {
                    @unlink($fullPath);
                }

                // Göreli (relative) yolu yeniden hesapla
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $imagePath);
                $pathParts = pathinfo($relativePath);
                $newRelativePath = trim($pathParts['dirname'], '/') . '/' . $newFileName;

                if (!str_starts_with($newRelativePath, '/')) {
                    $newRelativePath = '/' . $newRelativePath;
                }

                return $this->sendSuccess('WebP dönüşümü başarılı', ['path' => $newRelativePath]);
            }

            return $this->sendError('WebP kaydetme hatası.');
        } catch (\Exception $e) {
            return $this->sendError('WebP dönüşüm hatası: ' . $e->getMessage());
        }
    }

    private function createImage(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/gif' => imagecreatefromgif($path),
            default => false
        };
    }

    private function saveImage($image, string $path, string $mime): bool
    {
        return match ($mime) {
            'image/jpeg' => imagejpeg($image, $path, 85),
            'image/png' => imagepng($image, $path),
            'image/webp' => imagewebp($image, $path, 85),
            'image/gif' => imagegif($image, $path),
            default => false
        };
    }
}
