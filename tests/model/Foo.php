<?php

namespace FT\Sets\Tests\Model;

use FT\Sets\Comparable;

class Foo implements Comparable {
    public function __construct(public string $bar) { }

    public function compare(Comparable $object): int
    {
        if ($object instanceof Foo)
            return $this->bar <=> $object->bar;

        return 1;
    }
}

?>