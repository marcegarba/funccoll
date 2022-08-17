<?php

namespace Marcegarba\FuncColl;

use ArrayAccess;
use Countable;
use LogicException;
use Closure;

/**
 * This is a useful superset of the PHP arrays.
 * <p>
 * It actually implements an <i>immutable</i> collection, where the
 * collection itself cannot be modified (i.e. set, shrunk, enlarged),
 * although the individual elements may have their state modified
 * externally (i.e. it doesn't guarantee immutability of the elements).
 * </p>
 * <p>
 * The class implements <i>method chaining</i>, so that methods
 * can be invoked one after the other, in a way similar to how
 * modern languages like Clojure and Ruby.
 * </p>
 * <p>
 * The class <i>behaves</i> as an array (i.e. it implements
 * <code>ArrayAccess</code>), so it can be used exactly like it,
 * with the exception that elements cannot be modified or unset.
 * </p>
 * <p>
 * Here's a simple example, where a list of sequential numbers are
 * first filtered (odd numbers removed), then the list is squared,
 * and finally a sum of all elements is retrieved:
 * <pre>
 * $myval =
 *     Collection::fromArray(range(1, 10))
 *         ->filter(function ($elem) { return $elem % 2 == 0; })
 *         ->map(function ($elem) { return $elem * $elem; })
 *         ->reduce(function ($a, $b) { return $a + $b; }, 0);
 * echo $myval;   // 120
 * </pre>
 * </p>
 *
 * @author Marcelo Garbarino <jda1419-github@yahoo.com.ar>
 */
class Collection implements ArrayAccess, Countable
{

    /**
     * The actual array holding the collection values.
     *
     * @var array
     */
    private $arr;

    /**
     * Initializes a collection from an array.
     *
     * @param  array      $arr the array to form the collection from
     * @return Collection the new collection
     */
    public static function fromArray(array $arr = [])
    {
        return new Collection($arr);
    }

    /**
     * Initializes a collection from a generator.
     * <p>
     * The closure be called and each return value will be stored in
     * the collection's inner array, until the return value evaluates
     * to false, 0, null or empty.
     * </p>
     * <p>
     * Parameter <i>$maxItems</i> is used to limit the number of elements
     * generated.
     * </p>
     * <p>
     * This static method has many uses; for instance, when extracting
     * the results of a SQL query:
     * <pre>
     * $stmt = $pdo->query('SELECT * from items');
     * $col = Collections::generate(
     *         function () use ($stmt) {
     *             return $stmt->fetch(PDO::FETCH_ASOC);
     *         }
     *      )->map(function ($row) { return new Item($row); });
     * </pre>
     * </p>
     *
     * @param  Closure    $generator the generator closure
     * @param  int        $maxItems  [optional] the max amount of items the
     *                               collection will have; default = 1000
     * @return Collection the new collection with the generated
     *                              values
     */
    public static function generate(Closure $generator, $maxItems = 1000)
    {
        $arr = [];
        $pos = 0;
        while ($val = $generator()) {
            if (++$pos > $maxItems) {
                break;
            }
            $arr[] = $val;
        }

        return new Collection($arr);
    }

    /**
     * Extracts the underlying array from the collection.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->arr;
    }

    /**
     * Applies a closure to each element in the array,
     * and the resulting Collection has all the elements from
     * this one for whose <i>$callback</i> applied to them returns
     * <code>true</code>.
     * <p>
     * This uses the <code>array_filter()</code> PHP built-in function.
     * Take into account that the keys of the resulting array
     * (supporting the new collection)  are <b>not</b> rewritten.
     * </p>
     *
     * @param  Closure    $callback
     * @return Collection the collection with all elemens for whose
     *                             application of <i>$callback</i> returned
     *                             <code>true</code>
     */
    public function filter(Closure $callback)
    {
        $dest = array_filter($this->arr, $callback);

        return new Collection($dest);
    }

    /**
     * Applies a closure to each element in the array,
     * returning the transformed collection.
     * <p>
     * This uses the <code>array_map()</code> PHP built-in function.
     * </p>
     *
     * @param  Closure    $callback the closure to map each element
     * @return Collection the transformed collection
     */
    public function map(Closure $callback)
    {
        $dest = array_map($callback, $this->arr);

        return new Collection($dest);
    }

    /**
     * Applies a reduction closure to all elements in the colleciton,
     * until a single value is returned.
     * <p>
     * This uses the <code>array_reduce()</code> PHP built-in function.
     * </p>
     *
     * @param  Closure $callback the two-parameters closure to apply
     *                           to successive elements until a final
     *                           result is obtained
     * @param  mixed   $initial  [optional] the initial value; default: null
     * @return mixed
     */
    public function reduce(Closure $callback, $initial = null)
    {
        return array_reduce($this->arr, $callback, $initial);
    }

    /**
     * Returns the number of elements in the collection.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->arr);
    }

    /**
     * Generates a new collection with only the first <i>$length</i>
     * elements in it (if <i>$length</i> is positive or zero), or
     * the first element minus the last (abs(<i>$length</i>)), if
     * negative.
     * <p>
     * This uses the <code>array_slice()</code> PHP built-in function.
     * </p>
     *
     * @param  int        $length the length to take
     * @return Collection
     */
    public function take($length)
    {
        $dest = array_slice($this->arr, 0, (int) $length);

        return new Collection($dest);
    }

    /**
     * Generates a new collection with the first elements of this one, until
     * the evaluation of a closure returns <code>false</code>.
     *
     * @param  Closure    $callback   a one-parameter closure which should
     *                                return a boolean
     * @return Collection  a new collection with the first elements
     *                     from this collection where evaluating the
     *                     closure returns <code>true</code>
     */
    public function takeWhile(Closure $callback)
    {
        $dest = [];
        foreach($this->arr as $key => $elem) {
            if ($callback($elem)) {
                $dest[$key] = $elem;
            } else {
                break;
            }
        }

        return new Collection($dest);
    }

