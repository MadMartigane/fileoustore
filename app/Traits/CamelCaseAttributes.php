<?php

declare(strict_types=1);

namespace App\Traits;

trait CamelCaseAttributes
{
    /**
     * Convert array keys from snake_case to camelCase
     *
     * @param array $array
     * @return array
     */
    public function snakeToCamel(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->snakeToCamel($value);
            }
            $camelKey = $this->camelizeKey($key);
            $result[$camelKey] = $value;
        }
        return $result;
    }

    /**
     * Convert a string from snake_case to camelCase
     *
     * @param string $key
     * @return string
     */
    private function camelizeKey(string $key): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
    }
}