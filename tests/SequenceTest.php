<?php

declare(strict_types=1);

namespace Tests;

use Bogosoft\Collections\Sequence;
use Bogosoft\Core\InvalidOperationException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SplFixedArray;
use SplQueue;
use Traversable;
use function Bogosoft\Collections\seq;
use function Bogosoft\Collections\seqi;
use function Bogosoft\Collections\seqv;

class SequenceTest extends TestCase
{
    function testAllReturnsFalseWhenAtLeastOneItemInASequenceDoesNotMatchGivenPredicate(): void
    {
        $items = seq([0, 1]);

        $this->assertFalse($items->all(fn(int $i): bool => $i > 0));
    }

    function testAllReturnsTrueWhenEveryItemInASequenceMatchesAGivenCondition(): void
    {
        $items = seq([1, 2, 3, 4, 5]);

        $this->assertTrue($items->all(fn(int $i): bool => $i > 0));
    }

    function testAnyReturnsFalseForEmptySequence(): void
    {
        $items = seq();

        $this->assertEmpty($items);
        $this->assertFalse($items->any());
    }

    function testAnyReturnsFalseIfGivenConditionCannotBeMetOnNonEmptySequence(): void
    {
        $item  = 12;
        $items = seq([$item]);

        $this->assertNotEmpty($items);

        $this->assertFalse($items->any(fn(int $i): bool => $i !== $item));
    }

    function testAnyReturnsTrueIfGivenConditionCanBeMetOnNonEmptySequence(): void
    {
        $items     = seq([0, 1, 2, 3, 4]);
        $predicate = fn(int $i): bool => $i > 3;
        $actual    = $items->any($predicate);

        $this->assertTrue($actual);
    }

    function testAnyReturnsTrueIfSequenceHasAtLeastOneItemAndNoPredicateIsGiven(): void
    {
        $items = seq([0]);

        $this->assertTrue(count($items) > 0);
        $this->assertTrue($items->any());
    }

    function testCallingGlobalCountFunctionAndSequenceCountMethodAreEquivalent() : void
    {
        $source = seq([0, 1, 2, 3, 4]);

        $this->assertEquals(count($source), $source->count());
    }

    function testCanAppendItemsToASequence() : void
    {
        $expect = [1, 2, 3, 4];
        $actual = [...seq(['0'])->append(...$expect)];
        $this->assertEquals(count($expect) + 1, count($actual));
        $this->assertEquals(0, $actual[0]);

        for ($i = 1; $i < count($expect) + 1; $i++)
        {
            $this->assertEquals($expect[$i - 1], $actual[$i]);
        }
    }

    function testCanApplyActionToAllItemsInASequence() : void
    {
        $actual = new SplQueue();
        $action = fn(int $i) => $actual->enqueue($i);
        $expect = [0, 1, 2, 3, 4];

        seq($expect)->iter($action);

        $this->assertEquals(count($expect), $actual->count());

        $i = 0;

        while (!$actual->isEmpty())
        {
            $this->assertEquals($expect[$i++], $actual->dequeue());
        }
    }

    function testCanApplyASequenceToACallback() : void
    {
        $expect = [0, 1, 2, 3, 4];
        $actual = seq($expect)->apply(fn(Traversable $items) => iterator_to_array($items));

        $this->assertIsArray($actual);
        $this->assertEquals(count($expect), count($actual));

        for ($i = 0; $i < count($expect); $i++)
        {
            $this->assertEquals($expect[$i], $actual[$i]);
        }
    }

    function testCanApplyASequenceToACallbackAfterFolding() : void
    {
        $values      = [0, 1, 2, 3, 4];
        $accumulator = fn(?int $a, int $item): int => $a + $item;
        $expected    = array_reduce($values, $accumulator);
        $applicator  = fn(int $size): SplFixedArray => new SplFixedArray($size);
        $actual      = seq($values)->apply($applicator, $accumulator)->getSize();

        $this->assertEquals($expected, $actual);
    }

    function testCanConvertToArray() : void
    {
        $limit = 10;

        $generate = function() use ($limit) : iterable
        {
            for ($i = 0; $i < $limit; $i++)
            {
                yield $i;
            }
        };

        $values = seq($generate())->toArray();

        $this->assertTrue(is_array($values));
        $this->assertEquals($limit, count($values));

        for ($i = 0; $i < $limit; $i++)
            $this->assertEquals($i, $values[$i]);
    }

    function testCanCountAllItemsInANonEmptySequence() : void
    {
        $values = [0, 1, 2, 3, 4];
        $source = seq($values);

        $this->assertEquals(count($values), $source->count());
    }

