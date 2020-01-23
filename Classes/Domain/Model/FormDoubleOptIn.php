<?php
declare(strict_types=1);

namespace Plan2net\FormDoubleOptIn\Domain\Model;

use DateTime;
use Exception;
use Plan2net\FormDoubleOptIn\Helper\Encryption;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class FormDoubleOptIn
 *
 * @package Plan2net\FormDoubleOptIn\Domain\Model
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class FormDoubleOptIn extends AbstractEntity
{
    /**
     * @var string
     */
    protected $email;

    /**
     * @var DateTime
     */
    protected $mailingDate;

    /**
     * @var bool
     */
    protected $confirmed = false;

    /**
     * @var string
     */
    protected $confirmationHash;

    /**
     * @var DateTime
     */
    protected $confirmationDate;

    /**
     * The original form values as encrypted json string
     *
     * @var string
     */
    protected $formValues;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if ($this->confirmationHash === null) {
            // Results in a 32 character string
            $this->confirmationHash = bin2hex(random_bytes(16));
        }
        if ($this->mailingDate === null) {
            $this->mailingDate = new DateTime('now');
        }
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return FormDoubleOptIn
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getMailingDate(): DateTime
    {
        return $this->mailingDate;
    }

    /**
     * @param DateTime $mailingDate
     * @return FormDoubleOptIn
     */
    public function setMailingDate(DateTime $mailingDate): self
    {
        $this->mailingDate = $mailingDate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    /**
     * @param bool $confirmed
     * @return FormDoubleOptIn
     */
    public function setConfirmed(bool $confirmed): self
    {
        $this->confirmed = $confirmed;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getConfirmationDate(): DateTime
    {
        return $this->confirmationDate;
    }

    /**
     * @param DateTime $confirmationDate
     * @return FormDoubleOptIn
     */
    public function setConfirmationDate(DateTime $confirmationDate): self
    {
        $this->confirmationDate = $confirmationDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfirmationHash(): string
    {
        return $this->confirmationHash;
    }

    /**
     * @return array
     */
    public function getFormValues(): array
    {
        return json_decode(Encryption::decrypt($this->formValues), true);
    }

    /**
     * @param array $values
     * @return FormDoubleOptIn
     */
    public function setFormValues(array $values): self
    {
        $this->formValues = Encryption::encrypt(json_encode($values));

        return $this;
    }
}