<?php

trait SingletonTrait
{
    private static $instances = [];

    public static function getInstance(\Web|null $w = null)
    {
        $class = get_called_class();
        if (!isset(self::$instances[$class])) {
            $reflectedParameters = (new \ReflectionClass($class))->getConstructor()?->getParameters() ?? [];
            if (!empty($reflectedParameters) && ($reflectedParameters[0]->name === 'w')) {
                self::$instances[$class] = new $class($w);
            } else {
                self::$instances[$class] = new $class();
            }

            if (method_exists(self::$instances[$class], "_web_init")) {
                self::$instances[$class]->_web_init();
            }
        }

        return self::$instances[$class];
    }
}
