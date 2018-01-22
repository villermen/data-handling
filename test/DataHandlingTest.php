<?php

use Villermen\DataHandling\DataHandling;
use Villermen\DataHandling\DataHandlingException;

class DataHandlingTest extends PHPUnit_Framework_TestCase
{
    public function testExplode()
    {
        self::assertEquals(["Foo", "Bar", "Baz"], DataHandling::explode("Foo > Bar <   \\Baz "));
        self::assertEquals(["Foo", "Bar", "Baz"], DataHandling::explode("Foo k Bar j Baz", "kj"));
        self::assertEquals(["Foo", "Bar &  Baz"], DataHandling::explode("Foo ;  Bar &amp;  Baz"));
    }

    public function testImplode()
    {
        self::assertEquals("Foo -Bar -Baz", DataHandling::implode([4 => "Foo", 3 => "Bar", "Baz"], " -"));
    }

    public function testSanitizeUrl()
    {
        self::assertEquals("https://whatever.whatever", DataHandling::sanitizeUrl("https://whatever.whatever"));
    }

    public function testSanitizeString()
    {
        self::assertEquals("awesome teXt-", DataHandling::sanitizeString("   awesome teXt-   "));
        self::assertEquals("actually read able", DataHandling::sanitizeString("actually\nread\r\nable"));
        self::assertEquals("endlinebreak", DataHandling::sanitizeString("endlinebreak\n"));
        self::assertEquals("no HTML =<", DataHandling::sanitizeString("<i>no</span> <b>HTML</b> =<  <br>"));
        self::assertEquals("entity & decoding", DataHandling::sanitizeString("entity &amp; decoding"));
    }

    public function testSanitizeText()
    {
        self::assertEquals("<b>Text</b><br><i>Text</i>", DataHandling::sanitizeText("<b>Text</b><br><i>Text</i>"));
        self::assertEquals("derr", DataHandling::sanitizeText("<script><p>derr</p></script>"));
    }

    public function testSanitizeDigits()
    {
        self::assertEquals(8, DataHandling::sanitizeDigits("8a7s9f87"));
        self::assertEquals(782634234, DataHandling::sanitizeDigits("78.2634 2-34"));
    }

    public function testValidateInRange()
    {
        DataHandling::validateInRange(1.0, 1, 5);
        DataHandling::validateInRange(1.0, 1, 5);

        try {
            DataHandling::validateInRange(5.1, 1, 5);
            self::fail();
        } catch (DataHandlingException $exception) {
        }

        try {
            DataHandling::validateInRange(0.99999, 1, 5);
            self::fail();
        } catch (DataHandlingException $exception) {
        }

        try {
            DataHandling::validateInRange(null, 1, 5) ;
            self::fail();
        } catch (DataHandlingException $exception) {
        }
    }

    public function testValidateInArray()
    {
        DataHandling::validateInArray(5, [ 0, 5, 0]);
        DataHandling::validateInArray(5, [ 0, "5", 0]);

        try {
            DataHandling::validateInArray(4, [ 5, 0, 5]);
            self::fail();
        } catch (DataHandlingException $exception) {
        }

        try {
            DataHandling::validateInArray(null, [ 5, null, 5]);
            self::fail();
        } catch (DataHandlingException $exception) {
        }
    }

    public function testSanitizeUrlParts()
    {
        self::assertEquals("sseyzdj", DataHandling::sanitizeUrlParts("ßÈÿžÐ"));
        self::assertEquals("asdf-asdf", DataHandling::sanitizeUrlParts(" aSDF   asdf   "));
        self::assertEquals("asdf-asdf-asdf", DataHandling::sanitizeUrlParts("-asdf---asdf   - asdf_"));
        self::assertEquals("asd.f", DataHandling::sanitizeUrlParts("_a;s/[d.f"));
        self::assertEquals("asdf", DataHandling::sanitizeUrlParts("as/df"));
        self::assertEquals("asdf/zxcv", DataHandling::sanitizeUrlParts("asdf", "zxcv"));
        self::assertEquals("foo/bar", DataHandling::sanitizeUrlParts(["foo", "bar"]));
        self::assertEquals("baz//bar", DataHandling::sanitizeUrlParts(["baz", "---- ", "bar"]));
    }

    public function testSanitizeAlphanumeric()
    {
        self::assertEquals("asdfasdf", DataHandling::sanitizeAlphanumeric(" Asdf. aSD f-", $mapping));
        self::assertEquals([[0, 1], [4, 2], [7, 1], [8, 1]], $mapping);
    }

    public function testFindInString()
    {
        self::assertEquals([7, 6], DataHandling::findInString("AAaA._.ABa._Ab_._", "ABaa"));

        $haystack = "irrelevant-text.match-text,,irrelevant-text---match-text--";
        $needle = "-mat---chtext--";
        $match = DataHandling::findInString($haystack, $needle);
        self::assertEquals("irrelevant-text.,,irrelevant-text---match-text--", substr($haystack, 0, $match[0]) . substr($haystack, $match[0] + $match[1]));

        $match = DataHandling::findInString($haystack, $needle, true);
        self::assertEquals("irrelevant-textirrelevant-text---match-text--", substr($haystack, 0, $match[0]) . substr($haystack, $match[0] + $match[1]));
    }

    public function testStartsAndEndsWith()
    {
        self::assertTrue(DataHandling::startsWith("some/String/", "some/S"));
        self::assertFalse(DataHandling::startsWith("some/string/", "some/S"));
        self::assertFalse(DataHandling::startsWith("somestring/", "some/s"));
        self::assertTrue(DataHandling::startsWithAlphanumeric(" So meString", "s OmEs"));
        self::assertFalse(DataHandling::startsWithAlphanumeric(" So meString", "son"));

        self::assertTrue(DataHandling::endsWith("some/String/", "ing/"));
        self::assertTrue(DataHandling::endsWithAlphanumeric("somestri Ng", "ing"));
    }

