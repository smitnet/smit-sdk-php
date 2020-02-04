<?php

namespace SMIT\SDK\Tests;

use ReflectionClass;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function dd()
    {
        die(var_dump(func_get_args()));
    }

    public function getProtectedPropertyValue($object, $property)
    {
        $reflection = new ReflectionClass($object);

        $result = $reflection->getProperty($property);

        $result->setAccessible(true);

        return $result->getValue($object);
    }
}
