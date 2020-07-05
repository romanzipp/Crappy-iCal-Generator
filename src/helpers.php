<?php

function spaces(string $string, int $length, int $type = STR_PAD_BOTH): string
{
    return str_pad($string, $length, ' ', $type);
}
