<?php

namespace Zycon42\ParamConverters\Converters;

use Zycon42\ParamConverters\Annotations\ParamConverter;
use Nette\Application\Request;

interface IParamConverter {

    /**
     * Convert parameters.
     * This is done by adding/replacing parameters in request.
     * @param Request $request
     * @param ParamConverter $configuration
     * @return bool
     */
    function convert(Request $request, ParamConverter $configuration);

    /**
     * Converts parameters back to original.
     * This is done by removing converted parameters from request
     * @param Request $request
     * @param ParamConverter $configuration
     * @return bool
     */
    function convertBack(Request $request, ParamConverter $configuration);

    /**
     * Checks if converter supports given parameter
     * @param ParamConverter $configuration
     * @return bool
     */
    function supports(ParamConverter $configuration);
}