    function testCanCountOnlyQualifiedItemsInANonEmptySequence() : void
    {
        $values = [0, 1, 2, 3, 4];
        $filter = fn(int $i): bool => 0 === $i % 2;
        $expect = count(array_filter($values, $filter));
        $actual = seq($values)->count($filter);

        $this->assertEquals($expect, $actual);
    }

    function testCanCreateSequenceFromArbitraryNumberOfArguments() : void
    {
        $actual = seqv();

        $this->assertInstanceOf(Sequence::class, $actual);
        $this->assertCount(0, $actual);

        $actual = seqv(1, true, 'oranges', -90);

        $this->assertInstanceOf(Sequence::class, $actual);
        $this->assertCount(4, $actual);
    }

    function testCanCreateSequenceFromSingletonValue(): void
    {
        $length   = 10;
        $seed     = 0;
        $expander = function(int $i) use ($length): iterable
        {
            for ($j = $i; $j < $i + $length; $j++)
            {
                yield $j;
            }
        };

        $actual = [...seqi($seed, $expander)];

        $this->assertEquals($length, count($actual));

        for ($i = 0; $i < $length; $i++)
        {
            $this->assertEquals($seed + $i, $actual[$i]);
        }
    }

    function testCanFilterValues() : void
    {
        $values    = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $predicate = fn(int $i): bool => 0 === $i % 2;
        $expected  = array_filter($values, $predicate);
        $sequence  = seq($values)->filter($predicate);

        $i = 0;

        foreach ($sequence as $actual)
        {
            $this->assertEquals($expected[$i], $actual);

            $i += 2;
        }
    }

    function testCanFlattenASequenceOfSequences() : void
    {
        $values = [
            [0, 1,  2,  3],
            [4, 5,  6,  7],
            [8, 9, 10, 11]
        ];

        $expander = fn(array $x): array => $x;

        $actual = [...seq($values)->collect($expander)];

        $j = 0;

        foreach ($values as $x)
        {
            foreach ($x as $y)
            {
                $this->assertEquals($actual[$j++], $y);
            }
        }
    }

    function testCanFoldASequence() : void
    {
        $values      = [0, 1, 2, 3, 4, 5];
        $accumulator = fn(?int $a, int $item): int => $a + $item;
        $expect      = array_reduce($values, $accumulator);
        $actual      = seq($values)->fold($accumulator);

        $this->assertEquals($expect, $actual);
    }

    function testCanGetFirstQualifiedValueWhenSequenceIsNotEmpty() : void
    {
        $values = [1, 2, 3, 4];
        $filter = fn(int $i): bool => 0 === $i % 2;
        $expect = array_values(array_filter($values, $filter))[0];
        $actual = seq($values)->getFirst($filter);

        $this->assertEquals($expect, $actual);
    }

    function testCanGetFirstValueWhenSequenceIsNotEmpty() : void
    {
        $values = [4, 3, 2, 1, 0];
        $source = seq($values);

        $this->assertEquals($source->getFirst(), $values[0]);
    }

    function testCanGetLastItemFromNonEmptySequence() : void
    {
        $values = [0, 1];
        $expect = $values[count($values) - 1];
        $actual = seq($values)->getLast();

        $this->assertEquals($expect, $actual);
    }

    function testCanGetLastQualifiedItemFromNonEmptySequence() : void
    {
        $values = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $filter = fn(int $i): bool => 1 === $i % 2;
        $expect = array_values(array_filter($values, $filter));
        $actual = seq($values)->getLast($filter);

        $this->assertEquals($expect[count($expect) - 1], $actual);
    }

    function testCanGetOnlyItemFromSequence() : void
    {
        $values = [0];

        $this->assertTrue(count($values) === 1);
        $this->assertEquals($values[0], seq($values)->getSingle());
    }

    function testCanGetOnlyQualifiedItemFromSequence() : void
    {
        $values = [0, 1];
        $filter = fn(int $i): bool => 1 === $i % 2;
        $expect = array_values(array_filter($values, $filter))[0];
        $actual = seq($values)->getSingle($filter);

        $this->assertEquals($expect, $actual);
    }

    function testCanMapValuesFromSequence() : void
    {
        $values = [];

        for ($i = 0; $i < 10; $i++)
        {
            $values[] = $i;
        }

        $mapper = fn(int $i) => $i * 2;

        $sequence = seq($values)->map($mapper);

        $i = 0;

        foreach ($sequence as $actual)
        {
            $expected = $mapper($values[$i++]);

            $this->assertEquals($expected, $actual);
        }
    }

