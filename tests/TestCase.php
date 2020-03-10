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

    public static function getMethod($object, $method, $arguments = [])
    {
        $class = new ReflectionClass($object);

        $result = $class->getMethod($method);

        $result->setAccessible(true);

        return count($arguments)
            ? $result->invoke($object)
            : $result->invokeArgs($object, $arguments);
    }

    public static function getPropertyValue($object, $property)
    {
        $reflection = new ReflectionClass($object);

        $result = $reflection->getProperty($property);

        $result->setAccessible(true);

        return $result->getValue($object);
    }
}
