<?php

namespace Bggardner\StaticTools;

/**
 * Non-static extention of ArrayObject with additional methods
 */
class ArrayObject extends \ArrayObject
{
    /**
     * Replaces non-existent keys with provided values, maintaining order
     */
    public function coalesce(array $array): ArrayObject
    {
        return $this->replace($array, $this->getArrayCopy());
    }

    /**
     * Replaces non-existent keys with provided values recursively, maintaining order
     */
    public function coalesceRecursive(array $array): ArrayObject
    {
        return $this->replaceRecursive($array, $this->getArrayCopy());
    }

    /**
     * @see array_diff_key()
     */
    public function diffKeys(array ...$arrays): ArrayObject
    {
        array_unshift($arrays, $this->getArrayCopy());
        $this->exchangeArray(call_user_func_array('array_diff_key', $arrays));
        return $this;
    }

   /**
     * Similar to intersectKeys, but argument is array of keys instead of keyed array
     * Complement of remove()
     */
    public function extract(array $keys): ArrayObject
    {
        return $this->intersectKeys(array_fill_keys($keys, null));
    }

    /**
     * @see array_intersect_key()
     */
    public function intersectKeys(array ...$arrays): ArrayObject
    {
        array_unshift($arrays, $this->getArrayCopy());
        $this->exchangeArray(call_user_func_array('array_intersect_key', $arrays));
        return $this;
    }

    /**
     * @see array_merge()
     */
    public function merge(array ...$arrays): ArrayObject
    {
        array_unshift($arrays, $this->getArrayCopy());
        $this->exchangeArray(call_user_func_array('array_merge', $arrays));
        return $this;
    }

    /**
     * @see array_merge_recursive()
     */
    public function mergeRecursive(array ...$arrays): ArrayObject
    {
        array_unshift($arrays, $this->getArrayCopy());
        $this->exchangeArray(call_user_func_array('array_merge_recursive', $arrays));
        return $this;
    }

    /**
     * Similar to array_key_diff, but argument is array of keys instead of keyed array
     * Complement of extract()
     */
    public function remove(array $keys): ArrayObject
    {
        return $this->diffKeys(array_fill_keys($keys, null));
    }

    /**
     * @see array_replace()
     */
    public function replace(array ...$replacements): ArrayObject
    {
        array_unshift($replacements, $this->getArrayCopy());
        $this->exchangeArray(call_user_func_array('array_replace', $replacements));
        return $this;
    }

    /**
     * @see array_replace_recursive()
     */
    public function replaceRecursive(array ...$replacements): ArrayObject
    {
        array_unshift($replacements, $this->getArrayCopy());
        $this->exchangeArray(call_user_func_array('array_replace_recursive', $replacements));
        return $this;
    }
}
