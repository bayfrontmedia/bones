<?php

namespace App\Filters;

use Bayfront\Bones\Filter;
use Bayfront\Bones\Interfaces\FilterInterface;
use Bayfront\Container\NotFoundException;

/**
 * VeilViewRouteTag filter.
 *
 * Add route tag to return named routes to Veil views.
 */
class VeilViewRouteTag extends Filter implements FilterInterface
{

    /**
     * @inheritDoc
     */

    public function isActive(): bool
    {
        return $this->container->has('Bayfront\Veil\Veil') && $this->container->has('Bayfront\RouteIt\Router');
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

        // @route

        preg_match_all("/@route:[\w.]+/", $value, $tags); // Any word character or period

        if (isset($tags[0]) && !empty($tags[0])) { // If a tag was found

            foreach ($tags[0] as $tag) {

                $use = explode(':', $tag, 2);

                if (isset($use[1])) { // If valid @route syntax

                    try {

                        $value = str_replace($tag, get_named_route($use[1]), $value);

                    } catch (NotFoundException $e) {

                        /*
                         * No named route exists for this tag.
                         * Do nothing.
                         */

                    }

                }
            }

        }

        return $value;

    }

}