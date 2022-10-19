<?php declare(strict_types=1);

namespace Satellite\System;

use Psr\Container\ContainerInterface;

interface SetupStepInterface {
    /**
     * Call with the just-build service-container and the app-wide config.
     *
     * @param ContainerInterface $container
     * @param array $config
     * @return void
     */
    public function __invoke(ContainerInterface $container, array $config): void;
}
