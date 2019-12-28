<?php

declare(strict_types=1);

namespace Tests;

use Bogosoft\Collections\IComparable;

class Person implements IComparable
{
    public int $age;
    public string $name;

    function __construct(string $name, int $age)
    {
        $this->age  = $age;
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    function compare($other): int
    {
        return $this->age - $other->age;
    }
}