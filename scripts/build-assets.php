<?php

declare(strict_types=1);

/**
 * scripts/build-assets.php — pure-PHP asset build (no npm/node involved).
 *
 * Reads public/assets/css/{app,admin}.css and public/assets/js/{app,admin}.js, minifies
 * them conservatively, and writes the result to public/assets/dist/*.min.{css,js}.
 *
 * Conservative by design (correctness over maximum compression):
 *   - CSS: strips /* ... *\/ comments and collapses whitespace, and only removes the
 *     space directly touching { } : ; , > + ~ . String literals ('...'/"...") and the
 *     full contents of url(...) are copied through byte-for-byte, untouched.
 *   - JS: only strips `//` line comments that occur *outside* of string/template literals,
 *     and collapses indentation/blank lines. Block comments and code are otherwise left
 *     alone — this script does not attempt to parse or rewrite JavaScript.
 *
 * As a build-time safety net, every CSS file is checked for open/close brace balance
 * against its source before being written; a mismatch aborts the build with exit code 1.
 *
 * Run via `composer build` or `php scripts/build-assets.php`.
 */

const ASSET_MAP = [
    ['src' => __DIR__ . '/../public/assets/css/app.css', 'dist' => __DIR__ . '/../public/assets/dist/app.min.css', 'type' => 'css'],
    ['src' => __DIR__ . '/../public/assets/css/admin.css', 'dist' => __DIR__ . '/../public/assets/dist/admin.min.css', 'type' => 'css'],
    ['src' => __DIR__ . '/../public/assets/js/app.js', 'dist' => __DIR__ . '/../public/assets/dist/app.min.js', 'type' => 'js'],
    ['src' => __DIR__ . '/../public/assets/js/admin.js', 'dist' => __DIR__ . '/../public/assets/dist/admin.min.js', 'type' => 'js'],
];

/**
 * Minify CSS. Strips comments, collapses runs of whitespace to a single space, and drops
 * the space immediately adjacent to { } : ; , > + ~ — but never inside a quoted string or
 * inside url(...), which are copied through verbatim.
 */
function minify_css(string $code): string
{
    static $noSpaceChars = ['{', '}', ':', ';', ',', '>', '+', '~'];

    $len = strlen($code);
    $i = 0;
    $out = '';
    $pendingSpace = false;

    $resolvePendingSpace = static function (string $out, bool &$pendingSpace, string $upcoming) use ($noSpaceChars): string {
        if (!$pendingSpace) {
            return $out;
        }
        $pendingSpace = false;
        $prev = $out === '' ? '' : $out[strlen($out) - 1];
        if ($prev === '' || in_array($prev, $noSpaceChars, true)) {
            return $out;
        }
        if ($upcoming !== '' && in_array($upcoming, $noSpaceChars, true)) {
            return $out;
        }

        return $out . ' ';
    };

    while ($i < $len) {
        $ch = $code[$i];

        // Block comment — drop entirely; always leave a boundary space behind so we never
        // accidentally fuse two tokens that only had a comment (no real whitespace) between them.
        if ($ch === '/' && $i + 1 < $len && $code[$i + 1] === '*') {
            $close = strpos($code, '*/', $i + 2);
            $i = $close === false ? $len : $close + 2;
            $pendingSpace = true;
            continue;
        }

        // Whitespace run -> collapse to one pending space.
        if ($ch === ' ' || $ch === "\t" || $ch === "\n" || $ch === "\r" || $ch === "\f") {
            $i++;
            while ($i < $len && strpos(" \t\n\r\f", $code[$i]) !== false) {
                $i++;
            }
            $pendingSpace = true;
            continue;
        }

        // Quoted string — copy through untouched, including any punctuation/whitespace inside.
        if ($ch === "'" || $ch === '"') {
            $out = $resolvePendingSpace($out, $pendingSpace, $ch);
            $quote = $ch;
            $start = $i;
            $i++;
            while ($i < $len) {
                if ($code[$i] === '\\' && $i + 1 < $len) {
                    $i += 2;
                    continue;
                }
                if ($code[$i] === $quote) {
                    $i++;
                    break;
                }
                $i++;
            }
            $out .= substr($code, $start, $i - $start);
            continue;
        }

        // url(...) — copy through untouched (handles unquoted url()s, e.g. data: URIs, which
        // may legitimately contain ; and , that must not be treated as CSS punctuation).
        if (($ch === 'u' || $ch === 'U') && preg_match('/\Gurl\s*\(/i', $code, $m, 0, $i) === 1) {
            $out = $resolvePendingSpace($out, $pendingSpace, 'u');
            $start = $i;
            $i += strlen($m[0]);
            while ($i < $len && $code[$i] !== ')') {
                if ($code[$i] === "'" || $code[$i] === '"') {
                    $quote = $code[$i];
                    $i++;
                    while ($i < $len && $code[$i] !== $quote) {
                        if ($code[$i] === '\\' && $i + 1 < $len) {
                            $i += 2;
                            continue;
                        }
                        $i++;
                    }
                    if ($i < $len) {
                        $i++;
                    }
                    continue;
                }
                $i++;
            }
            if ($i < $len) {
                $i++; // closing ')'
            }
            $out .= substr($code, $start, $i - $start);
            continue;
        }

        // Plain character, including the punctuation marks we compact spacing around.
        $out = $resolvePendingSpace($out, $pendingSpace, $ch);
        $out .= $ch;
        $i++;
    }

    return trim($out) . "\n";
}

