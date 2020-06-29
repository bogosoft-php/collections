<?php

declare(strict_types=1);

namespace Bogosoft\Collections;

use Bogosoft\Core\InvalidOperationException;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;
use TypeError;

/**
 * Represents a sequence of items.
 *
 * @package Bogosoft\Collections
 */
class Sequence implements Countable, IteratorAggregate
{
    private static function sortDefault($a, $b): int
    {
        if ($a == $b)
            return 0;
        else
            return $a > $b ? 1 : -1;
    }

    private iterable $source;

    /**
     * Create a new sequence of items.
     *
     * @param iterable $source The source of items for the new sequence.
     */
    function __construct(iterable $source)
    {
        $this->source = $source;
    }

    /**
     * Determine if all of the items in the current sequence satisfy a given
     * condition.
     *
     * The predicate is expected to be of the form:
     * - fn({@see mixed}): {@see bool}
     *
     * @param  callable $predicate A condition with which each item in the
     *                             current sequence will be checked against.
     * @return bool                True if every item in the current sequence
     *                             satisfies the given condition; false
     *                             if at least one item does not match.
     */
    function all(callable $predicate): bool
    {
        foreach ($this as $item)
            if (!$predicate($item))
                return false;

        return true;
    }

    /**
     * Determine if the current sequence contains at least one item that matches
     * a given predicate. If no predicate is given, the current sequence will be
     * evaluated as to whether it is empty.
     *
     * The predicate is expected to be of the form:
     * - fn({@see mixed}): {@see bool}
     *
     * @param  callable|null $predicate A condition with which the current sequence
     *                                  will be checked.
     * @return bool                     True if the given current contains at least
     *                                  one item that satisfies the given condition;
     *                                  false otherwise. If no predicate was given,
     *                                  this function will return false if
     *                                  the current sequence was empty.
     */
    function any(callable $predicate = null): bool
    {
        $predicate ??= fn($item): bool => true;

        foreach ($this as $item)
            if ($predicate($item))
                return true;

        return false;
    }

    /**
     * Append zero or more items to the current sequence.
     *
     * @param  mixed    ...$items Item(s) to be appended to the current sequence.
     * @return Sequence           The current sequence with the given item(s) appended to it.
     */
    function append(...$items) : Sequence
    {
        return new class($this, $items) extends Sequence
        {
            /** @var mixed */
            private $items;

            function __construct(iterable $source, $items)
            {
                parent::__construct($source);

                $this->items = $items;
            }

            function getIterator() : Traversable
            {
                yield from $this->getSource();
                yield from $this->items;
            }
        };
    }

    /**
     * Apply either the current sequence or the result of folding the current sequence
     * to a given applicator function.
     *
     * The applicator is expected to be of the forms:
     * - No accumulator provided => fn({@see iterable} current_sequence): {@see mixed}
     * - Accumulator provider    => fn({@see mixed} accumulator_output): {@see mixed}
     *
     * Accumulator function form (if provided):
     * - fn({@see mixed} accumulator_input, {@see mixed} item): {@see mixed} accumulator_output
     *
     * @param  callable      $applicator  A function to which either the current sequence
     *                                    or the result of folding the current sequence
     *                                    will be passed.
     * @param  callable|null $accumulator An optional accumulator function. If null,
     *                                    the current sequence will be passed in whole
     *                                    to the given applicator function.
     * @param  null          $seed        An optional seed with which to begin folding
     *                                    the current sequence.
     * @return mixed                      The result of calling the given applicator
     *                                    function with either the current sequence or
     *                                    the result of folding the current sequence.
     */
    function apply(callable $applicator, callable $accumulator = null, $seed = null)
    {
        return null === $accumulator
             ? $applicator($this)
             : $applicator($this->fold($accumulator, $seed));
    }

    /**
     * Expand each item of the current sequence into its own sequence and flatten
     * the results into a single sequence.
     *
     * The expander is expected to be of the form:
     * - fn({@see mixed}): {@see iterable}
     * 
     * @param  callable $expander A strategy for expanding an item into a sequence.
     * @return Sequence           A flattened sequence of each item in the current
     *                            sequence expanded to its own sequence.
     */
    function collect(callable $expander) : Sequence
    {
        return new class($this, $expander) extends Sequence
        {
            /** @var callable */
            private $expander;

            function __construct(Sequence $seq, callable $expander)
            {
                parent::__construct($seq);

                $this->expander = $expander;
            }

            function getIterator() : Traversable
            {
                foreach ($this->getSource() as $x)
                    foreach (($this->expander)($x) as $y)
                        yield $y;
            }
        };
    }

