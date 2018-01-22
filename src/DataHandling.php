<?php

namespace Villermen\DataHandling;

/**
 * Contains sanitization and validation functions.
 * Sanitizes correct given values fitting for their desired data type.
 * If a value does not conform to its data type, null will be returned.
 * Validators throw an exception if their desired condition is not met.
 */
class DataHandling
{
    const ACCENTED_CHARACTERS = [
        "È" => "e", "É" => "e", "Ê" => "e", "Ë" => "e", "è" => "e", "é" => "e", "ê" => "e", "ë" => "e",
        "Ì" => "i", "Í" => "i", "Î" => "i", "Ï" => "i", "ì" => "i", "í" => "i", "î" => "i", "ï" => "i",
        "À" => "a", "Á" => "a", "Â" => "a", "Ã" => "a", "Ä" => "a", "Å" => "a", "Æ" => "a",
        "à" => "a", "á" => "a", "â" => "a", "ã" => "a", "ä" => "a", "å" => "a", "æ" => "a",
        "Ù" => "u", "Ú" => "u", "Û" => "u", "Ü" => "u", "ù" => "u", "ú" => "u", "û" => "u",
        "ò" => "o", "ó" => "o", "ô" => "o", "õ" => "o", "ö" => "o", "ø" => "o", "ð" => "o",
        "Ò" => "o", "Ó" => "o", "Ô" => "o", "Õ" => "o", "Ö" => "o", "Ø" => "o",
        "Ý" => "y", "ý" => "y", "ÿ" => "y",
        "Ç" => "c", "ç" => "c",
        "Ñ" => "n", "ñ" => "n",
        "Š" => "s", "š" => "s",
        "Ž" => "z", "ž" => "z",
        "Þ" => "b", "þ" => "b",
        "Ð" => "dj",
        "ß" => "ss",
        "ƒ" => "f"
    ];

    /**
     * Prefixes the url with a protocol if it has none, to prevent local lookups.
     *
     * @param string $url
     * @return string|null
     */
    public static function sanitizeUrl($url)
    {
        $url = trim($url);

        if (!$url) {
            return null;
        }

        if (!preg_match("/^https?:\/\//i", $url)) {
            $url = "http://" . $url;
        }

        return self::sanitizeString($url);
    }

    /**
     * Same as sanitizeString but allows linebreaks and strong and italic text.
     *
     * @param $text
     * @return string|null
     */
    public static function sanitizeText($text)
    {
        if (!$text) {
            return null;
        }

        $allowedTagNames = [ "b", "strong", "i", "em", "br" ];

        $marker = "\&8slkc7\\";

        // Create a one-to-one conversion array for easy replacing
        $allowedTagConversion = [];
        foreach($allowedTagNames as $allowedTagName) {
            $allowedTagConversion["<{$allowedTagName}>"] = "{$marker}{$allowedTagName}{$marker}";
            $allowedTagConversion["<{$allowedTagName}/>"] = "{$marker}{$allowedTagName}/{$marker}";
            $allowedTagConversion["<{$allowedTagName} />"] = "{$marker}{$allowedTagName} /{$marker}";
            $allowedTagConversion["</{$allowedTagName}>"] = "{$marker}/{$allowedTagName}{$marker}";
        }

        // Convert to BB-like tags before sanitizing and then revert
        $text = str_ireplace(array_keys($allowedTagConversion), array_values($allowedTagConversion), $text);

        $text = self::sanitizeString($text);

        if (!$text) {
            return null;
        }

        return str_ireplace(array_values($allowedTagConversion), array_keys($allowedTagConversion), $text);
    }

    /**
     * Trims string, removes tags and linebreaks.
     * Returns null if string equals false, before or after conversion.
     *
     * @param $string
     * @return string|null
     */
    public static function sanitizeString($string)
    {
        if (!$string) {
            return null;
        }

        // Remove linebreaks and HTML tags
        $string = str_ireplace(["\r\n", "\n", "\r"], " ", $string);
        $string = html_entity_decode($string, ENT_NOQUOTES, "UTF-8");
        $string = trim(strip_tags($string));

        if (!$string) {
            return null;
        }

        return (string)$string;
    }

    /**
     * @param $number
     * @return float|null
     */
    public static function sanitizeNumber($number)
    {
        return (float)trim($number);
    }

    /**
     * @param $digits
     * @return int|null
     */
    public static function sanitizeDigits($digits)
    {
        $digits = self::sanitizeString($digits);
        $digits = str_replace([" ", "-", "."], "", $digits);
        $digits = (int)trim($digits);

        if (!ctype_digit((string)$digits)) {
            return null;
        }

        return $digits;
    }