    /**
     * Generates a new collection dropping the first <i>$length</i>
     * elements (if <i>$length</i> is positive or zero), or
     * the the last (abs(<i>$length</i>)), if negative.
     * <p>
     * This uses the <code>array_slice()</code> PHP built-in function.
     * </p>
     *
     * @param  int        $length the number of elements to drop
     * @return Collection
     */
    public function drop($length)
    {
        $dest = array_slice($this->arr, (int) $length);

        return new Collection($dest);
    }

    /**
     * Generates a new collection with all but the first elements from this one
     * dropped, until the closure which evaluates them returns
     * <code>false</code>.
     *
     * @param  Closure    $callback   a one-parameter closure which should
     *                                return a boolean
     * @return Collection  a new collection with the first elements
     *                     from this collection dropped, until their evaluation
     *                     by <i>$callback</i> returns <code>false</code>
     */
    public function dropWhile(Closure $callback)
    {
        $dest = [];
        $exhausted = false;
        foreach($this->arr as $key => $elem) {
            if (!$exhausted && $callback($elem)) {
                continue;
            } else {
                $exhausted = true;
                $dest[$key] = $elem;
            }
        }

        return new Collection($dest);
    }

    /**
     * Generates a new collection with a flattened list of elements.
     * <p>
     * This uses the <code>array_walk_recursive()</code> PHP built-in
     * function, and implements an idea taken from Stack Overflow
     * (see link below)
     * </p>
     *
     * @see http://stackoverflow.com/a/1320156
     * @return Collection
     */
    public function flatten()
    {
        $dest = [];
        $array = $this->arr;
        array_walk_recursive($array, function ($elem) use (&$dest) {
            $dest[] = $elem;
        });

        return new Collection($dest);
    }

    /**
     * Sorts the collection elements according to a closure, and
     * return a new collection with the sorted elements.
     * <p>
     * The closure is passed two arguments, and it must return an
     * integer, where -1 indicates that the first argument goes
     * before the second one, 0 if they are equal, or +1 if the
     * second element goes before the first one.
     * </p>
     * <p>
     * This uses the <code>usort()</code> PHP built-in function.
     * </p>
     *
     * @param  Closure    $callback the closure to sort elements
     * @return Collection the transformed collection
     */
    public function sort(Closure $callback)
    {
        $arr = $this->arr;
        usort($arr, $callback);

        return new Collection($arr);
    }

    /**
     * Returns the first element in the collection where the closure
     * returns <code>true</code>.
     * <p>
     * The closure is passed as an argument each of the elements of
     * the collection, until the result is <code>true</code>.
     * </p>
     * <p>
     * If no element in the collection fulfills the condition, then
     * <code>null</code> is returned.
     * </p>
     *
     * @param  Closure $callback the closure to evaluate elements
     * @return mixed   the found element, or <code>null</code>
     */
    public function findFirst(Closure $callback)
    {
        $ret = null;
        foreach ($this->arr as $elem) {
            if ($callback($elem)) {
                $ret = $elem;
                break;
            }
        }

        return $ret;
    }

    /**
     * Returns the first element of the collection, or <code>null</code>
     * if the collection is empty.
     *
     * @return mixed the first element, or <code>null</code> if the collection is empty
     */
    public function head()
    {
        if (count($this->arr)) {
            return $this->arr[0];
        } else {
            return null;
        }
    }

    /**
     * Returns a collection with all the elements but the first.
     * <p>
     * If the collection has zero or one element, then an empty collection
     * is returned.
     * </p>
     *
     * @return Collection
     */
    public function tail()
    {
        return new Collection(array_slice($this->arr, 1));
    }

    /**
     * Returns a collection with all the elements in this collection,
     * but with regenerated numeric indexes.
     * <p>
     * This uses the <code>array_values()</code> PHP built-in function.
     * </p>
     *
     * @return Collection
     */
    public function values()
    {
        return new Collection(array_values($this->arr));
    }

    /**
     * Runs a closure on each element in the collection, without generating
     * another collection.
     * <p>
     * The closure can have one or two arguments, the first being the value
     * and the second the key/index.
     * </p>
     * <p>
     * This uses the <code>array_walk()</code> PHP built-in function.
     * </p>
     *
     * @param  Closure $callback the closure to evaluate on each element
     * @return void
     */
    public function each(Closure $callback)
    {
        $array = $this->arr;
        array_walk($array, $callback);
    }

    /**
     * Runs a closure on each element in the collection, and returns
     * a map where the key is the result of the closure applied to each
     * element and the value is an array/hash with all the elements that
     * match that calculated key.
     * <p>
     * The key of each element in the array is preserved.
     * </p>
     *
     * @param  Closure    $callback the closure to generate the key from
     *                              each element
     * @return Collection
     */
    public function groupBy(Closure $callback)
    {
        $grouped = [];
        foreach ($this->arr as $key => $elem) {
            $grouped[$callback($elem)][$key] = $elem;
        }

        return new Collection($grouped);
    }

    /**
     * Creates the object.
     *
     * @param array $arr [optional] the initial array; default: []
     */
    private function __construct(array $arr = [])
    {
        $this->arr = $arr;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->arr[$offset]);
    }

    public function offsetGet($offset)
    {
        if (isset($this->arr[$offset])) {
            $result = $this->arr[$offset];
        } else {
            $result = null;
        }

        return $result;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new LogicException('Collection object is immutable');
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new LogicException('Collection object is immutable');
    }

}
