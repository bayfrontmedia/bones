<?php

namespace App\Filters;

use Bayfront\Bones\Filter;
use Bayfront\Bones\Interfaces\FilterInterface;

/**
 * VeilViewReplaceStrings filter.
 *
 * Replace strings in Veil views.
 */
class VeilViewReplaceStrings extends Filter implements FilterInterface
{

    /**
     * @inheritDoc
     */

    public function isActive(): bool
    {
        return $this->container->has('Bayfront\Veil\Veil');
    }

    /**
     * @inheritDoc
     */

    public function getFilters(): array
    {

        return [
            'veil.view' => 5
        ];
    }

    /**
     * Return array of case-sensitive strings to replace.
     *
     * @return array
     */

    protected function getCaseSensitive(): array
    {
        return [
            '~CASE-SENSITIVE-BAD-WORD~' => '****'
        ];
    }

    /**
     * Return array of case-insensitive strings to replace.
     *
     * @return array
     */

    protected function getCaseInsensitive(): array
    {
        return [
            '~CASE-INSENSITIVE-BAD-WORD~' => '****'
        ];
    }

    /**
     *
     * @inheritDoc
     *
     */

    public function action($value)
    {

        // Case-sensitive

        $sensitive = $this->getCaseSensitive();

        // Sort replacements by key/needle length (specificity)

        uksort($sensitive, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        $value = str_replace(array_keys($sensitive), array_values($sensitive), $value);

        // Case-insensitive

        $insensitive = $this->getCaseInsensitive();

        // Sort replacements by key/needle length (specificity)

        uksort($insensitive, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        return str_ireplace(array_keys($insensitive), array_values($insensitive), $value);

    }

}