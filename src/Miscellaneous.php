<?php

namespace Bggardner\StaticTools;

class Miscellaneous
{
    protected static $format_info;

    /**
     * Locale-aware @see number_format()
     */
    public static function numberFormat($num): string
    {
        if (!isset(static::$format_info)) {
            static::$format_info = localeconv();
        }
        return number_format($num, 0, static::$format_info['decimal_point'], static::$format_info['thousands_sep']);
    }

    /**
     * Redirect after POST.
     *
     * @param ?string $uri
     */
    public static function return(?string $uri): void
    {
        if (!$uri) {
            $uri = $_SERVER['HTTP_REFERER'];
        }
        if (!$uri) {
            $uri = $_SERVER['REQUEST_URI'];
        }
        header('Location: ' . $uri);
        exit;
    }
}
