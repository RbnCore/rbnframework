<?php

namespace Rbn\Framework\Core\Routes\Engine;

/**
 * Matcher - Route Finding Logic
 * 
 * Compiles URI patterns into regex and matches incoming requests.
 */
class Matcher
{
    private array $routes;

    /** @var array Compiled regex pattern cache 🧠⚡ */
    private static array $patternCache = [];

    public function __construct(array $routes = [])
    {
        $this->routes = $routes;
    }

    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    /**
     * Match a URI and method against collected routes
     */
    public function match(string $uri, string $method): ?array
    {
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'])) {
                continue;
            }

            $pattern = $this->compilePattern($route['uri']);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // First match is full URI
                
                // 🛡️ Safe Parameter Filter: Keep "0" and 0 parameters intact
                $params = array_filter($matches, function ($val) {
                    return $val !== null && $val !== '';
                });

                return [
                    'uri' => $route['uri'],
                    'action' => $route['action'],
                    'params' => array_values($params),
                    'middleware' => $route['middleware']
                ];
            }
        }

        return null;
    }

    /**
     * [R-08] Bir `{...}` parametre blogunu AYRIŞTIRIR.
     *
     * ESKI KODUN SORUNU: bloklar `\{[^{}]+\}` ile cekiliyordu. Bu desen
     * IC ICE parantez kumesi iceren bir blogu YAKALAMAZ (cift kume ic ice ge-
     * miyor). Sonraki asamada `(:([^}]+))?` ilk `}`'de durdugu icin
     * `{key:[a-f0-9]{64}}` -> regex = `([a-f0-9]{64)` ve kalan `}` deseni
     * bozuyordu. Sonuc: IndexNow dogrulama endpoint'i kalici 404.
     *
     * @return array{optional: bool, regex: string}|null null -> token degil,Oldugu gibi literal
     */
    private static function parseToken(string $block): ?array
    {
        // {ad} · {ad?} · {ad:regex} · {ad?:regex}
        if (!preg_match('/^([a-zA-Z0-9_]+)(\?)?(?::(.+))?$/s', $block, $m)) {
            return null;
        }

        $regex = ($m[3] ?? '') !== '' ? $m[3] : '[a-zA-Z0-9_-]+';

        // [YR-6] Ayirac `~` kullanilir; ozel regex icinde de kacisli olmali,
        // aksi halde `~^...$~` erken kapanir ve rota hicbir zaman eslesmez.
        $regex = (string) preg_replace('/(?<!\\\\)~/', '\\~', $regex);

        return [
            'optional' => ($m[2] ?? '') === '?',
            'regex' => $regex,
        ];
    }

    /**
     * [R-08] URI'nin LITERAL (token disi) kismini regex'e cevirir.
     *
     * Yalnizca `.` ve ayirac `~` kacislanir — onceki davranisin BIREBIR
     * korunmasi icin. Boylece mevcut 456 rotanin hicbiri degismez.
     */
    private static function escapeLiteral(string $literal): string
    {
        return str_replace(['.', '~'], ['\.', '\~'], $literal);
    }

    /**
     * URI pattern to regex (Memoized)
     *
     * [R-08] Token blogu TEK GECITTE, DENGELI parantez sayaciyla bulunur
     * (ic ice `{n}` kume kuantifierlari olan ozel regex'ler de dogru ayrisir).
     * [YR-6] Ayirac `~` literal kisimda da kacislanir.
     */
    private function compilePattern(string $uri): string
    {
        if (isset(self::$patternCache[$uri])) {
            return self::$patternCache[$uri];
        }

        $rawUri = '/' . trim($uri, '/');

        // 🎼 RBN Framework: [RBN Framework MATCHING ENGINE] 🛰️🪐⚓
        // Support for optional parameters {param?} and custom regex {param:regex}

        // [R-08] `match()` gelen URI'yi `'/'.trim($uri,'/')` ile normalize eder,
        // yani giren URI DAIMA tek basinda `/` tasir. Rota tanimi basinda `/`
        // OLMADAN yazilabilir (`{key:[a-f0-9]{64}}.txt`, `{slug}-dizileri`).
        // Desen de ayni bicimde kurulmali; aksi halde `{key:...}` deseni hicbir
        // zaman eslesmez. `$rawUri` daha once hesaplaniyor ama HICBIR YERDE
        // kullanilmiyordu (ol kod).
        $uri = $rawUri;

        $len = strlen($uri);
        $out = '';
        $literal = '';
        $i = 0;

        while ($i < $len) {
            if ($uri[$i] !== '{') {
                $literal .= $uri[$i];
                $i++;
                continue;
            }

            // Dengeli kapanis ara: derinlik sayaci.
            $depth = 0;
            $end = -1;
            for ($j = $i; $j < $len; $j++) {
                if ($uri[$j] === '{') {
                    $depth++;
                } elseif ($uri[$j] === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $end = $j;
                        break;
                    }
                }
            }

            // Dengesiz (acik) kume: token degil, literal say.
            if ($end === -1) {
                $literal .= $uri[$i];
                $i++;
                continue;
            }

            $token = self::parseToken(substr($uri, $i + 1, $end - $i - 1));

            if ($token === null) {
                // Taninmayan blok: aynen literal olarak koru.
                $literal .= substr($uri, $i, $end - $i + 1);
                $i = $end + 1;
                continue;
            }

            $out .= self::escapeLiteral($literal);
            $literal = '';

            // Onundeki `/` regex'te `\/` olmali; tokeni gomulu degilse
            // (`sitemap-{type}.xml`) slash YOK.
            $slash = str_ends_with($out, '/');
            if ($slash) {
                $out = substr($out, 0, -1);
            }

            $group = '(' . $token['regex'] . ')';
            $out .= $token['optional']
                ? ($slash ? '(?:\/' . $group . ')?' : $group . '?')
                : ($slash ? '\/' . $group : $group);

            $i = $end + 1;
        }

        $out .= self::escapeLiteral($literal);

        // 🎼 RBN Framework: [RBN Framework DELIMITER]
        // Using ~ as delimiter to avoid escaping slashes (/)
        $pattern = "~^" . $out . "$~";

        // [R-08] Bozuk desen uretilirse istek hicbir zaman eslesmesin
        // (fail-closed) ve PHP "Unknown modifier" uyarisi basmasin.
        if (@preg_match($pattern, '') === false) {
            self::$patternCache[$uri] = '~^(?!)~';
            return self::$patternCache[$uri];
        }

        return self::$patternCache[$uri] = $pattern;
    }
}
