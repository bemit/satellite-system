<?php declare(strict_types=1);

namespace Satellite\System;

use Doctrine\Common\Annotations\AnnotationReader;
use Orbiter\AnnotationsUtil\AnnotationUtil;
use Orbiter\AnnotationsUtil\CodeInfo;
use Orbiter\AnnotationsUtil\CodeInfoSource;
use Psr\Container\ContainerInterface;

class SetupAnnotations implements SetupStepInterface {
    public const CONFIG_ANNOTATION = 'annotation';
    public const CONFIG_CODE_INFO = 'code_info';

    public function __invoke(ContainerInterface $container, array $config): void {
        foreach($config[self::CONFIG_ANNOTATION]['psr4'] as $annotation_ns => $annotation_ns_dir) {
            AnnotationUtil::registerPsr4Namespace($annotation_ns, $annotation_ns_dir);
        }
        foreach($config[self::CONFIG_ANNOTATION]['ignore'] as $annotation_ig) {
            AnnotationReader::addGlobalIgnoredName($annotation_ig);
        }

        // Parse Code for Annotations
        /**
         * @var $code_info CodeInfo
         */
        $code_info = $container->get(CodeInfo::class);
        $config_code_info = $config[self::CONFIG_CODE_INFO];
        foreach($config_code_info as $sources_config) {
            $code_info->defineSource(
                new CodeInfoSource(
                    $sources_config['folder'],
                    $sources_config['flags'],
                    $sources_config['extensions'],
                )
            );
        }
        $code_info->process();
    }
}
