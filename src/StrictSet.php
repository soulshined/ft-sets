<?php

namespace FT\Sets;

use InvalidArgumentException;

/**
 * This class ensures the Set container holds exactly the same data types for all values
 *
 * The first element added to the Set dictates the data type permitted for subsequent elements
 */
class StrictSet extends Set {
    private $managed_type = null;

    /**
     * @param mixed $a Element to add to Set
     *
     * @throws InvalidArgumentException when an element added is not of the same data type as the first element
     */
    public function add(mixed $a) : bool
    {
        if ($this->managed_type === null)
            $this->managed_type = $this->get_type($a);

        else if ($this->managed_type !== $this->get_type($a))
            throw new InvalidArgumentException($this::class . " managed type " . $this->managed_type . " does not expect " . $this->get_type($a));

        return parent::add($a);
    }

    private function get_type($value): string
    {
        $this_type = gettype($value);
        if ($this_type === 'object')
            return get_class($value);

        return $this_type;
    }

    protected function is_managed_type(mixed $value) : bool {
        return $this->get_type($value) === $this->managed_type;
    }
}

?>