/**
 * Minify JS: strip `//` line comments outside of string/template literals, then collapse
 * leading indentation and drop blank lines. Block comments, strings and code structure are
 * left completely untouched — this is intentionally not a real JS parser.
 */
function minify_js(string $code): string
{
    $lines = preg_split('/\r\n|\r|\n/', $code);
    $inString = null; // "'" | '"' | '`' | null
    $inBlockComment = false;
    $outLines = [];

    foreach ($lines as $line) {
        $len = strlen($line);
        $i = 0;
        $result = '';
        $lineStartedInString = $inString !== null;
        $lineStartedInBlockComment = $inBlockComment;

        while ($i < $len) {
            $ch = $line[$i];
            $next = $i + 1 < $len ? $line[$i + 1] : '';

            if ($inBlockComment) {
                $result .= $ch;
                if ($ch === '*' && $next === '/') {
                    $result .= $next;
                    $i += 2;
                    $inBlockComment = false;
                    continue;
                }
                $i++;
                continue;
            }

            if ($inString !== null) {
                $result .= $ch;
                if ($ch === '\\') {
                    if ($i + 1 < $len) {
                        $result .= $line[$i + 1];
                        $i += 2;
                        continue;
                    }
                    $i++;
                    continue;
                }
                if ($ch === $inString) {
                    $inString = null;
                }
                $i++;
                continue;
            }

            if ($ch === '/' && $next === '/') {
                // Rest of the physical line is a line comment — drop it.
                break;
            }

            if ($ch === '/' && $next === '*') {
                $inBlockComment = true;
                $result .= $ch . $next;
                $i += 2;
                continue;
            }

            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $inString = $ch;
                $result .= $ch;
                $i++;
                continue;
            }

            $result .= $ch;
            $i++;
        }

        // Lines that are a continuation of a multi-line string/template literal or block
        // comment are data/formatting, not code layout — leave them exactly as they were.
        if ($lineStartedInString || $lineStartedInBlockComment) {
            $outLines[] = $result;
            continue;
        }

        $trimmed = trim($result);
        if ($trimmed === '') {
            continue; // drop blank lines and now-empty (comment-only) lines
        }
        $outLines[] = $trimmed;
    }

    return implode("\n", $outLines) . "\n";
}

/** Count of a single-character token, ignoring nothing — used only for the CSS brace sanity check. */
function count_char(string $haystack, string $char): int
{
    return substr_count($haystack, $char);
}

function human_bytes(int $bytes): string
{
    return number_format($bytes) . ' B';
}

function fail(string $message): never
{
    fwrite(STDERR, "build-assets: ERROR: {$message}\n");
    exit(1);
}

function main(): void
{
    $distDir = __DIR__ . '/../public/assets/dist';
    if (!is_dir($distDir) && !mkdir($distDir, 0775, true) && !is_dir($distDir)) {
        fail("Could not create dist directory: {$distDir}");
    }

    $rows = [];

    foreach (ASSET_MAP as $asset) {
        $src = $asset['src'];
        $dist = $asset['dist'];
        $type = $asset['type'];

        if (!is_file($src)) {
            fail("Source file not found: {$src}");
        }

        $original = file_get_contents($src);
        if ($original === false) {
            fail("Could not read: {$src}");
        }

        $minified = $type === 'css' ? minify_css($original) : minify_js($original);

        if ($type === 'css') {
            $openOriginal = count_char($original, '{');
            $closeOriginal = count_char($original, '}');
            $openMin = count_char($minified, '{');
            $closeMin = count_char($minified, '}');

            if ($openOriginal !== $openMin || $closeOriginal !== $closeMin) {
                fail(sprintf(
                    "Brace count mismatch while minifying %s: source has %d '{' / %d '}', "
                    . "minified output has %d '{' / %d '}'. Aborting to avoid shipping broken CSS.",
                    basename($src),
                    $openOriginal,
                    $closeOriginal,
                    $openMin,
                    $closeMin
                ));
            }
            if ($openMin !== $closeMin) {
                fail(sprintf(
                    "Minified %s is unbalanced: %d '{' vs %d '}'. Aborting.",
                    basename($src),
                    $openMin,
                    $closeMin
                ));
            }
        }

        if (trim($minified) === '') {
            fail("Minified output for {$src} is empty — refusing to write it.");
        }

        $bytesWritten = file_put_contents($dist, $minified);
        if ($bytesWritten === false) {
            fail("Could not write: {$dist}");
        }

        $before = strlen($original);
        $after = strlen($minified);
        $savingsPct = $before > 0 ? (1 - $after / $before) * 100 : 0.0;

        $rows[] = [
            'file' => basename($src) . ' -> ' . basename($dist),
            'before' => $before,
            'after' => $after,
            'savings' => $savingsPct,
        ];
    }

    print_table($rows);
}

/**
 * @param array<int, array{file: string, before: int, after: int, savings: float}> $rows
 */
function print_table(array $rows): void
{
    $fileWidth = max(4, ...array_map(static fn (array $r): int => strlen($r['file']), $rows));
    $fileWidth = max($fileWidth, strlen('File'));

    $header = sprintf(
        '%-' . $fileWidth . 's  %10s  %10s  %10s',
        'File',
        'Before',
        'After',
        'Savings'
    );
    echo $header . "\n";
    echo str_repeat('-', strlen($header)) . "\n";

    foreach ($rows as $row) {
        echo sprintf(
            '%-' . $fileWidth . 's  %10s  %10s  %9.1f%%' . "\n",
            $row['file'],
            human_bytes($row['before']),
            human_bytes($row['after']),
            $row['savings']
        );
    }
}

main();
