<?php

/**
 * Utility class for making PHP reflection easier to use.
 */
class ReflectionHelper
{
    private static $privateVarsToReset = array();

    /**
     * Resets any static vars that were set to their
     * original values (to not screw up later unit test runs).
     *
     * @return void
     */
    public static function reset()
    {
        foreach (self::$privateVarsToReset as $class => $valueNames) {
            foreach ($valueNames as $valueName => $originalValue) {
                self::setStaticValue($class, $valueName, $originalValue, $saveOriginalValue = false);
            }
        }
        self::$privateVarsToReset = array();
    }

    /**
     * Get the value of a static private or public class property.
     * Used to test internals of class without having to make the property public
     *
     * @param string $class
     * @param string $valueName
     * @return mixed|null
     */
    public static function getStaticValue($class, $valueName)
    {
        $reflectionClass = new ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty($valueName);
        $value = $reflectionProperty->getValue();

        return $value;
    }

    /**
     * Set the value of a static private or public class property.
     * Used to test internals of class without having to make the property public
     *
     * @param string $class
     * @param string $valueName
     * @param mixed|null $value
     * @param bool|void $saveOriginalValue
     * @return void
     */
    public static function setStaticValue($class, $valueName, $value, $saveOriginalValue = true)
    {
        $reflectionClass = new ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty($valueName);

        // to prevent side-effects in later tests, we need to remember the original value and reset it on tear down
        // Keep the first value even when it was null.
        if ($saveOriginalValue && (!isset(self::$privateVarsToReset[$class]) || !array_key_exists($valueName, self::$privateVarsToReset[$class]))) {
            self::$privateVarsToReset[$class][$valueName] = $reflectionProperty->getValue();
        }
        $reflectionProperty->setValue(null, $value);

        // clean up
    }

    /**
     * @param object $object
     * @param string $valueName
     *
     * @return mixed|null
     */
    public static function getValueOnObject($object, $valueName)
    {
        $reflectionObject = new ReflectionObject($object);
        $reflectionProperty = $reflectionObject->getProperty($valueName);
        $value = $reflectionProperty->getValue($object);

        return $value;
    }
}