    function testCanPerformMultipleMutatingOperationsOnASequence() : void
    {
        $values = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $filter = fn(int $i): bool => 1 === $i % 2;
        $mapper = fn(int $i): int => $i * 2;
        $expect = array_map($mapper, array_values(array_filter($values, $filter)));
        $source = seq($values)->filter($filter)->map($mapper);

        $i = 0;

        foreach ($source as $actual)
        {
            $this->assertEquals($expect[$i++], $actual);
        }
    }

    function testCanPrependItemsToASequence() : void
    {
        $expect = [0, 1, 2, 3];
        $actual = [...seq([4])->prepend(...$expect)];

        for ($i = 0; $i < count($expect); $i++)
        {
            $this->assertEquals($expect[$i], $actual[$i]);
        }

        $this->assertEquals(4, $actual[count($expect)]);
    }

    function testCanSkipItems() : void
    {
        $amount = 2;
        $values = ['1', '2', '3', '4', '5'];

        $i = $amount;

        foreach (seq($values)->skip($amount) as $actual)
        {
            $this->assertEquals($values[$i++], $actual);
        }
    }

    function testCanSortComparablesInAscendingOrder(): void
    {
        /** @var Person[] $people */
        $people   = [...seqi(16, PersonGenerator::class . '::generate')];
        $expected = $people;

        usort($expected, fn(Person $a, Person $b): int => $a->age - $b->age);

        /** @var Person[] $actual */
        $actual = [...seq($people)->sortc()];

        $this->assertEquals(count($expected), count($actual));

        for ($i = 0; $i < count($expected); $i++)
        {
            $this->assertEquals($expected[$i]->name, $actual[$i]->name);
        }
    }

    function testCanSortComparableInDescendingOrder(): void
    {
        /** @var Person[] $people */
        $people   = [...seqi(16, PersonGenerator::class . '::generate')];
        $expected = $people;

        usort($expected, fn(Person $a, Person $b): int => $b->age - $a->age);

        /** @var Person[] $actual */
        $actual = [...seq($people)->sortc(true)];

        $this->assertEquals(count($expected), count($actual));

        for ($i = 0; $i < count($expected); $i++)
        {
            $this->assertEquals($expected[$i]->name, $actual[$i]->name);
        }
    }

    function testCanSortUsingDefaultAlgorithm(): void
    {
        $integers = [0, 5, 3, 4, 1, 9, 7, 8, 2, 5, 6, 5];
        $expected = $integers;

        usort($expected, fn(int $a, int $b) => $a - $b);

        $actual = [...seq($integers)->sort()];

        $this->assertEquals(count($expected), count($actual));

        for ($i = 0; $i < count($expected); $i++)
        {
            $this->assertEquals($expected[$i], $actual[$i]);
        }
    }

    function testCanSortUsingGivenAlgorithm(): void
    {
        $integers = [0, 5, 3, 4, 1, 9, 7, 8, 2, 5, 6, 5];
        $expected = $integers;
        $comparer = fn(int $a, int $b): int => $a * 2 + $b;

        usort($expected, $comparer);

        $actual = [...seq($integers)->sort($comparer)];

        $this->assertEquals(count($expected), count($actual));

        for ($i = 0; $i < count($expected); $i++)
        {
            $this->assertEquals($expected[$i], $actual[$i]);
        }
    }

    function testCanTakeASpecifiedNumberOfItems() : void
    {
        $amount = 2;
        $values = [0, 1, 2, 3];

        $i = 0;

        foreach (seq($values)->take($amount) as $actual)
        {
            $this->assertEquals($values[$i++], $actual);
        }

        $this->assertEquals($amount, $i);
    }

    function testEmptySequenceReportsZeroItemsOnCount() : void
    {
        $this->assertEquals(0, seq()->count());
    }

    function testFirstOrDefaultReturnsDefaultFromEmptySequence() : void
    {
        $expect = 'Hello, World!';
        $actual = seq()->getFirstOrDefault($expect);

        $this->assertEquals($expect, $actual);
    }

    function testFirstOrDefaultReturnsDefaultFromUnqualifiedSequence() : void
    {
        $values = [0, 1, 2, 3, 4];
        $expect = 'Hello, World!';
        $filter = fn($x): bool => is_string($x);
        $actual = seq($values)->getFirstOrDefault($expect, $filter);

        $this->assertEquals($expect, $actual);
    }

    function testFirstOrDefaultReturnsFirstItemFromNonEmptySequence() : void
    {
        $values = ['apples', 'oranges'];
        $actual = seq($values)->getFirstOrDefault('bananas');

        $this->assertEquals($values[0], $actual);
    }