    /**
     * Count the number of items in the current sequence. An optional predicate can be provided
     * to filter the sequence before counting starts.
     *
     * The predicate is expected to be of the form:
     * - fn({@see mixed} item): {@see bool}
     *
     * @param  callable|null $predicate An optional predicate by which to filter the current
     *                                  sequence before filtering.
     * @return int                      The number of items in the current sequence.
     */
    function count(callable $predicate = null) : int
    {
        if (null === $predicate)
        {
            $i = 0;

            foreach ($this->getIterator() as $item)
                ++$i;

            return $i;
        }

        return $this->filter($predicate)->count();
    }

    /**
     * Filter the current sequence according to a given condition.
     *
     * The predicate is expected to be of the form:
     * - fn({@see mixed} item): {@see bool}
     *
     * @param  callable $predicate A condition by which each item in the
     *                             current sequence is to be filtered.
     * @return Sequence            The current sequence, filtered.
     */
    function filter(callable $predicate) : Sequence
    {
        return new class($this, $predicate) extends Sequence
        {
            /** @var callable */
            private $predicate;

            function __construct(iterable $source, callable $predicate)
            {
                parent::__construct($source);

                $this->predicate = $predicate;
            }

            function getIterator() : Traversable
            {
                foreach ($this->getSource() as $key => $value)
                    if (($this->predicate)($value))
                        yield $key => $value;
            }
        };
    }

    /**
     * Fold the current sequence into a single value using a given accumulator function
     * and an optional seed value.
     *
     * The accumulator (if provided) is expected to be of the form:
     * - fn({@see mixed} accumulator_input, {@see mixed} item}: {@see mixed} accumulator_output
     *
     * @param  callable $accumulator An accumulator function. This function has the form:
     * @param  null     $seed        A seed value with which to begin the folding process.
     * @return mixed                 The result of applying an accumulator function and an
     *                               optional seed value to the current sequence.
     */
    function fold(callable $accumulator, $seed = null)
    {
        $result = $seed;

        foreach ($this as $item)
            $result = $accumulator($result, $item);

        return $result;
    }

    /**
     * Get either the first item in the current sequence or the first item in the current sequence that
     * matches a given predicate (if provided).
     *
     * The predicate is expected to be of the form:
     * - fn({@see mixed} item): {@see bool}
     *
     * @param  callable|null $predicate Criteria by which the current sequence is to be filtered first.
     * @return mixed                    The first item in the current sequence or the first item in the current
     *                                  sequence that matches a given predicate (if provided).
     *
     * @throws InvalidOperationException when the sequence is empty.
     */
    function getFirst(callable $predicate = null)
    {
        if (null === $predicate)
        {
            foreach ($this::getIterator() as $item)
                return $item;

            throw new InvalidOperationException('Sequence is empty.');
        }
        else
        {
            return $this->filter($predicate)->getFirst();
        }
    }

    /**
     * Get either the first item in the current sequence or a default value if the current
     * sequence is empty. An optional predicate can be supplied to filter the sequence before
     * trying to get the first value.
     *
     * The predicate is expected to be of the form:
     * - fn({@see mixed} item): {@see bool}
     *
     * @param mixed|null    $default   A default value to return if the sequence is empty.
     * @param callable|null $predicate An option predicate to filter the current sequence by
     *                                 before attempting to get the first value.
     * @return mixed                   The first value in the current or filtered sequence or
     *                                 the given default value if the sequence is empty.
     */
    function getFirstOrDefault($default = null, callable $predicate = null)
    {
        if (null === $predicate)
        {
            foreach ($this->getIterator() as $item)
                return $item;

            return $default;
        }

        return $this->filter($predicate)->getFirstOrDefault($default);
    }

    /**
     * Get an iterator for the current sequence.
     *
     * @return Traversable A data structure capable of iterating over the items in the current sequence.
     */
    function getIterator() : Traversable
    {
        foreach ($this->source as $item)
            yield $item;
    }

