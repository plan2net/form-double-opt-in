<?php

declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class FormDoubleOptIn extends AbstractEntity
{
    protected string $email = '';

    protected int $mailingDate = 0;

    protected bool $confirmed = false;

    protected string $confirmationHash = '';

    protected int $confirmationDateTimestamp = 0;

    /**
     * The original form values as json string
     */
    protected string $formValues = '';

    /**
     * The original confirmation receiver information as json string
     */
    protected string $receiverInformation = '';

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        // Results in a 32 character string
        $this->confirmationHash = bin2hex(random_bytes(16));
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getMailingDate(): int
    {
        return $this->mailingDate;
    }

    public function setMailingDate(int $mailingDate): self
    {
        $this->mailingDate = $mailingDate;

        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): self
    {
        $this->confirmed = $confirmed;

        return $this;
    }

    public function getConfirmationDate(): int
    {
        return $this->confirmationDateTimestamp;
    }

    public function setConfirmationDate(int $confirmationDate): self
    {
        $this->confirmationDateTimestamp = $confirmationDate;

        return $this;
    }

    public function getConfirmationHash(): string
    {
        return $this->confirmationHash;
    }

    public function getFormValues(): string
    {
        return $this->formValues;
    }

    public function setFormValues(string $formValues): void
    {
        $this->formValues = $formValues;
    }

    public function getReceiverInformation(): string
    {
        return $this->receiverInformation;
    }

    public function setReceiverInformation(string $receiverInformation): void
    {
        $this->receiverInformation = $receiverInformation;
    }

    /**
     * @throws \JsonException
     */
    public function getFormValuesAsArray(): array
    {
        return json_decode($this->formValues, true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    /**
     * @throws \JsonException
     */
    public function setFormValuesAs(array $values): self
    {
        $this->formValues = json_encode($values, JSON_THROW_ON_ERROR);

        return $this;
    }

    /**
     * @throws \JsonException
     */
    public function getReceiverInformationAsArray(): array
    {
        return json_decode($this->receiverInformation, true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    /**
     * @throws \JsonException
     */
    public function setReceiverInformationAs(array $values): self
    {
        $this->receiverInformation = json_encode($values, JSON_THROW_ON_ERROR);

        return $this;
    }
}
