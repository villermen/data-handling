<?php

use PHPUnit\Framework\TestCase;
use Villermen\DataHandling\Path;

class PathTest extends TestCase
{
    public function testFormat(): void
    {
        self::assertSame('path/to/file', Path::format('path/to/file'));
        self::assertSame('/path/to/file', Path::format('/././//.///path//to\\file'));
        self::assertSame('../path/to/file', Path::format('../path//to\\file'));
        self::assertSame('/path/to/file', Path::format('/././//.//../path//to\\file'));
        self::assertSame('../../path/file', Path::format('../../path//to/..\\file'));
        self::assertSame('/file/', Path::format('/././//.//path//to/..\\..\\file/'));

        self::assertSame('foo/bar', Path::format('foo', 'bar'));
        self::assertSame('/foo/bar/', Path::format('/foo/', '/bar/'));
        self::assertSame('/', Path::format('/'));
        self::assertSame('/', Path::format('/', '/'));
        self::assertSame('/foo/', Path::format('/', 'foo', '/'));
        self::assertSame('foo', Path::format('', '/foo'));
    }

    public function testMakeRelative(): void
    {
        self::assertSame('file', Path::makeRelative('/path/to/file', '/path/to'));
        self::assertSame('file', Path::makeRelative('/path/to/file', '/path/to/'));
        self::assertSame('', Path::makeRelative('/path/to/file', '/path/to/file'));
        self::assertSame('', Path::makeRelative('/path/to/file/', '/path/to/file'));
        self::assertSame('', Path::makeRelative('/path/to/file', '/path/to/file/'));
        self::assertSame('', Path::makeRelative('/', '/'));
        self::assertSame('file', Path::makeRelative('/path/../path/to/./file', '/path/to/../to/./'));
        self::assertNull(Path::makeRelative('/path/to', '/path/to/file'));
    }

    public function testFormatFilesize(): void
    {
        self::assertSame('1023 B', Path::formatFilesize(1023));
        self::assertSame('1 KiB', Path::formatFilesize(1024));
        self::assertSame('1.34 GiB', Path::formatFilesize(1370 * 1024 * 1024));
        self::assertSame('347 MiB', Path::formatFilesize(354919 * 1024));
        self::assertSame('0 B', Path::formatFilesize(0));
        self::assertSame('909 TiB', Path::formatFilesize(1000000000000000));
        self::assertSame('Large.', Path::formatFilesize(10000000000000000));
    }
}