    public static function sanitizeBoolean($boolean)
    {
        $boolean = trim(strtolower($boolean));

        if (in_array($boolean, ["false", "null", "0", "", "no", "nee", "niet", "none", "geen", "incorrect"])) {
            return false;
        }

        if (in_array($boolean, ["true", "1", "yes", "ja", "yes", "wel", "correct"])) {
            return true;
        }

        return (bool)$boolean;
    }

    /**
     * Sanitize, or SEOify url part.
     * Will result in a string with only dashes, dots and alphanumeric characters.
     * Arguments are joined by slashes.
     * If an array of parts is given as the first argument, each one will be processed and returned as one string with slashes between parts.
     *
     * @param string|string[] $urlPartOrUrlParts An array of parts or a single part.
     * @param string[] $additionalUrlParts Additional url parts, if the first argument is not an array.
     * @return string
     */
    public static function sanitizeUrlParts($urlPartOrUrlParts, ...$additionalUrlParts)
    {
        if (is_array($urlPartOrUrlParts)) {
            $urlParts = $urlPartOrUrlParts;
        } else {
            $urlParts = array_merge([$urlPartOrUrlParts], $additionalUrlParts);
        }

        $sanitizedUrlParts = [];
        foreach ($urlParts as $urlPart) {
            $urlPart = self::sanitizeString($urlPart);

            // Replace accented characters for their regular counterparts
            $urlPart = str_replace(array_keys(self::ACCENTED_CHARACTERS), array_values(self::ACCENTED_CHARACTERS), $urlPart);

            // Collapse spaces, dashes and underscores into single dashes
            $urlPart = preg_replace("/[\s-_]+/", "-", $urlPart);

            $urlPart = strtolower($urlPart);

            // Remove any leftover invalid characters
            $urlPart = preg_replace("/[^a-z0-9\\-\\.]/", "", $urlPart);

            // Trim possibly existing dashes at start and end of string
            $urlPart = trim($urlPart, "-");

            $sanitizedUrlParts[] = $urlPart;
        }

        return implode("/", $sanitizedUrlParts);
    }

    /**
     * Will return only lowercase alphanumeric characters (converts accents).
     *
     * @param string $string
     * @param int[] $mapping If set it will be filled by mapping information: Each array element denotes an offset and length of a removed part in the resulting string.
     * @return string
     */
    public static function sanitizeAlphanumeric($string, &$mapping = null)
    {
        $newString = str_replace(array_keys(self::ACCENTED_CHARACTERS), array_values(self::ACCENTED_CHARACTERS), $string);
        $newString = strtolower($newString);
        $stringParts = preg_split("/[^a-z0-9]+/", $newString, -1, PREG_SPLIT_OFFSET_CAPTURE);

        $mapping = [];
        $result = "";
        $positionInOriginal = 0;
        foreach($stringParts as $stringPart) {
            if ($stringPart[0]) {
                $positionInResult = strlen($result);
                $removedLength = $stringPart[1] - $positionInOriginal;

                if ($removedLength !== 0) {
                    $mapping[] = [$positionInResult, $removedLength];
                }

                $positionInOriginal = $stringPart[1] + strlen($stringPart[0]);

                // Append part to result string
                $result .= $stringPart[0];
            }
        }

        $remainder = strlen($string) - $positionInOriginal;
        if ($remainder) {
            $positionInResult = strlen($result);
            $mapping[] = [$positionInResult, $remainder];
        }

        return $result;
    }

    /**
     * Advanced stripos() that matches the target string based on only its mapped alphanumeric characters.
     * Both haystack and needle will be sanitizeAlphanumeric'd and, if a match is found, the start position and length in the original string will be returned.
     *
     * @param string $haystack
     * @param string $needle
     * @param bool $expand Whether non-alphanumeric characters are to be included in the result.
     * @return false|\int[]
     */
    public static function findInString($haystack, $needle, $expand = false)
    {
        $alphaHaystack = self::sanitizeAlphanumeric($haystack, $haystackMapping);
        $alphaNeedle = self::sanitizeAlphanumeric($needle);

        $matchPos = strpos($alphaHaystack, $alphaNeedle);

        if ($matchPos === false) {
            return false;
        }

        $mappedPosition = $matchPos;
        $mappedLength = strlen($alphaNeedle);
        foreach($haystackMapping as $haystackMappingItem) {
            if ($haystackMappingItem[0] <= $matchPos) {
                if ($expand && $haystackMappingItem[0] === $matchPos) {
                    // Include expand before
                    $mappedLength += $haystackMappingItem[1];
                } else {
                    // Exclude expand before
                    $mappedPosition += $haystackMappingItem[1];
                }
            } elseif ($haystackMappingItem[0] > $matchPos && $haystackMappingItem[0] < $matchPos + strlen($alphaNeedle)) {
                // Expand within
                $mappedLength += $haystackMappingItem[1];
            } else {
                if ($expand && $haystackMappingItem[0] === $matchPos + strlen($alphaNeedle)) {
                    // Include expand after
                    $mappedLength += $haystackMappingItem[1];
                } else {
                    break;
                }
            }
        }

        return [$mappedPosition, $mappedLength];
    }

