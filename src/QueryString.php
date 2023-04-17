<?php

namespace Bggardner\StaticTools;

/**
 * Utility class that provides for reading and writing URL query strings
 */
class QueryString extends ArrayObject implements \Stringable
{
    /** @var ?array $storage Immutable copy of $_GET */
    private static $storage;

    public function __toString(): string
    {
        return $this->build();
    }

    /**
     * @ see http_build_query()
     */
    public function build(): string
    {
        return http_build_query($this->getArrayCopy());
    }

    /**
     * Flattens into 1-dimensional array with keys in HTML-form-input-name format
     */
    public function flatten(string $prefix = ''): array
    {
        $array = $this->getArrayCopy();
        $result = [];
        foreach ($array as $key => $value) {
            $key = strlen($prefix) ? $prefix . '[' . $key . ']' : $key;
            if (is_array($value)) {
                $result = array_merge($result, (new QueryString($value))->flatten($key));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * @see parse_str()
     */
    public static function parse(string $string): QueryString
    {
        parse_str($string, $result);
        return new static($result);
    }

    /**
     * Static class factory combined with functionality of extract() and offsetGet()
     *
     * @ param ?string $key If null, return a new instance of QueryString.
     *                      If an array, return the result of extract($key) on a new
     *                      instance of QueryString.
     *                      Otherwise, equivalent to: $_GET[$key] ?? null
     */
    public static function get($key = null): mixed
    {
        if (!isset(static::$storage)) {
            static::$storage = static::parse($_SERVER['QUERY_STRING'])->getArrayCopy();
        }
        if (isset($key)) {
            if (is_array($key)) {
                return (new static(static::$storage))->extract($key);
            } else {
                return static::$storage[$key] ?? null;
            }
        }
        return new static(static::$storage);
    }
}
