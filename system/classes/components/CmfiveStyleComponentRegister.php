<?php

declare(strict_types=1);

class CmfiveStyleComponentRegister extends CmfiveComponentRegister
{
    protected static $_register = [];

    public static function outputStyles(): void
    {
        usort(array: static::$_register, callback: ['CmfiveComponentRegister', 'compareWeights']);

        array_map(callback: function ($style): void {
            echo $style->include() . "\n";
        }, array: static::getComponents() ?: []);
    }
}
