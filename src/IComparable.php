<?php

namespace Bogosoft\Collections;

/**
 * Indicates that an implementing type compare itself to another object.
 *
 * @package Bogosoft\Collections
 */
interface IComparable
{
    /**
     * Compare the current object to another.
     *
     * @param  mixed $other An object with which to compare the current
     *                      object.
     * @return int          The result of comparing the current object
     *                      to a given object.
     */
    function compare($other): int;
}