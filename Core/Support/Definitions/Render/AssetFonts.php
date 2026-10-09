<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Definitions\Render;

/**
 * AssetFonts - The RBN Framework Font Dictionary 🏺🎨⚓
 * 
 * RBN Framework Architecture.
 * This file serves as the centralized library for all typographic assets.
 * Allows decoupling fonts from general assets for better orchestration.
 */
class AssetFonts
{
    /**
     * FONT_LIBRARY - Comprehensive collection of ready-to-use fonts. 🎡⚓
     */
    public const FONT_LIBRARY = [
        // ── Modern Sans-Serif (SaaS, Minimal & Modern UI) ─────────────
        'Inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap',
        'Outfit' => 'https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap',
        'Plus Jakarta Sans' => 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap',
        'Poppins' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap',
        'Manrope' => 'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap',
        'Geist' => 'https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap',
        'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap',
        'Montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap',
        'IBM Plex Sans' => 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap',

        // ── Expressive & Editorial Display Serif ───────────────────────
        'Fraunces' => 'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,100..900;1,9..144,100..900&display=swap',
        'Newsreader' => 'https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&display=swap',
        'Instrument Serif' => 'https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&display=swap',
        'DM Serif Display' => 'https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&display=swap',
        'Cormorant Garamond' => 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap',
        'EB Garamond' => 'https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&display=swap',
        'Bodoni Moda' => 'https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&display=swap',
        'Cardo' => 'https://fonts.googleapis.com/css2?family=Cardo:ital,wght@0,400;0,700;1,400&display=swap',
        'Source Serif 4' => 'https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,opsz,wght@0,8..60,200..900;1,8..60,200..900&display=swap',
        'Spectral' => 'https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap',
        'Crimson Pro' => 'https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap',
        'Playfair Display' => 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap',
        'Lora' => 'https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&display=swap',

        // ── Brutalist, Poster & Dynamic Sans ───────────────────────────
        'Bricolage Grotesque' => 'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&display=swap',
        'Space Grotesk' => 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap',
        'Syne' => 'https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&display=swap',
        'Anton' => 'https://fonts.googleapis.com/css2?family=Anton&display=swap',
        'Big Shoulders Display' => 'https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@100..900&display=swap',
        'Tomorrow' => 'https://fonts.googleapis.com/css2?family=Tomorrow:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap',

        // ── Monospace (Code, Terminal & Technical Badges) ───────────────
        'JetBrains Mono' => 'https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap',
        'Space Mono' => 'https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap',
        'Geist Mono' => 'https://fonts.googleapis.com/css2?family=Geist+Mono:wght@100..900&display=swap',
        'IBM Plex Mono' => 'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap',
        'Fira Code' => 'https://fonts.googleapis.com/css2?family=Fira+Code:wght@300;400;500;600;700&display=swap'
    ];

    /**
     * 🏷️ Direct Access Aliases (For autocomplete support)
     */
    public const INTER = 'Inter';
    public const OUTFIT = 'Outfit';
    public const GEIST = 'Geist';
    public const JETBRAINS_MONO = 'JetBrains Mono';
    public const SPACE_MONO = 'Space Mono';
    public const GEIST_MONO = 'Geist Mono';
    public const PLUS_JAKARTA_SANS = 'Plus Jakarta Sans';
    public const POPPINS = 'Poppins';
    public const SYNE = 'Syne';
    public const MANROPE = 'Manrope';
    public const FRAUNCES = 'Fraunces';
    public const NEWSREADER = 'Newsreader';
    public const INSTRUMENT_SERIF = 'Instrument Serif';
    public const DM_SERIF_DISPLAY = 'DM Serif Display';
    public const BRICOLAGE_GROTESQUE = 'Bricolage Grotesque';
    public const SPACE_GROTESK = 'Space Grotesk';
}
