<?php

namespace App\Http\GraphQL\Directives;

use Nuwave\Lighthouse\Schema\Values\ArgumentValue;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Support\Contracts\ArgMiddleware;
use Nuwave\Lighthouse\Support\Traits\HandlesQueryFilter;

class GreaterEqThanDirective extends BaseDirective implements ArgMiddleware

{
    use HandlesQueryFilter;

    /**
     * Name of the directive.
     *
     * @return string
     */
    public function name(): string
    {
        return 'gte';
    }

    /**
     * Resolve the field directive.
     *
     * @param ArgumentValue $argument
     * @param \Closure       $next
     *
     * @return ArgumentValue
     */

    public function handleArgument(ArgumentValue $argument, \Closure $next): ArgumentValue
    {
        $this->injectFilter(
            $argument,
            function ($query, string $columnName, $value) {
                return $query->where($columnName, '>=', $value);
            }
        );

        return $next($argument);
    }
}
