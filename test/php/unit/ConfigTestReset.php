<?php

trait ConfigTestReset
{
    protected function resetConfig(): void
    {
        $reflection = new ReflectionClass(Config::class);
        foreach ([
            'register',
            '_config_cache',
            '_keys_cache',
            'shadow_register',
            '_shadow_config_cache',
            '_shadow_keys_cache',
        ] as $property) {
            $reflection->getProperty($property)->setValue(null, []);
        }
        $reflection->getProperty('_use_sandbox')->setValue(null, false);
    }
}