    function testFirstOrDefaultReturnsFirstQualifiedItemFromNonEmptySequence() : void
    {
        $values = [true, 0, 'pizza'];
        $filter = fn($x): bool => is_int($x);
        $expect = array_values(array_filter($values, $filter))[0];
        $actual = seq($values)->getFirstOrDefault(3.14, $filter);

        $this->assertEquals($expect, $actual);
    }

    function testLastOrDefaultReturnsDefaultFromEmptySequence() : void
    {
        $expect = 'Hello, World!';

        $this->assertEquals($expect, seq()->getLastOrDefault($expect));
    }

    function testLastOrDefaultReturnsDefaultFromUnqualifiedSequence() : void
    {
        $expect = 'Hello, World!';
        $values = [0, 1, 2, 3, 4];
        $filter = fn(int $i): bool => $i > 4;
        $actual = seq($values)->getLastOrDefault($expect, $filter);

        $this->assertEquals($expect, $actual);
    }

    function testLastOrDefaultReturnsLastItemFromNonEmptySequence() : void
    {
        $values = [0, 1, 2, 3, 4];
        $expect = $values[count($values) - 1];
        $actual = seq($values)->getLastOrDefault();

        $this->assertEquals($expect, $actual);
    }

    function testLastOrDefaultReturnsLastQualifiedItemFromNonEmptySequence() : void
    {
        $values = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $filter = fn(int $i): bool => 0 === $i % 2;
        $expect = array_values(array_filter($values, $filter));
        $actual = seq($values)->getLastOrDefault(null, $filter);

        $this->assertEquals($expect[count($expect) - 1], $actual);
    }

    function testSingleOrDefaultReturnsDefaultWhenSequenceContainsMoreThanOneItem() : void
    {
        $values = [0, 1];
        $expect = 'Hello, World!';
        $actual = seq($values)->getSingleOrDefault($expect);

        $this->assertTrue(count($values) > 1);
        $this->assertEquals($expect, $actual);
    }

    function testSingleOrDefaultReturnsDefaultWhenSequenceIsEmpty() : void
    {
        $expect = 'Hello, World!';
        $actual = seq()->getSingleOrDefault($expect);

        $this->assertEquals($expect, $actual);
    }

    function testSingleOrDefaultReturnsOnlyQualifiedItemFromNonEmptySequence() : void
    {
        $values = [0, 1, 2, 3, 4];
        $filter = fn(int $i): bool => 2 === $i;
        $expect = array_values(array_filter($values, $filter))[0];
        $actual = seq($values)->getSingleOrDefault(null, $filter);

        $this->assertEquals($expect, $actual);
    }

    function testSingleOrDefaultReturnsOnlyValueWhenSequenceContainsOneItem() : void
    {
        $expect = 'bananas';
        $actual = seq([$expect])->getSingleOrDefault('grapefruit');

        $this->assertEquals($expect, $actual);
    }

    function testSingleThrowsInvalidOperationExceptionWhenSequenceContainsMoreThanOneItem() : void
    {
        $values = ['apples', 'oranges'];

        $this->assertTrue(count($values) > 1);

        $this->expectException(InvalidOperationException::class);

        seq($values)->getSingle();
    }

    function testSingleThrowsInvalidOperationExceptionWhenSequenceIsEmpty() : void
    {
        $this->expectException(InvalidOperationException::class);

        seq()->getSingle();
    }

    function testSortingNonComparableSequenceWithComparableMethodThrowsInvalidOperationException() : void
    {
        $integers = seq([0, 3, 2, 1, 4]);

        $this->expectException(InvalidOperationException::class);

        $integers->sortc();
    }

    function testThrowsInvalidArgumentExceptionWhenSkipCountIsLessThanZero() : void
    {
        $this->expectException(InvalidArgumentException::class);

        seq()->skip(-1);
    }

    function testThrowsInvalidArgumentExceptionWhenTakeCountIsLessThanZero() : void
    {
        $this->expectException(InvalidArgumentException::class);

        seq()->take(-1);
    }

    function testThrowsInvalidOperationExceptionWhenTryingToGetFirstItemFromEmptySequence() : void
    {
        $source = seq();

        $this->expectException(InvalidOperationException::class);

        $actual = $source->getFirst();
    }

    function testThrowsInvalidOperationExceptionWhenTryingToGetLastItemFromEmptySequence() : void
    {
        $this->expectException(InvalidOperationException::class);

        seq()->getLast();
    }
}