    /**
     * Get the last item in the current sequence. An optional predicate can supplied to filter
     * the sequence before attempting to get the last item.
     *
     * The predicate is expected to be of the form:
     * - fn({@see mixed} item): {@see bool}
     *
     * @param  callable|null $predicate Criteria by which the current sequence is to be filtered first.
     * @return mixed                    The last element of the sequence.
     *
     * @throws InvalidOperationException when the sequence is empty.
     */
    function getLast(callable $predicate = null)
    {
        $seq = null === $predicate
             ? $this
             : $this->filter($predicate);

        $empty = true;
        $found = null;

        foreach ($seq as $item)
        {
            $empty = false;
            $found = $item;
        }

        if ($empty)
            throw new InvalidOperationException('The sequence is empty.');

        return $found;
    }

    /**
     * Get the last item in the current sequence or a default value if the sequence is empty. An
     * optional predicate can be supplied to filter the current sequence before attempting to get
     * the last item.
     *
     * The predicate is expected to be of the form:
     * - fn({@see mixed} item): {@see bool}
     *
     * @param  mixed|null               A default value to return if the current sequence is empty.
     * @param  callable|null $predicate Criteria by which the current sequence is to be filtered first.
     * @return mixed                    The last item of the sequence.
     */
    function getLastOrDefault($default = null, callable $predicate = null)
    {
        $seq = null === $predicate
             ? $this
             : $this->filter($predicate);

        $found = $default;

        foreach ($seq as $item)
            $found = $item;

        return $found;
    }

    /**
     * Get the only item from the current sequence. An optional predicate can be provided to
     * filter the current sequence before the operation.
     *
     * The predicate is expected to be of the form:
     * - fn({@see mixed} item): {@see bool}
     *
     * @param  callable|null $predicate An optional set of criteria for filtering the current
     *                                  sequence before the operation.
     * @return mixed                    The first item from the current sequence.
     *
     * @throws InvalidOperationException when the resulting sequence is empty or contains
     *                                   more than one item.
     */
    function getSingle(callable $predicate = null)
    {
        $seq = null === $predicate
             ? $this
             : $this->filter($predicate);

        $count = 0;
        $found = null;

        foreach ($seq as $item)
        {
            if (++$count > 1)
                throw new InvalidOperationException('The sequence contains more than one (1) item.');

            $found = $item;
        }

        if (0 === $count)
            throw new InvalidOperationException('The sequence contains more than a single item.');

        return $found;
    }

    /**
     * Get the only item from the current sequence or a given default value if the current
     * sequence contains zero or more than one item. An optional predicate can be provided
     * to filter the current sequence before the operation.
     *
     * The predicate is expected to be of the form:
     * - fn({@see mixed} item): {@see bool}
     *
     * @param null          $default   A default value to be used in the event that the
     *                                 sequence contains zero or more than one item.
     * @param callable|null $predicate Optional criteria to be used to filter the
     *                                 current sequence before the operation.
     * @return mixed|null              The only item in the sequence or the default value
     *                                 if the resulting sequence was empty or contained
     *                                 more than one item.
     */
    function getSingleOrDefault($default = null, callable $predicate = null)
    {
        $seq = null === $predicate
             ? $this
             : $this->filter($predicate);

        $count = 0;
        $found = null;

        foreach ($seq as $item)
        {
            if (++$count > 1)
                return $default;

            $found = $item;
        }

        return 1 === $count ? $found : $default;
    }

    /**
     * Get the source of items for the current sequence.
     *
     * @return iterable The source of items for the current sequence.
     */
    protected function getSource() : iterable
    {
        return $this->source;
    }

    /**
     * Iterate through the current sequence and apply the given action to each item.
     *
     * The action is expected to be of the form:
     * - fn({@see mixed} item): {@see void}
     *
     * @param callable $action An action to be applied to each item in the current sequence.
     */
    function iter(callable $action) : void
    {
        foreach ($this as $item)
            $action($item);
    }

