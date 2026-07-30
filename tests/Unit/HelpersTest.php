<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testEEscapesQuotesAndTags(): void
    {
        $input = '<script>alert("xss")</script> & \'single\'';

        $escaped = e($input);

        self::assertStringNotContainsString('<script>', $escaped);
        self::assertStringContainsString('&lt;script&gt;', $escaped);
        self::assertStringContainsString('&quot;', $escaped);
        self::assertStringContainsString('&#039;', $escaped);
        self::assertStringContainsString('&amp;', $escaped);
    }

    public function testEHandlesNull(): void
    {
        self::assertSame('', e(null));
    }

    public function testPriceFormatsIntegerWithNbspSeparators(): void
    {
        $formatted = price(1290.0);

        self::assertSame("1\u{00A0}290\u{00A0}₽", $formatted);
    }

    public function testPriceFormatsLargeNumberWithGrouping(): void
    {
        $formatted = price(1234567.0);

        self::assertSame("1\u{00A0}234\u{00A0}567\u{00A0}₽", $formatted);
    }

    public function testPriceKeepsDecimalsWhenFractional(): void
    {
        $formatted = price(199.5);

        self::assertSame("199,50\u{00A0}₽", $formatted);
    }

    public function testSlugifyTransliteratesCyrillic(): void
    {
        self::assertSame('vaza-dlya-tsvetov', slugify('Ваза для цветов'));
    }

    public function testSlugifyLowercasesAndStripsPunctuation(): void
    {
        self::assertSame('svechi-aromaticheskie', slugify('Свечи, ароматические!'));
    }

    public function testSlugifyHandlesLatinInput(): void
    {
        self::assertSame('hello-world', slugify('Hello World'));
    }
}
