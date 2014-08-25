<?php

namespace Zycon42\ParamConverters\Annotations;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 */
final class ParamConverter {

    /**
     * Parameter name
     * @var string
     */
    public $name;

    /**
     * Parameter class
     * @var string
     */
    public $class;

    /**
     * Array of options
     * @var array
     */
    public $options;
}