    /**
     * Project each item in the current sequence into a different form.
     *
     * The mapper is expected to be of the form:
     * - fn({@see mixed} item): {@see mixed}
     *
     * @param  callable $mapper A strategy for projecting an item into a different form.
     * @return Sequence         A sequence of each item in the current sequence in a projected form.
     */
    function map(callable $mapper) : Sequence
    {
        return new class($this, $mapper) extends Sequence
        {
            /** @var callable */
            private $mapper;

            function __construct(iterable $source, callable $mapper)
            {
                parent::__construct($source);

                $this->mapper = $mapper;
            }

            function getIterator() : Traversable
            {
                foreach ($this->getSource() as $key => $value)
                    yield $key => ($this->mapper)($value);
            }
        };
    }

    /**
     * Prepend zero or more items to the current sequence.
     *
     * @param  mixed    ...$items Item(s) to be prepended to the current sequence.
     * @return Sequence           The current sequence with the given item(s) prepended to it.
     */
    function prepend(...$items) : Sequence
    {
        return new class($this, $items) extends Sequence
        {
            private iterable $items;

            function __construct(iterable $source, iterable $items)
            {
                parent::__construct($source);

                $this->items = $items;
            }

            function getIterator() : Traversable
            {
                yield from $this->items;
                yield from $this->getSource();
            }
        };
    }

    /**
     * Create a new sequence from the current sequence where a given number of items
     * from the start of the sequence will be skipped once the sequence is iterated.
     *
     * @param  int      $count The number of items to skip once iteration starts.
     * @return Sequence        The current sequence without the skipped items.
     *
     * @throws InvalidArgumentException when the given count is less than zero.
     */
    function skip(int $count) : Sequence
    {
        if ($count < 0)
            throw new InvalidArgumentException('The amount to skip cannot be less than zero (0).');

        return new class($this, $count) extends Sequence
        {
            private int $count;

            function __construct(Sequence $source, int $count)
            {
                parent::__construct($source);

                $this->count = $count;
            }

            function getIterator() : Traversable
            {
                $i = 0;

                foreach ($this->getSource() as $key => $value)
                    if ($i++ >= $this->count)
                        yield $key => $value;
            }
        };
    }

    /**
     * Sort the current sequence.
     *
     * The comparer is expected to be of the form:
     * - fn({@see mixed}, {@see mixed}): {@see int}
     *
     * @param  callable|null $comparer A comparer to be applied to each item
     *                                 in the current sequence. If omitted,
     *                                 a default, naive algorithm will be
     *                                 used instead.
     * @return Sequence                The current sequence, sorted.
     */
    function sort(callable $comparer = null): Sequence
    {
        $comparer ??= Sequence::class . '::sortDefault';

        $items = [...$this];

        usort($items, $comparer);

        return new Sequence($items);
    }

    /**
     * Sort the current sequence of items using their own comparison
     * methods.
     *
     * NOTE: This method assumes all items in the current sequence
     * implement the {@see IComparable} interface.
     *
     * @param  bool     $descending Whether or not the sequence is to be
     *                              sorted in ascending or descending
     *                              order.
     * @return Sequence             The current sequence, sorted.
     *
     * @throws InvalidOperationException when the sequence is not wholly
     *                                   comprised of objects that
     *                                   implement the {@see IComparable}
     *                                   interface.
     */
    function sortc(bool $descending = false): Sequence
    {
        $multiplier = $descending ? -1 : 1;

        $comparer = fn(IComparable $a, IComparable $b): int => $a->compare($b) * $multiplier;

        try
        {
            return $this->sort($comparer);
        }
        catch (TypeError $te)
        {
            throw new InvalidOperationException('', 0, $te);
        }
    }

    /**
     * Take a specified number of items from the current sequence.
     *
     * @param  int      $count The number of items to take from the current sequence.
     * @return Sequence        The current sequence with only the specified number of items.
     *
     * @throws InvalidArgumentException when the given count is less than zero.
     */
    function take(int $count) : Sequence
    {
        if ($count < 0)
            throw new InvalidArgumentException('The amount to take cannot be less than zero (0).');

        return new class($this, $count) extends Sequence
        {
            private int $count;

            function __construct(Sequence $source, int $count)
            {
                parent::__construct($source);

                $this->count = $count;
            }

            function getIterator() : Traversable
            {
                $taken = 0;

                foreach ($this->getSource() as $key => $value)
                    if ($taken++ < $this->count)
                        yield $key => $value;
                    else
                        break;
            }
        };
    }
}