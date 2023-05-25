<?php

declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Event;

use Plan2net\FormDoubleOptIn\Domain\Model\FormDoubleOptIn;

final class AfterOptInCreationEvent
{
    private FormDoubleOptIn $doubleOptIn;

    public function __construct(FormDoubleOptIn $doubleOptIn)
    {
        $this->doubleOptIn = $doubleOptIn;
    }

    public function getDoubleOptIn(): FormDoubleOptIn
    {
        return $this->doubleOptIn;
    }
}
