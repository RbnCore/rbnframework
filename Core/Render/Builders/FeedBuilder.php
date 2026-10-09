<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Builders;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * FeedBuilder - Stateful accumulator and RSS XML generator 📡📰⚓
 * Part of RBN Framework.
 */
class FeedBuilder extends BaseComponent
{
    /** @var array Accumulated posts */
    protected array $posts = [];

    /** @var array Channel metadata */
    protected array $channel = [];

    /**
     * Set channel metadata.
     */
    public function setChannel(array $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * Add posts array.
     */
    public function addPosts(array $posts): self
    {
        $this->posts = $posts;
        return $this;
    }

    /**
     * Generates valid RSS 2.0 XML payload.
     */
    public function buildXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">' . PHP_EOL;
        $xml .= '<channel>' . PHP_EOL;
        $xml .= '  <title>' . htmlspecialchars($this->channel['title'] ?? '') . '</title>' . PHP_EOL;
        $xml .= '  <link>' . htmlspecialchars($this->channel['channelLink'] ?? '') . '</link>' . PHP_EOL;
        $xml .= '  <description>' . htmlspecialchars($this->channel['description'] ?? '') . '</description>' . PHP_EOL;
        $xml .= '  <language>tr</language>' . PHP_EOL;
        $xml .= '  <atom:link href="' . htmlspecialchars($this->channel['feedLink'] ?? '') . '" rel="self" type="application/rss+xml" />' . PHP_EOL;

        if (!empty($this->posts)) {
            $xml .= '  <lastBuildDate>' . ($this->posts[0]['pubDate'] ?? date(DATE_RSS)) . '</lastBuildDate>' . PHP_EOL;
        }

        foreach ($this->posts as $post) {
            $xml .= '  <item>' . PHP_EOL;
            $xml .= '    <title>' . htmlspecialchars($this->clean((string)($post['title'] ?? ''), true), ENT_NOQUOTES, 'UTF-8') . '</title>' . PHP_EOL;
            $xml .= '    <link>' . htmlspecialchars($this->clean((string)($post['link'] ?? ''), true), ENT_NOQUOTES, 'UTF-8') . '</link>' . PHP_EOL;
            $xml .= '    <guid isPermaLink="true">' . htmlspecialchars($this->clean((string)($post['link'] ?? ''), true), ENT_NOQUOTES, 'UTF-8') . '</guid>' . PHP_EOL;
            $xml .= '    <pubDate>' . ($post['pubDate'] ?? date(DATE_RSS)) . '</pubDate>' . PHP_EOL;
            $xml .= '    <description><![CDATA[' . $this->clean((string)($post['description'] ?? '')) . ']]></description>' . PHP_EOL;

            if (!empty($post['image'])) {
                $xml .= '    <enclosure url="' . htmlspecialchars(url($this->clean((string)$post['image'], true))) . '" type="image/jpeg" />' . PHP_EOL;
            }
            $xml .= '  </item>' . PHP_EOL;
        }

        $xml .= '</channel>' . PHP_EOL;
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Clean strings from invalid XML characters and strip newlines if required 🛡️
     */
    protected function clean(string $string, bool $stripNewlines = false): string
    {
        if (empty($string)) return '';
        
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        
        if ($stripNewlines) {
            $string = str_replace(["\r", "\n", "\t"], ' ', $string);
        }
        
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    }
}
