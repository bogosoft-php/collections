<?php

namespace Tests;

use Traversable;

class PersonGenerator
{
    private static array $names = [
        'Ahmed',
        'Alice',
        'Bob',
        'Carly',
        'Else',
        'Geronimo',
        'Greta',
        'Jorge',
        'Pascal',
        'Priya',
        'Zohan'
    ];

    static function generate(int $count): Traversable
    {
        for ($i = 0; $i < $count; $i++)
        {
            $age  = rand(5, 100);
            $name = self::$names[rand(0, count(self::$names) - 1)];

            yield new Person($name, $age);
        }
    }
}