<?php declare(strict_types=1);

namespace Satellite\System;

use Invoker\Invoker;
use Psr\Container\ContainerInterface;
use Satellite\Invoker\InvokerTypeHintContainerResolver;

class SetupInvoker implements SetupStepInterface {

    public function __invoke(ContainerInterface $container, array $config): void {
        /**
         * @var $invoker Invoker
         */
        $invoker = $container->get(Invoker::class);
        $invoker->getParameterResolver()->prependResolver(
            new InvokerTypeHintContainerResolver($container)
        );
    }
}
