<?php

namespace romanzipp\MotoGP\Generator;

interface GeneratorInterface
{
    /**
     * @return \romanzipp\MotoGP\Objects\Event[]
     */
    public function generateEvents(): array;
}
