<?php

namespace Villermen\DataHandling;

class Path
{
    /**
     * Merges path parts into one formatted path. Only the first argument can cause the path to become absolute.
     */
    public static function format(string ...$paths): string
    {
        $prefix = '';
        $suffix = '';
        foreach ($paths as $i => &$path) {
            $path = self::formatPart($path);

            // Allow only first path to add root prefix.
            if ($i === array_key_first($paths) && str_starts_with($path, '/')) {
                $prefix = '/';
            }

            // Allow only last part to add directory suffix.
            if ($i === array_key_last($paths) && str_ends_with($path, '/')) {
                $suffix = '/';
            }

            $path = trim($path, '/');
        }

        // Glue non-empty paths back together.
        $path = implode('/', array_filter($paths));

        // Remove suffix if there is no path to prevent it from making root.
        if ($path === '') {
            $suffix = '';
        }

        return $prefix . $path . $suffix;
    }

    /**
     * @deprecated Use {@see format()} with a slash for the last argument.
     */
    public static function formatDirectory(string $path): string
    {
        return self::format($path, '/');
    }

    /**
     * @deprecated Replaced by {@see format()}.
     */
    public static function merge(string ...$path): string
    {
        return self::format(...$path);
    }

    /**
     * Formats a path part to a uniform representation. Removes path-relative components.
     */
    private static function formatPart(string $path): string
    {
        $path = str_replace("\\", "/", $path);

        // Remove self-referencing path parts (resolving already takes care of this)
        $replacements = 0;
        do {
            $path = str_replace(['/./', '//'], '/', $path, $replacements);
        } while ($replacements > 0);

        // Replace parent directory paths if possible
        $pathParts = explode('/', $path);

        $ignoredParts = 0;
        do {
            $parentKey = array_search('..', array_slice($pathParts, $ignoredParts, null, true));

            // Don't remove .. if it starts the path
            if ($parentKey > 0) {
                switch ($pathParts[$parentKey - 1]) {
                    // Don't remove root / but remove ..'s directly after it
                    case '':
                        unset($pathParts[$parentKey]);
                        break;

                    // Keep consecutive ..'s (only possible if the path starts with them)
                    case '..':
                        $ignoredParts++;
                        break;

                    // Collapse
                    default:
                        unset($pathParts[$parentKey - 1]);
                        unset($pathParts[$parentKey]);
                        break;
                }

                $pathParts = array_values($pathParts);
            } else {
                $ignoredParts++;
            }
        } while ($parentKey !== false);

        return implode('/', $pathParts);
    }

    /**
     * Makes given `$path` relative to the given `$rootPath`. Returns `null` when `$path` is not part of `$rootPath`.
     */
    public static function makeRelative(string $path, string $rootPath): ?string
    {
        $rootPath = self::format($rootPath, '/');
        $path = self::format($path);

        if (!str_starts_with(self::format($path, '/'), $rootPath)) {
            return null;
        }

        return substr($path, strlen($rootPath));
    }

    /**
     * Returns a suffixed and shortened indication of an amount of bytes.
     */
    public static function formatFilesize(int $size): string
    {
        $suffixes = [
            'B', 'KiB', 'MiB', 'GiB', 'TiB' //, 'PiB', 'EiB', 'ZiB', 'YiB'
        ];

        $level = 1;
        for ($exponent = 0; $exponent < count($suffixes); $exponent++) {
            $nextLevel = pow(1024, $exponent + 1);

            if ($nextLevel > $size) {
                $smallSize = $size / $level;

                if ($smallSize < 10) {
                    $decimals = 2;
                } elseif ($smallSize < 100) {
                    $decimals = 1;
                } else {
                    $decimals = 0;
                }

                return round($smallSize, $decimals) . ' ' . $suffixes[$exponent];
            }

            $level = $nextLevel;
        }

        return 'Large.';
    }
}
