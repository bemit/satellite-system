<?php declare(strict_types=1);

namespace Satellite\System;

use Psr\Container\ContainerInterface;
use Satellite\EventProfiler\EventProfilerInterface;

class SystemControl {
    protected array $config;
    protected ?EventProfilerInterface $profiler;

    public function __construct(array $config, ?EventProfilerInterface $profiler = null) {
        $this->config = $config;
        $this->profiler = $profiler;
    }

    public static function fromConfig(array $config, ?EventProfilerInterface $profiler = null): ContainerInterface {
        $control = new static($config, $profiler);

        $setup_container = new SetupContainer();
        if($profiler) {
            $container = $profiler->run($control, $setup_container, fn() => $setup_container($config));
        } else {
            $container = $setup_container($config);
        }

        return $control->setup(
            $container,
            new \Satellite\System\SetupInvoker(),
            new \Satellite\System\SetupAnnotations(),
            new \Satellite\System\SetupEvents(),
        );
    }

    /**
     * @param ContainerInterface $container
     * @param SetupStepInterface[] ...$setup_steps
     * @return ContainerInterface
     */
    public function setup(ContainerInterface $container, ...$setup_steps): ContainerInterface {
        foreach($setup_steps as $setup_step) {
            $setup = fn() => call_user_func_array($setup_step, [$container, $this->config]);
            if($this->profiler) {
                $this->profiler->run($this, $setup_step, $setup);
            } else {
                $setup();
            }
        }
        return $container;
    }
}
