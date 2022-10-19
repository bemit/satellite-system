<?php declare(strict_types=1);

namespace Satellite\System;

use Invoker\InvokerInterface;
use Psr\Container\ContainerInterface;
use Satellite\Event\EventListenerInterface;

class SetupEvents implements SetupStepInterface {
    public const CONFIG_EVENTS = 'events';
    public const CONFIG_SETUP_EVENTS = 'setup__events';

    public function __invoke(ContainerInterface $container, array $config): void {
        /**
         * @var $code_info EventListenerInterface
         */
        $event_listener = $container->get(EventListenerInterface::class);
        $config_modules_events = $config[self::CONFIG_EVENTS] ?? null;
        if($config_modules_events) {
            foreach($config_modules_events as $event) {
                $event_listener->on($event[0], $event[1]);
            }
        }

        $setup_files = $config[self::CONFIG_SETUP_EVENTS] ?? null;
        if(!$setup_files) return;

        /**
         * @var InvokerInterface $invoker
         */
        $invoker = $container->get(InvokerInterface::class);

        foreach($setup_files as $setup_file) {
            $events = require $setup_file;
            $invoker->call($events);
        }
    }
}
