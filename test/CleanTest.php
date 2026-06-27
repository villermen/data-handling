<?php

use PHPUnit\Framework\TestCase;
use Villermen\DataHandling\Clean;

class CleanTest extends TestCase
{
    public function testText(): void
    {
        self::assertSame('awesome teXt-', Clean::text('   awesome teXt-   '));
        self::assertSame("with\nline\nbreaks\n\n", Clean::text("with\r\nline\rbreaks\n\r"));
        self::assertSame('no HTML =<', Clean::text('<i>no</span> <b>HTML</b> =<  <br>'));
        self::assertSame('entity & decoding', Clean::text('entity &amp; decoding'));
        self::assertSame("<b>Text</b><br>\n<i>Text</i>", Clean::text("<b>Text</b><br>\n<i>Text</i>", ['b','br','i']));
        self::assertSame("derr \n", Clean::text("<script><p><b>derr</b></p> \n</script>"));
    }

    public function testDigits(): void
    {
        self::assertSame('87987', Clean::digits('8a7s9f87'));
        self::assertSame('782634234', Clean::digits('78.2634 2-34'));
    }

    public function testSlug(): void
    {
        self::assertSame('sseyzdj', Clean::slug('ßÈÿžÐ'));
        self::assertSame('asdf-asdf', Clean::slug(' aSDF   asdf   '));
        self::assertSame('asdf-asdf-asdf', Clean::slug('-asdf---asdf   - asdf_'));
        self::assertSame('asd.f', Clean::slug('_a;s/[d.f'));
        self::assertSame('asdf', Clean::slug('as/df'));
    }

    public function testAlphanumeric(): void
    {
        self::assertSame('asdfasdf', Clean::alphanumeric(' Asdf. aSD f-'));
        self::assertSame('string/with-extra-characters', Clean::alphanumeric('string/with-extra-characters', '/-'));
    }
}
