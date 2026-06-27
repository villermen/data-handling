<?php

use PHPUnit\Framework\TestCase;
use Villermen\DataHandling\Filter;

class FilterTest extends TestCase
{
    public function testMatch(): void
    {
        self::assertTrue(Filter::match('asdf', '*asdf'));
        self::assertTrue(Filter::match(' sdf', '*sdf'));
        self::assertTrue(Filter::match('file.txt', '*.txt'));
        self::assertFalse(Filter::match(' asdf', 'asdf'));
        self::assertTrue(Filter::match(' asdf', '?asdf'));
        self::assertTrue(Filter::match('asdf', 'as?f'));
        self::assertFalse(Filter::match('asdf', 'asd?f'));
        self::assertFalse(Filter::match('', '?'));
        self::assertTrue(Filter::match('d', '*?'));
        self::assertTrue(Filter::match('anything', '*?'));
        self::assertTrue(Filter::match('one\\two', 'one*t*'));
        self::assertTrue(Filter::match('one\\two', '*n*w?'));
        self::assertTrue(Filter::match('anything', '**'));
        self::assertTrue(Filter::match('', '*'));
        self::assertTrue(Filter::match('', '***'));
        self::assertTrue(Filter::match('', ''));
        self::assertTrue(Filter::match('aSDF', 'a???'));
        self::assertFalse(Filter::match('ASDF', 'a???'));
    }
}
