<?php

declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Event;

final readonly class AfterDoubleOptInConfirmation
{
    public function __construct(
        private array $formValues,
        private array $confirmationReceiverInformation
    ) {
    }

    public static function with(array $formValues, array $confirmationReceiverInformation): self
    {
        return new self(
            $formValues,
            $confirmationReceiverInformation
        );
    }

    public function getFormValues(): array
    {
        return $this->formValues;
    }

    public function getConfirmationReceiverInformation(): array
    {
        return $this->confirmationReceiverInformation;
    }
}
