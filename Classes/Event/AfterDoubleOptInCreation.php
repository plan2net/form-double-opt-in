<?php

declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Event;

use Plan2net\FormDoubleOptIn\Domain\Model\FormDoubleOptIn;

class AfterDoubleOptInCreation
{
    public function __construct(
        readonly FormDoubleOptIn $formDoubleOptIn
    ) {
    }

    public static function with(FormDoubleOptIn $formDoubleOptIn): self
    {
        return new self(
            $formDoubleOptIn
        );
    }


}
