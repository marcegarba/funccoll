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
	 * @var arraty
	 */
	private $arr;

	/**
	 * Initializes a collection from an array.
	 *
	 * @param  array       $arr the array to form the collection from
	 * @return Collection  the new collection
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
	 *      )->map(function ($row) { return new Items($row); });
	 * </pre>
	 * </p>
	 *
	 * @param   Closure $generator  the generator closure
	 * @param   int     $maxItems   [optional] the max amount of items the
	 *                              collection will have; default = 1000
	 * @return  Collection          the new collection with the generated
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
	 * @param Closure $callback
	 * @return Collection  the collection with all elemens for whose
	 *                     application of <i>$callback</i> returned
	 *                     <code>true</code>
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
	 * @param Closure $callback   the closure to map each element
	 * @return Collection         the transformed collection
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
	 * @param  Closure $callback  the two-parameters closure to apply
	 *                            to successive elements until a final
	 *                            result is obtained
	 * @param  mixed   $initial   [optional] the initial value; default: null
	 * @return mixed
	 */
	public function reduce(Closure $callback, $initial = null)
	{
		return array_reduce($this->arr, $callback, $initial);
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

	public function offsetSet($offset, $value)
	{
		throw new LogicException('Collection object is immutable');
	}

	public function offsetUnset($offset)
	{
		throw new LogicException('Collection object is immutable');
	}

	public function count()
	{
		return count($this->arr);
	}

}
