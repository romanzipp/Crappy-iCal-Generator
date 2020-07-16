<?php

namespace romanzipp\CalendarGenerator\Generator\Abstracts;

use romanzipp\CalendarGenerator\Generator\Interfaces\GeneratorInterface;

abstract class AbstractGenerator implements GeneratorInterface
{
    protected array $questionResponses = [];

    public function getCommandQuestions(): array
    {
        return [];
    }

    final public function setQuestionResponse(string $name, $choice): void
    {
        $this->questionResponses[$name] = $choice;
    }

    final public function getQuestionResponse(string $name)
    {
        return $this->questionResponses[$name] ?? null;
    }
}
