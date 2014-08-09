# PHP functional collection
[![Build Status](https://secure.travis-ci.org/marcegarba/funccoll.png?branch=master)](https://travis-ci.org/marcegarba/funccoll)
[![Coverage Status](https://coveralls.io/repos/marcegarba/funccoll/badge.png)](https://coveralls.io/r/marcegarba/funccoll)
[![Stable](https://poser.pugx.org/marcegarba/funccoll/v/stable.svg)](https://packagist.org/packages/marcegarba/funccoll)
&nbsp;&nbsp;&nbsp;&nbsp;
[![License](https://poser.pugx.org/marcegarba/funccoll/license.svg)](https://packagist.org/packages/marcegarba/funccoll)

This little library was inspired by Martin Fowler's [collection pipeline](http://martinfowler.com/articles/collection-pipeline/) pattern,
with the intent of implementing that pattern in PHP.

The beauty in the Ruby and Clojure examples in the article contrast with PHP's clunky syntax for lambdas (closures); nevertheless, my intention is making it easy to
create collection pipelines in PHP.

## Installation

You can get this PHP library via a [composer](https://getcomposer.org) package in [Packagist](https://packagist.org).
Just add this dependency to your `composer.json`:

```json
{
    "require": {
        "marcegarba/funccoll": "dev-master"
    }
}
```

## How it works

The ```Collection``` class implements an immutable collection, backed by an array.

The class has two static factory methods, ```fromArray()``` and ```generate()```.

The former creates a Collection object by passing a PHP array.

The latter uses a closure to generate the collection elements.

### Immutability

Each transformation creates a new collection object.

The class itself implements ```ArrayAccess``` and ```Countable```, so that it somehow
can be used as an array; but it doesn't implement Iterator (for traversing the collection
by using the `foreach` language construct), because that would imply having a counter
and therefore not making the class immutable.

Being immutable, the ```offsetSet()``` and ```offsetUnset()``` implementation of the
```ArrayAccess``` interface throws a ```LogicException```.

The immutability has to do with the collection itself, not about the state of its elements.

### Extracting the array

The ```toArray()``` method extracts the original array.

If any mutation needs to be done, then it should be on this array. The mutated array,
of course, can be used to create another Collection object.

## Examples

Here are a couple of simple examples, using the two factory methods.

### Example 1: adding odd numbers times two

A list of consecutive numbers, from 1 to 10, is used to generate the first object;
successive transformations then are applied, so as to obtain the sum of the double
of the first three odd numbers.

```php
use Marcegarba\FuncColl\Collection;

$sum = Collection::fromArray(range(1, 10))
    ->filter(function ($elem) { return $elem % 2 != 0; })
    ->take(3)
    ->map(function ($elem) { return $elem * 2; })
    ->reduce(function ($acc, $num) { return $acc + $num; });

echo $sum; // Outputs 18
```

### Example 2: extracting the rows from a PDO query

This example uses the result of a PDO query to generate a list of associative
arrays (with a maximum of 100 elements), each of which is then used to create an
entity object, based on that row contents, and the resulting array of
entity objects are stored in a variable.

```php
use Marcegarba\FuncColl\Collection;

$pdo = new PDO(...);

$stmt = $pdo->query('SELECT * from items');

$generator = function () use ($stmt) {
    return $stmt->fetch(PDO_ASSOC);
}

$items =
    Collection::generate($generator, 100)
    ->map(function ($row) { return new Item($row); });

```
