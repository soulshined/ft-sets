<?php

namespace FT\Sets;

interface SetPredicate {
    public function __invoke(mixed $element) : bool;
}

?>