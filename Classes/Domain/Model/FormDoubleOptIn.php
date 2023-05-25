<?php

declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class FormDoubleOptIn.
 */
class FormDoubleOptIn extends AbstractEntity
{
    protected string $email;

    protected \DateTime $mailingDate;

    protected bool $confirmed = false;

    protected string $confirmationHash = '';

    protected ?\DateTime $confirmationDate = null;

    /**
     * The original form values as json string.
     *
     * @var string
     *
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $formValues;

    /**
     * The original confirmation receiver information as json string.
     *
     * @var string
     */
    protected $receiverInformation;
    // phpcs:enable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        // Results in a 32 character string
        $this->confirmationHash = bin2hex(random_bytes(16));

        $this->mailingDate = new \DateTime('now');
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

    public function getMailingDate(): \DateTime
    {
        return $this->mailingDate;
    }

    public function setMailingDate(\DateTime $mailingDate): self
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

    public function getConfirmationDate(): ?\DateTime
    {
        return $this->confirmationDate;
    }

    public function setConfirmationDate(?\DateTime $confirmationDate): self
    {
        $this->confirmationDate = $confirmationDate;

        return $this;
    }

    public function getConfirmationHash(): string
    {
        return $this->confirmationHash;
    }

    public function getFormValues(): array
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return json_decode($this->formValues ?? '', true) ?? [];
    }

    public function setFormValues(array $values): self
    {
        $this->formValues = json_encode($values);

        return $this;
    }

    public function getReceiverInformation(): array
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return json_decode($this->receiverInformation ?? '', true) ?? [];
    }

    public function setReceiverInformation(array $values): self
    {
        $this->receiverInformation = json_encode($values);

        return $this;
    }
}
