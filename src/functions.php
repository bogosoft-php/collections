<?php

declare(strict_types=1);

namespace Bogosoft\Collections;

/**
 * Create a new {@see Sequence} from a given iterable object.
 *
 * @param  iterable $source An iterable object.
 * @return Sequence         A new sequence.
 */
function seq(iterable $source = null) : Sequence
{
    return new Sequence($source ?? []);
}

/**
 * Create a new {@see Sequence} from zero or more variadic arguments.
 *
 * @param  mixed    ...$items An arbitrary number of items.
 * @return Sequence           A new sequence.
 */
function seqv(...$items) : Sequence
{
    return new Sequence($items);
}