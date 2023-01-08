<?php

namespace FT\Sets;

final class Utils {

    public static function flatten(array $array, int $depth = 1) {
        $flattened = [];
        $should_flatten = $depth === -1 || $depth-- > 0;

        foreach ($array as $value) {
            if ($should_flatten && is_array($value)) {
                $values = static::flatten(array_values($value), $depth);
                array_push($flattened, ...$values);
            }
            else $flattened[] = $value;
        }

        return $flattened;
    }

}

?>