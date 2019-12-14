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

The following functions all return an instance of the `Sequence` class.

Function|Description
--------|-----------
`seq(iterable)`|Creates a sequence from an iterable source (i.e., an `array` or anything that implements `Traversable`).
`seqv(mixed ... $items)`|Creates a sequence from zero or more variadic arguments.

#### `Sequence` Methods

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
- `take`
- `toArray`