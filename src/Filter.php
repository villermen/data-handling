<?php

namespace Villermen\DataHandling;

class Filter
{
    /**
     * Returns whether `$string` matches `$filter`. Filter can contain wildcard characters * and ?, where * matches
     * anything and ? matches precisely one character.
     */
    public static function match(string $string, string $filter): bool
    {
        // Convert filter to regular expression.
        $filter = preg_quote($filter, '/');
        $filter = str_replace(['\\*', '\\?'], ['.*', '.'], $filter);
        $filter = sprintf('/^%s$/', $filter);

        return preg_match($filter, $string);
    }
}
