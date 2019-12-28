<?php

declare(strict_types=1);

namespace Bogosoft\Collections;

use Bogosoft\Core\InvalidOperationException;
use Traversable;

/**
 * Determine if all of the items in the given sequence satisfy a given
 * condition.
 *
 * The predicate is expected to be of the form:
 * - fn({@see mixed}): {@see bool}
 *
 * @param  iterable $items     Any type that is an array or implements
 *                             {@see Traversable}.
 * @param  callable $predicate A condition with which each item in the
 *                             given sequence will be checked against.
 * @return bool                True if every item in the given sequence
 *                             satisfies the given condition; false
 *                             if at least one item does not match.
 */
function all(iterable $items, callable $predicate): bool
{
    return seq($items)->all($predicate);
}

/**
 * Determine if a given sequence contains at least one item that matches
 * a given predicate. If no predicate is given, the given sequence will be
 * evaluated as to whether it is empty.
 *
 * The predicate is expected to be of the form:
 * - fn({@see mixed}): {@see bool}
 *
 * @param  iterable      $items     Any type that is an array or implements
 *                                  {@see Traversable}.
 * @param  callable|null $predicate A condition with which the current
 *                                  sequence will be checked.
 * @return bool                     True if the given sequence contains at
 *                                  least one item that satisfies the given
 *                                  condition; false otherwise. If no
 *                                  predicate was given, this function will
 *                                  return false if the given sequence was
 *                                  empty.
 */
function any(iterable $items, callable $predicate = null): bool
{
    return seq($items)->any($predicate);
}

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
 * Initialize a sequence with a singleton seed value and an expander
 * function.
 *
 * @param  mixed    $seed     A singleton seed value to be expanded
 *                            into a sequence of items.
 * @param  callable $expander A strategy for converting a single item
 *                            into a sequence of items.
 * @return Sequence           The result of expanding the given
 *                            singleton value.
 */
function seqi($seed, callable $expander): Sequence
{
    return seq([$seed])->collect($expander);
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

/**
 * Sort a given sequence of items.
 *
 * @param  iterable      $items    An array or an object that implements
 *                                 {@see Traversable}.
 * @param  callable|null $comparer A comparer to be applied to each item
 *                                 in the current sequence. If omitted,
 *                                 a default, naive algorithm will be
 *                                 used instead.
 * @return Sequence                A new, sorted sequence.
 */
function sort(iterable $items, callable $comparer = null): Sequence
{
    return seq($items)->sort($comparer);
}

/**
 * Sort a given sequence of items using their own comparison
 * methods.
 *
 * NOTE: This function assumes all items in the given sequence
 * implement the {@see IComparable} interface.

 * @param  iterable $items      A sequence of items to sort.
 * @param  bool     $descending Whether or not the sequence is to be
 *                              sorted in ascending or descending
 *                              order.
 * @return Sequence             A new, sorted sequence.
 *
 * @throws InvalidOperationException when the sequence is not wholly
 *                                   comprised of objects that
 *                                   implement the {@see IComparable}
 *                                   interface.
 */
function sortc(iterable $items, bool $descending = true): Sequence
{
    return seq($items)->sortc($descending);
}