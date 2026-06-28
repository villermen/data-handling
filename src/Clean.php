<?php

namespace Villermen\DataHandling;

/**
 * Contains methods to sanitize strings in various ways.
 */
class Clean
{
    private const array ACCENTED_CHARACTERS = [
        'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a', 'Å' => 'a', 'Æ' => 'a',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a',
        'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u', 'ù' => 'u', 'ú' => 'u', 'û' => 'u',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ð' => 'o',
        'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o', 'Ø' => 'o',
        'Ý' => 'y', 'ý' => 'y', 'ÿ' => 'y',
        'Ç' => 'c', 'ç' => 'c',
        'Ñ' => 'n', 'ñ' => 'n',
        'Š' => 's', 'š' => 's',
        'Ž' => 'z', 'ž' => 'z',
        'Þ' => 'b', 'þ' => 'b',
        'Ð' => 'dj',
        'ß' => 'ss',
        'ƒ' => 'f'
    ];

    /**
     * Trims and removes HTML tags from `$text`. Linebreaks are preserved, along with an optional set of simple HTML
     * tags that can't have any attributes.
     */
    public static function text(string $text, array $allowedTags = []): string
    {
        $marker = '\&8slkc7\\';

        // Convert to non-HTML before cleaning and then revert.
        $conversions = [];
        foreach($allowedTags as $allowedTagName) {
            $conversions["<{$allowedTagName}>"] = $marker . $allowedTagName . '-1';
            $conversions["<{$allowedTagName}/>"] = $marker . $allowedTagName . '-2';
            $conversions["<{$allowedTagName} />"] = $marker . $allowedTagName . '-3';
            $conversions["</{$allowedTagName}>"] = $marker . $allowedTagName . '-4';
        }
        $conversions["\n"] = $marker . ':n';

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_ireplace(array_keys($conversions), array_values($conversions), $text);
        $text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
        $text = trim(strip_tags($text), ' ');
        return str_ireplace(array_values($conversions), array_keys($conversions), $text);
    }

    /**
     * Will return only lowercase alphanumeric characters. Converts accented characters.
     *
     * @param string $additionalCharacters List of additional characters that are allowed.
     */
    public static function alphanumeric(string $string, string $additionalCharacters = ''): string
    {
        $string = str_replace(array_keys(self::ACCENTED_CHARACTERS), array_values(self::ACCENTED_CHARACTERS), $string);
        $string = strtolower($string);

        return preg_replace(
            sprintf('/[^a-z0-9%s]+/', preg_quote($additionalCharacters, '/')),
            '',
            $string,
        );
    }

    /**
     * Sanitize, or SEOify url part. Results in a string with only dashes, dots and alphanumeric characters.
     */
    public static function slug(string $slug): string
    {
        // Collapse spaces, dashes and underscores into single dashes
        $slug = preg_replace('/[\s\-_]+/', '-', $slug);

        $slug = self::alphanumeric($slug, '-.');

        // Remove leading and trailing dashes.
        return trim($slug, '-');
    }

    /**
     * Keeps only the digits contained in `$digits`.
     */
    public static function digits(string $digits): string
    {
        return preg_replace('/[^0-9]+/', '', $digits);
    }

    /**
     * URL-encodes a full URL, leaving slashes and scheme in tact.
     */
    public static function url(string $url): string
    {
        if (!preg_match('~^([a-zA-Z0-9+\.\-]*://[^/]*)?([^\?#]*)(.*)$~', $url, $matches)) {
            return $url;
        }

        // Host won't be matched if there's no scheme, but that's still miles better than whatever `parse_url()` spits
        // out.
        $schemeHost = $matches[1];
        $path = $matches[2];
        $query = $matches[3];

        $path = implode('/', array_map('rawurlencode', explode('/', $path)));

        return $schemeHost . $path . $query;
    }
}
