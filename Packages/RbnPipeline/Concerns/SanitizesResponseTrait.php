<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Concerns;

/**
 * SanitizesResponseTrait - Unified response sanitization & JSON repair helpers for RBN Pipeline Rules 🪐🧪🧼🛠️
 * 
 * RBN 3.5 Masterpiece Standard.
 * Centralizes all markdown JSON wrapper stripping, unnesting, control character sanitization,
 * unescaped quote repairing, and array decoding across the framework.
 */
trait SanitizesResponseTrait
{
    /**
     * Safe JSON Decoding
     */
    protected function decodeJson(string $response): ?array
    {
        $data = $this->helper('datasweep')->decodeJson($response);
        return (json_last_error() === JSON_ERROR_NONE && is_array($data)) ? $data : null;
    }

    /**
     * Safe JSON Encoding
     */
    protected function encodeJson(array $data): string
    {
        return $this->helper('datasweep')->encodeJson($data);
    }

    /**
     * Converts a string to English characters and removes all quotes
     */
    protected function cleanAndEnglish(string $text): string
    {
        $clean = $this->helper('text')->toEnglishAlphabet($text);
        return str_replace(["'", '"'], "", $clean);
    }

    /**
     * AI veya dış API tarafından dönen markdown JSON sarmallarını temizler, onarır ve decode eder. 🧼🛠️
     */
    public function cleanJsonWrapper(string $rawText): ?array
    {
        $rawText = trim($rawText);

        // ```json ile başlıyorsa temizle
        if (strpos($rawText, '```json') === 0) {
            $rawText = substr($rawText, 7);
        } elseif (strpos($rawText, '```') === 0) {
            // Sadece ``` ile başlıyorsa temizle
            $rawText = substr($rawText, 3);
        }

        // ``` ile bitiyorsa temizle
        if (substr($rawText, -3) === '```') {
            $rawText = substr($rawText, 0, -3);
        }

        $cleanText = trim($rawText);

        // 🧼 1. Try direct json_decode first
        $decoded = json_decode($cleanText, true);

        // 🧼 2. Robust balanced bracket extraction (Extracts exact JSON object {...} or array [...])
        if ($decoded === null || !is_array($decoded)) {
            $extracted = $this->extractBalancedJson($cleanText);
            if (!empty($extracted)) {
                $cleanText = $extracted;
                $decoded = json_decode($cleanText, true);
            }
        }

        // If direct decode failed, sanitize raw control characters and try once more
        if (json_last_error() !== JSON_ERROR_NONE) {
            $sanitizedText = preg_replace('/[\x00-\x1F\x7F]/', '', $cleanText);
            $decoded = json_decode($sanitizedText, true);
        }

        // If it still fails, try to repair unescaped quotes
        if (json_last_error() !== JSON_ERROR_NONE) {
            $repairedText = $this->repairJson($cleanText);
            $decoded = json_decode($repairedText, true);
        }

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // 🔄 Robust Unwrapping of nested JSON (e.g. {"status": "success", "data": "```json {...} ```"})
            if (isset($decoded['data']) && is_string($decoded['data']) && !isset($decoded['content'])) {
                $cleanData = trim($decoded['data']);
                if (str_starts_with($cleanData, '```')) {
                    $cleanData = preg_replace('/^```(?:json)?\s*/i', '', $cleanData);
                    $cleanData = preg_replace('/\s*```$/', '', $cleanData);
                }
                $nestedDecoded = json_decode(trim($cleanData), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($nestedDecoded)) {
                    $decoded = array_merge($decoded, $nestedDecoded);
                }
            }

            // HTML karakter kaçışlarını düzelt (&amp; -> &)
            array_walk_recursive($decoded, function (&$item) {
                if (is_string($item)) {
                    $item = str_replace('&amp;', '&', $item);
                }
            });
            return array_merge(['success' => true], $decoded);
        }

        return null;
    }

    /**
     * JSON içindeki kaçırılmamış çift tırnakları ve yapısal olmayan hataları onarır. 🛠️
     */
    public function repairJson(string $json): string
    {
        $len = strlen($json);
        $inString = false;
        $escaped = false;
        $result = '';

        for ($i = 0; $i < $len; $i++) {
            $char = $json[$i];

            if ($inString) {
                if ($escaped) {
                    $result .= $char;
                    $escaped = false;
                } elseif ($char === '\\') {
                    $result .= $char;
                    $escaped = true;
                } elseif ($char === '"') {
                    // Bir sonraki boşluk olmayan karakteri kontrol et
                    $nextNonWhitespace = '';
                    for ($j = $i + 1; $j < $len; $j++) {
                        if (!ctype_space($json[$j])) {
                            $nextNonWhitespace = $json[$j];
                            break;
                        }
                    }

                    // Eğer yapısal bir JSON karakteri (virgül, süslü parantez vb.) takip ediyorsa string sonudur
                    if (in_array($nextNonWhitespace, [',', '}', ']', ':'])) {
                        $inString = false;
                        $result .= $char;
                    } else {
                        // Yapısal değilse, kaçırılmamış bir tırnaktır
                        $result .= '\\"';
                    }
                } else {
                    $result .= $char;
                }
            } else {
                if ($char === '"') {
                    $inString = true;
                }
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Extracts exact balanced JSON object ({...}) or array ([...]) from raw text 🎯
     */
    public function extractBalancedJson(string $text): ?string
    {
        $startPos = -1;
        $startChar = '';
        $endChar = '';

        // Find first opening { or [
        for ($i = 0; $i < strlen($text); $i++) {
            if ($text[$i] === '{') {
                $startPos = $i;
                $startChar = '{';
                $endChar = '}';
                break;
            } elseif ($text[$i] === '[') {
                $startPos = $i;
                $startChar = '[';
                $endChar = ']';
                break;
            }
        }

        if ($startPos === -1) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $len = strlen($text);

        for ($i = $startPos; $i < $len; $i++) {
            $char = $text[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
            } else {
                if ($char === '"') {
                    $inString = true;
                } elseif ($char === $startChar) {
                    $depth++;
                } elseif ($char === $endChar) {
                    $depth--;
                    if ($depth === 0) {
                        return substr($text, $startPos, $i - $startPos + 1);
                    }
                }
            }
        }

        return null;
    }
}
