<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * Interface ExposableInterface
 *
 * Defines a contract for modules that can expose
 * services, entities, routes, or other resources
 * for inter-module communication or public discovery.
 */
interface ExposableInterface
{
    /**
     * Declare the module’s explicitly exposed elements.
     *
     * @return array {
     *     @type array|null $public     Public API available to all modules
     *     @type array|null $internal   Elements available only to dependent modules
     * }
     */
    public function expose(): array;
}


