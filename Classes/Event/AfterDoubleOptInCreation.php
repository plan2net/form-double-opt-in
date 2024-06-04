<?php

declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Event;

final class AfterDoubleOptInCreation
{
    public function __construct(
        private array $formValues
    ) {
    }

    public static function with(array $formValues): self
    {
        return new self(
            $formValues
        );
    }

    public function getFormValues(): array
    {
        return $this->formValues;
    }

    public function setFormValues(array $formValues): void
    {
        $this->formValues = $formValues;
    }
}
