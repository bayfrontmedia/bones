<?php

namespace App\Filters;

use Bayfront\Bones\Filter;
use Bayfront\Bones\Interfaces\FilterInterface;
use Bayfront\Container\NotFoundException;
use Bayfront\Translation\TranslationException;

/**
 * VeilViewSayTag filter.
 *
 * Add say tag to return translation strings to Veil views.
 *
 * Created for Bones v2.0.0
 */
class VeilViewSayTag extends Filter implements FilterInterface
{

    /**
     * @inheritDoc
     */

    public function isActive(): bool
    {
        return $this->container->has('Bayfront\Veil\Veil') && $this->container->has('Bayfront\Translation\Translate');
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
     *
     * @inheritDoc
     *
     */

    public function action($value)
    {

        // @say

        preg_match_all("/@say:[\w.]+/", $value, $tags); // Any word character or period

        if (isset($tags[0]) && !empty($tags[0])) { // If a tag was found

            foreach ($tags[0] as $tag) {

                $use = explode(':', $tag, 2);

                if (isset($use[1])) { // If valid @say syntax

                    try {

                        $value = str_replace($tag, translate($use[1]), $value);

                    } catch (NotFoundException|TranslationException $e) {

                        /*
                         * No translation exists for this tag.
                         * Do nothing.
                         */

                    }

                }
            }

        }

        return $value;

    }

}