# bogosoft/collections

This library contains the `Sequence` class for fluently working with and modifying sequences of items.

The `Sequence` class implements the following interfaces:

- `Countable`
- `IteratorAggregate`

### Requirements

- PHP >= 7.4

### Installation

```bash
composer require bogosoft/collections
```

#### Utility Functions

The following functions all return an instance of the `Sequence` class or provide `Sequence`-like logic in a functional manner.

Function|Description
--------|-----------
`all(iterable, callable)`|Determine if all of the items in a given sequence match a given condition.
`any(iterable, ?callable)`|Determine if any item in the given sequence matches a given condition.
`seq(iterable)`|Creates a sequence from an iterable source (i.e., an `array` or anything that implements `Traversable`).
`seqi(mixed, callable)`|Create a sequence from a singleton seed value and an expansion function.
`seqv(mixed ... $items)`|Creates a sequence from zero or more variadic arguments.
`sort(iterable, ?callable)`|Sort a sequence with an optional comparer.
`sortc(iterable, bool)`|Sort a sequence of `IComparable` objects in either ascending or descending order.

#### `Sequence` Methods

- `all`
- `any`
- `append`
- `apply`
- `collect`
- `count`
- `filter`
- `fold`
- `getFirst`
- `getFirstOrDefault`
- `getLast`
- `getLastOrDefault`
- `getSingle`
- `getSingleOrDefault`
- `iter`
- `map`
- `prepend`
- `skip`
- `sort`
- `sortc`
- `take`
- `toArray`