    /**
     * @param float $number
     * @param float $min Inclusive lower bound.
     * @param float $max Inclusive upper bound.
     * @param string $name
     * @throws DataHandlingException
     */
    public static function validateInRange($number, $min, $max, $name = "value")
    {
        if ($number < $min || $number > $max) {
            throw new DataHandlingException("\"{$name}\" is not in the range of {$min}-{$max}.");
        }
    }

    /**
     * @param mixed $value
     * @param mixed[] $options
     * @param string $name
     * @throws DataHandlingException
     */
    public static function validateInArray($value, $options, $name = "value")
    {
        if ($value === null || !in_array($value, $options)) {
            throw new DataHandlingException("\"{$value}\" is not a valid value for \"{$name}\".");
        }
    }

    /**
     * Explodes a string into an array after performing sanitization on each element.
     *
     * @param string $string
     * @param string $characters
     * @return string[]
     */
    public static function explode($string, $characters = ";>|/\\<")
    {
        $string = self::sanitizeString($string);

        if ($string === null) {
            return [];
        }

        // Convert characters into a safe regular expression
        $splitRegex = "";
        for ($i = 0; $i < strlen($characters); $i++) {
            $splitRegex .= "\\" . $characters[$i];
        }
        $splitRegex = "/[" . $splitRegex . "]/";

        $rawElements = preg_split($splitRegex, $string);
        $elements = [];
        foreach($rawElements as $rawElement) {
            $element = self::sanitizeString($rawElement);

            if ($element !== null) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    /**
     * Implodes an array into a string after performing sanitization on each element.
     *
     * @param string[] $array
     * @param string $separator
     * @return string
     */
    public static function implode($array, $separator = " > ")
    {
        if ($array === null) {
            return "";
        }

        $elements = [];
        foreach($array as $rawElement) {
            $element = self::sanitizeString($rawElement);

            if ($element !== null) {
                $elements[] = $element;
            }
        }

        return implode($separator, $elements);
    }

    /**
     * Returns whether the value of the given string starts with any of the supplied options.
     *
     * @param string $string
     * @param string|string[] $optionOrOptions A string depicting a first
     * @param string[] $additionalOptions Additional options, if the first option is a string.
     * @return bool
     */
    public static function startsWith($string, $optionOrOptions, ...$additionalOptions)
    {
        if (is_array($optionOrOptions)) {
            $options = $optionOrOptions;
        } else {
            $options = array_merge([$optionOrOptions], $additionalOptions);
        }

        foreach($options as $option) {
            if (substr($string, 0, strlen($option)) === $option) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the value of the given string ends with any of the supplied options.
     *
     * @param string $string
     * @param string|string[] $optionOrOptions
     * @param string[] $additionalOptions
     * @return bool
     */
    public static function endsWith($string, $optionOrOptions, ...$additionalOptions)
    {
        if (is_array($optionOrOptions)) {
            $options = $optionOrOptions;
        } else {
            $options = array_merge([$optionOrOptions], $additionalOptions);
        }

        array_walk($options, function(&$option) {
            $option = strrev($option);
        });

        return self::startsWith(strrev($string), $options);
    }

    /**
     * Returns whether the alphanumeric value of the given string starts with any of the supplied options.
     *
     * @param $string
     * @param string|string[] $optionOrOptions A string depicting a first
     * @param string[] $additionalOptions Additional options, if the first option is a string.
     * @return bool
     */
    public static function startsWithAlphanumeric($string, $optionOrOptions, ...$additionalOptions)
    {
        if (is_array($optionOrOptions)) {
            $options = $optionOrOptions;
        } else {
            $options = array_merge([$optionOrOptions], $additionalOptions);
        }

        // Convert both string and options to alphanumeric
        $string = self::sanitizeAlphanumeric($string);
        $options = array_map([ self::class, "sanitizeAlphanumeric"], $options);

        return self::startsWith($string, $options);
    }

    /**
     * Returns whether the alphanumeric value of the given string ends with any of the supplied options.
     *
     * @param string $string
     * @param string|string[] $optionOrOptions
     * @param string[] $additionalOptions
     * @return bool
     */
    public static function endsWithAlphanumeric($string, $optionOrOptions, ...$additionalOptions)
    {
        if (is_array($optionOrOptions)) {
            $options = $optionOrOptions;
        } else {
            $options = array_merge([$optionOrOptions], $additionalOptions);
        }

        array_walk($options, function(&$option) {
            $option = strrev($option);
        });

        return self::startsWithAlphanumeric(strrev($string), $options);
    }

    /**
     * Formats a file path to a uniform representation.
     * Multiple paths can be given and will be concatenated.
     *
     * @param string|string[] $pathOrPaths
     * @param string[] $additionalPaths
     * @return string
     */
    public static function formatPath($pathOrPaths, ...$additionalPaths)
    {
        $path = self::mergePaths($pathOrPaths, ...$additionalPaths);

        // Remove optional scheme to add back later
        $scheme = "";
        $path = preg_replace_callback("/^[a-z0-9+\.\-]+:\/\//", function ($matches) use (&$scheme) {
            $scheme = $matches[0];
            return "";
        }, $path);

        // Remove self-referencing path parts (resolving already takes care of this)
        $replacements = 0;
        do {
            $path = str_replace(["/./", "//"], "/", $path, $replacements);
        } while ($replacements > 0);

        // Replace parent directory paths if possible
        $pathParts = explode("/", $path);

        $ignoredParts = 0;
        do {
            $parentKey = array_search("..", array_slice($pathParts, $ignoredParts, null, true));

            // Don't remove .. if it starts the path
            if ($parentKey > 0) {
                switch ($pathParts[$parentKey - 1]) {
                    // Don't remove root / but remove ..'s directly after it
                    case "":
                        unset($pathParts[$parentKey]);
                        break;

                    // Keep consecutive ..'s (only possible if the path starts with them)
                    case "..":
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

        $path = implode("/", $pathParts);

        return $scheme . $path;
    }

    /**
     * Resolves and formats a path.
     *
     * @param string|string[] $pathOrPaths
     * @param string[] $additionalPaths
     * @return string
     * @throws DataHandlingException
     */
    public static function formatAndResolvePath($pathOrPaths, ...$additionalPaths)
    {
        $path = self::mergePaths($pathOrPaths, ...$additionalPaths);
        $path = realpath($path);

        if (!$path) {
            throw new DataHandlingException("Given path does not exist.");
        }

        return self::formatPath($path);
    }

    /**
     * Merges path parts into one path.
     * Only the first argument can cause the path to become absolute.
     *
     * @param string|string[] $pathOrPaths
     * @param string[] $additionalPaths
     * @return string
     */
    public static function mergePaths($pathOrPaths, ...$additionalPaths)
    {
        if (is_array($pathOrPaths)) {
            $paths = $pathOrPaths;
        } else {
            $paths = array_merge([$pathOrPaths], $additionalPaths);
        }

        $paths = array_values($paths);

        $prefix = "";
        $suffix = "";
        if (count($paths) > 0) {
            // Save root for first path
            if (self::startsWith($paths[0], "/", "\\")) {
                $prefix = "/";
            }

            // Save separator for last path
            if (self::endsWith($paths[count($paths) - 1], "/", "\\")) {
                $suffix = "/";
            }
        }

        // Remove leading and trailing separators from parts to not end up with repeated separators
        array_walk($paths, function(&$path) {
            $path = str_replace("\\", "/", $path);
            $path = trim($path, "/");
        });

        // Remove empty parts to not end up with repeated separators
        $paths = array_filter($paths);

        return $prefix . implode("/", $paths) . $suffix;
    }

    /**
     * Makes given path relative to the given root directory.
     *
     * @param string $path
     * @param string $rootDirectory
     * @return string
     * @throws DataHandlingException Thrown when the path is not part of the given root directory.
     */
    public static function makePathRelative($path, $rootDirectory)
    {
        $rootDirectory = self::formatDirectory($rootDirectory);

        if (!self::startsWith(self::formatDirectory($path), $rootDirectory)) {
            throw new DataHandlingException("Path is not part of the given root directory.");
        }

        return substr_replace($path, "", 0, strlen($rootDirectory));
    }

    /**
     * Formats a directory path to a uniform representation.
     * Basically formatPath but with a trailing slash.
     *
     * @param string|string[] $pathOrPaths
     * @param string[] $additionalPaths
     * @return string
     */
    public static function formatDirectory($pathOrPaths, ...$additionalPaths)
    {
        $directory = self::formatPath($pathOrPaths, ...$additionalPaths);

        if ($directory) {
            $directory = rtrim($directory, "/") . "/";
        }

        return $directory;
    }

    /**
     * Resolves and formats a directory.
     *
     * @param string|string[] $pathOrPaths
     * @param string[] $additionalPaths
     * @return string
     * @throws DataHandlingException
     */
    public static function formatAndResolveDirectory($pathOrPaths, ...$additionalPaths)
    {
        $directory = self::formatAndResolvePath($pathOrPaths, ...$additionalPaths);

        if ($directory) {
            $directory = rtrim($directory, "/") . "/";
        }

        return $directory;
    }
}
