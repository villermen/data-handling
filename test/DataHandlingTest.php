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

    public function testStartsWith()
    {
        self::assertTrue(DataHandling::startsWith("some/String/", "some/S"));
        self::assertFalse(DataHandling::startsWith("some/string/", "some/S"));
        self::assertFalse(DataHandling::startsWith("somestring/", "some/s"));
        self::assertTrue(DataHandling::startsWithAlphanumeric(" So meString", "s OmEs"));
        self::assertFalse(DataHandling::startsWithAlphanumeric(" So meString", "son"));
    }

    public function testFormatPathAndDirectory()
    {
        chdir(__DIR__);

        // Without resolving
        self::assertEquals("path/to/file", DataHandling::formatPathOrUri("path/to/file"));
        self::assertEquals("/path/to/file", DataHandling::formatPathOrUri("/././//.///path//to\\file"));
        self::assertEquals("../path/to/file", DataHandling::formatPathOrUri("../path//to\\file"));
        self::assertEquals("/path/to/file", DataHandling::formatPathOrUri("/././//.//../path//to\\file"));
        self::assertEquals("../../path/file", DataHandling::formatPathOrUri("../../path//to/..\\file"));
        self::assertEquals("/file", DataHandling::formatPathOrUri("/././//.//path//to/..\\..\\file"));

        // With resolving
        $sanitizedWorkingDirectory = DataHandling::formatAndResolveDirectory(__DIR__);
        self::assertEquals($sanitizedWorkingDirectory . "fixtures/directory/file.txt", DataHandling::formatAndResolvePath("fixtures/directory/file.txt"));
        self::assertEquals($sanitizedWorkingDirectory . "fixtures/directory/file.txt", DataHandling::formatAndResolvePath("./fixtures/../fixtures//directory/file.txt"));

        try {
            DataHandling::formatAndResolvePath("fixtures/directory/doesnotexist.txt");
            self::fail();
        } catch (DataHandlingException $exception) {
        }

        // Path combination
        self::assertEquals("path/to/file", DataHandling::formatPathOrUri("path", "to", "file"));
        self::assertEquals("path/to/file", DataHandling::formatPathOrUri("path/", "to/file"));
        self::assertEquals("path/to/file", DataHandling::formatPathOrUri("path/", "/to//file"));
    }
}
