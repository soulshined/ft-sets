<?php

namespace FT\Sets;

use InvalidArgumentException;

/**
 * This class sorts elements in ascending order by default. To descending sort, use `reverse()`
 */
class SortedSet extends StrictSet {
    private const ASC = 1;
    private const DESC = 0;
    private int $order = self::ASC;

    /**
     * @throws InvalidArgumentException when the value is not scalar or does not implement FT\Sets\Comparable
     */
    public function add(mixed $a) : bool
    {
        if (!is_scalar($a) && !($a instanceof Comparable))
            throw new InvalidArgumentException('Sorted sets only support scalar types or classes that implement Comparable');

        if (!parent::add($a)) return false;

        $this->sort();
        return true;
    }

    /**
     * SortedSet specific implementation is the same as parent, it just does not iterate through the values to filter non strings
     */
    public function levenshtein(string $query): ?string
    {
        if (!$this->is_managed_type("")) return null;

        if (empty($this)) return null;
        if ($this->size() === 1) return $this->toArray()[0];

        $closest = -1;

        foreach ($this as $s) {
            $lev = levenshtein($query, $s);

            if ($lev === 0) return $query;

            if ($lev < 11 && ($lev <= $closest || $closest < 0)) {
                $candidate = $s;
                $closest = $lev;
            }
        }

        return $closest === -1 ? null : $candidate;
    }

    /**
     * @return SortedSet a portion of this set using `array_slice` so same arguments are permitted here as there (like negative offsets)
     */
    public function partition(int $offset, ?int $length = null) : SortedSet
    {
        return new SortedSet(array_values(array_slice($this->hashtable, $offset, $length, true)));
    }

    /**
     * This method returns the least element in this set greater than or equal to the given element, or null if there is no such element
     */
    public function ceiling(string | int | float $value): string | int | float | null
    {
        if ($this->contains($value)) return $value;

        return $this->higher($value);
    }

    /**
     * This method returns the greatest element in this set less than or equal to the given element, or null if there is no such element
     */
    public function floor(string | int | float $value): string | int | float | null
    {
        if ($this->contains($value)) return $value;

        return $this->lower($value);
    }

    /**
     * This method returns the least element in this set strictly greater than the given element, or null if there is no such element
     */
    public function higher(string | int | float $value): string | int | float | null
    {
        if ($this->isEmpty()) return null;
        if (is_string($value) && !$this->is_managed_type($value)) return null;

        foreach ($this as $v)
            if ($v > $value) return $v;

        return null;
    }

    /**
     * This method returns the greatest element in this set strictly less than the given element, or null if there is no such element
     */
    public function lower(string | int | float $value): string | int | float | null
    {
        if ($this->isEmpty()) return null;
        if (is_string($value) && !$this->is_managed_type($value)) return null;

        foreach ($this->reverse() as $v)
            if ($v < $value) return $v;

        return null;
    }

    /**
     * This method will return all elements which are less than the given element
     */
    public function headSet(mixed $value) : SortedSet {
        $index = $this->indexOf($value);
        if ($index === -1) return $this;

        return $this->partition(0, $index);
    }

    /**
     * This method will return all elements which are greater than or equal to the given element
     */
    public function tailSet(mixed $value) {
        $index = $this->indexOf($value);
        if ($index === -1) return $this;

        return $this->partition($index);
    }

    /**
     * Reverses a given set, where it will continue to be sorted in descending order
     */
    public function reverse() : SortedSet {
        $set = new SortedSet;
        $set->order = self::DESC;
        $set->hashtable = $this->hashtable;
        $set->sort();
        return $set;
    }

    private function sort() : void {
        uasort($this->hashtable, function ($a, $b) {
            $lh = $this->order ? $a : $b;
            $rh = $this->order ? $b : $a;

            if ($a instanceof Comparable)
                return $lh->compare($rh);
            else if (is_string($a))
                return strcasecmp($lh, $rh);
            else if (is_numeric($a)) {
                if (function_exists('gmp_cmp'))
                    return gmp_cmp($lh, $rh);
                else return $lh <=> $rh;
            }
            else if (is_bool($a))
                return $lh === $rh ? 0 : 1;
            else return 0;
        });
    }

}