    public function testFormatPathAndDirectory()
    {
        chdir(__DIR__);

        // Without resolving
        self::assertEquals("path/to/file", DataHandling::formatPath("path/to/file"));
        self::assertEquals("/path/to/file", DataHandling::formatPath("/././//.///path//to\\file"));
        self::assertEquals("../path/to/file", DataHandling::formatPath("../path//to\\file"));
        self::assertEquals("/path/to/file", DataHandling::formatPath("/././//.//../path//to\\file"));
        self::assertEquals("../../path/file", DataHandling::formatPath("../../path//to/..\\file"));
        self::assertEquals("/file", DataHandling::formatPath("/././//.//path//to/..\\..\\file"));

        // With resolving
        $sanitizedWorkingDirectory = DataHandling::formatAndResolveDirectory(__DIR__);
        self::assertEquals($sanitizedWorkingDirectory . "fixtures/directory/file.txt", DataHandling::formatAndResolvePath("fixtures/directory/file.txt"));
        self::assertEquals($sanitizedWorkingDirectory . "fixtures/directory/file.txt", DataHandling::formatAndResolvePath("./fixtures/../fixtures//directory/file.txt"));
        self::assertEquals($sanitizedWorkingDirectory . "fixtures/directory/€' é+ÿ€.txt", DataHandling::formatAndResolvePath("fixtures/directory/%E2%82%AC'%20%C3%A9+%C3%BF€.txt"));

        try {
            DataHandling::formatAndResolvePath("fixtures/directory/doesnotexist.txt");
            self::fail();
        } catch (DataHandlingException $exception) {
        }

        // Path combination
        self::assertEquals("path/to/file", DataHandling::mergePaths(["path", "to", "file"]));
        self::assertEquals("path/to/file", DataHandling::mergePaths("path", "to", "file"));
        self::assertEquals("path/to/file", DataHandling::mergePaths("path/", "to/file"));
        self::assertEquals("path/to/file/", DataHandling::mergePaths("path/", "/to/file/"));
        self::assertEquals("to/file", DataHandling::mergePaths("", "/to/file"));
        self::assertEquals("/to/file", DataHandling::mergePaths("/", "/to/file"));
        self::assertEquals("", DataHandling::mergePaths([]));
    }

    public function testMakePathRelative()
    {
        self::assertEquals("file", DataHandling::makePathRelative("/path/to/file", "/path/to"));
        self::assertEquals("file", DataHandling::makePathRelative("/path/to/file", "/path/to/"));
        self::assertEquals("", DataHandling::makePathRelative("/path/to/file", "/path/to/file"));
        self::assertEquals("", DataHandling::makePathRelative("/path/to/file/", "/path/to/file"));
        self::assertEquals("", DataHandling::makePathRelative("/path/to/file", "/path/to/file/"));
        self::assertEquals("", DataHandling::makePathRelative("/", "/"));
        self::assertEquals("file", DataHandling::makePathRelative("/path/../path/to/./file", "/path/to/../to/./"));

        try {
            DataHandling::makePathRelative("/path/to", "/path/to/file");
            self::fail();
        } catch (DataHandlingException $exception) {
        }
    }

    public function testFormatBytesize()
    {
        self::assertEquals("1023 B", DataHandling::formatBytesize(1023));
        self::assertEquals("1 KiB", DataHandling::formatBytesize(1024));
        self::assertEquals("1.34 GiB", DataHandling::formatBytesize(1370 * 1024 * 1024));
        self::assertEquals("347 MiB", DataHandling::formatBytesize(354919 * 1024));
        self::assertEquals("0 B", DataHandling::formatBytesize(0));
    }

    public function testMatchesFilter()
    {
        self::assertTrue(DataHandling::matchesFilter("asdf", "*asdf"));
        self::assertTrue(DataHandling::matchesFilter(" sdf", "*sdf"));
        self::assertTrue(DataHandling::matchesFilter("file.txt", "*.txt"));
        self::assertFalse(DataHandling::matchesFilter(" asdf", "asdf"));
        self::assertTrue(DataHandling::matchesFilter(" asdf", "?asdf"));
        self::assertTrue(DataHandling::matchesFilter("asdf", "as?f"));
        self::assertFalse(DataHandling::matchesFilter("asdf", "asd?f"));
        self::assertFalse(DataHandling::matchesFilter("", "?"));
        self::assertTrue(DataHandling::matchesFilter("d", "*?"));
        self::assertTrue(DataHandling::matchesFilter("anything", "*?"));
        self::assertTrue(DataHandling::matchesFilter("one\\two", "one*t*"));
        self::assertTrue(DataHandling::matchesFilter("one\\two", "*n*w?"));
        self::assertTrue(DataHandling::matchesFilter("anything", "**"));
        self::assertTrue(DataHandling::matchesFilter("", "*"));
        self::assertTrue(DataHandling::matchesFilter("", "***"));
        self::assertTrue(DataHandling::matchesFilter("", ""));

        self::assertTrue(DataHandling::matchesFilter(["yasdf"], ["nope", "ya???"]));
        self::assertFalse(DataHandling::matchesFilter(["asdf", "nope"], ["asd*", "nope?"]));
        self::assertTrue(DataHandling::matchesFilter(["asdf", "nope"], ["asd*", "nop?"]));
        self::assertTrue(DataHandling::matchesFilter([], []));
        self::assertFalse(DataHandling::matchesFilter([""], []));
        self::assertFalse(DataHandling::matchesFilter(["anything"], []));

        self::assertTrue(DataHandling::matchesFilterInsensitive("oNE\\TwO", "onE*t*"));
    }
}
