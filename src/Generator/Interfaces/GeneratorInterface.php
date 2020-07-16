<?php

namespace romanzipp\CalendarGenerator\Generator\Interfaces;

interface GeneratorInterface
{
    /**
     * @return \romanzipp\CalendarGenerator\Generator\Abstracts\AbstractEvent[]
     */
    public function generateEvents(): array;

    public function getCommandQuestions(): array;

    public function setQuestionResponse(string $name, $choice): void;

    public function getQuestionResponse(string $name);
}
