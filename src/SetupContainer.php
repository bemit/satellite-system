<?php declare(strict_types=1);

namespace Satellite\System;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use RuntimeException;

class SetupContainer {
    public const CONFIG_CONTAINER = 'container';
    public const CONFIG_DEPENDENCIES = 'dependencies';
    public const CONFIG_SETUP_DEPENDENCIES = 'setup__dependencies';

    public function __invoke(array $config): ContainerInterface {
        return $this->build($config);
    }

    protected function build(array $config): ContainerInterface {
        $container_builder = new ContainerBuilder();
        $container_builder->useAutowiring(true);
        $container_builder->useAnnotations(true);

        $do_compile = isset($config[self::CONFIG_CONTAINER]['compile']) && $config[self::CONFIG_CONTAINER]['compile'];
        if($do_compile) {
            $container_builder->enableCompilation($config['dir_tmp'], 'CompiledContainer');
        }

        if(!$do_compile || !is_file($config['dir_tmp'] . '/CompiledContainer.php')) {
            // skipping the dependency wiring when it should compile and CompiledContainer exists
            $dependencies = isset($config[self::CONFIG_DEPENDENCIES]) ?
                [$this->wireDependencies($config[self::CONFIG_DEPENDENCIES])] : [];

            $dependency_files = $config[self::CONFIG_SETUP_DEPENDENCIES] ?? [];
            foreach($dependency_files as $dependency_file) {
                $dependencies[] = (require $dependency_file)($config);
            }

            $container_builder->addDefinitions(...$dependencies);
        }

        try {
            return $container_builder->build();
        } catch(\Exception $e) {
            error_log('failed to build container: ' . $e->getMessage());
            exit(2);
        }
    }

    protected function wireDependencies(?array $config_dependencies): array {
        if(!$config_dependencies) return [];

        $dependencies = [];
        $wire_up = static function(string $iface, string|array $impl, $helper_factory) {
            if(is_string($impl)) {
                return $helper_factory($impl);
            }
            if(!is_string($impl[0])) {
                throw new RuntimeException('Module dependency must be string at `0` for `' . $iface . '`');
            }
            if(!is_array($impl[1])) {
                throw new RuntimeException('Module dependency must be array at `1` for `' . $iface . '`');
            }
            $helper = $helper_factory($impl[0]);
            foreach($impl[1] as $key => $value) {
                $helper = $helper->constructorParameter($key, $value);
            }
            return $helper;
        };
        if(isset($config_dependencies['services'])) {
            foreach($config_dependencies['services'] as $service_iface => $service_impl) {
                $dependencies[$service_iface] = $wire_up($service_iface, $service_impl, static fn($impl) => \DI\autowire($impl));
            }
        }
        if(isset($config_dependencies['invokables'])) {
            foreach($config_dependencies['invokables'] as $service_iface => $service_impl) {
                $dependencies[$service_iface] = $wire_up($service_iface, $service_impl, static fn($impl) => \DI\autowire($impl));
            }
        }
        if(isset($config_dependencies['factories'])) {
            foreach($config_dependencies['factories'] as $service_iface => $service_impl) {
                $dependencies[$service_iface] = $wire_up($service_iface, $service_impl, static fn($impl) => \DI\factory($impl));
            }
        }
        if(isset($config_dependencies['aliases'])) {
            foreach($config_dependencies['aliases'] as $service_iface => $service_impl) {
                $dependencies[$service_iface] = $wire_up($service_iface, $service_impl, static fn($impl) => \DI\get($impl));
            }
        }

        return $dependencies;
    }
}
