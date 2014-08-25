<?php

namespace Zycon42\ParamConverters\DI;

use Nette\DI\CompilerExtension;

class ConvertersExtension extends CompilerExtension {

    const TAG_CONVERTER = "zycon42.paramConverter";

    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('convertersManager'))
            ->setClass('Zycon42\\ParamConverters\\ParamConvertersManager');

        $builder->addDefinition($this->prefix('requestStorage'))
            ->setClass('Zycon42\\ParamConverters\\Application\\RequestStorage');

        $builder->addDefinition($this->prefix('doctrineConverter'))
            ->setClass('Zycon42\\ParamConverters\\Converters\\DoctrineConverter')
            ->addTag(self::TAG_CONVERTER);
    }

    public function beforeCompile() {
        $builder = $this->getContainerBuilder();

        $manager = $builder->getDefinition($this->prefix('convertersManager'));
        foreach (array_keys($builder->findByTag(self::TAG_CONVERTER)) as $serviceName) {
            $manager->addSetup('addConverter', ['@'. $serviceName]);
        }
    }
}
