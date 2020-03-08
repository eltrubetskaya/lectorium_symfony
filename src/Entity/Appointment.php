<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AppointmentRepository")
 */
class Appointment
{
    public const STATUS_CREATED = 'created';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @return array
     */
    public function getStatuses (): array
    {
        return [
            self::STATUS_CREATED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $transactionId;

    /**
     * @var bool
     *
     * @ORM\Column(type="integer")
     */
    private $refunded;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="appointments")
     */
    private $user;

    /**
     * @var Schedule
     * @ORM\ManyToOne(targetEntity="Schedule", inversedBy="appointments")
     */
    private $schedule;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->setStatus(self::STATUS_CREATED);
        $this->setRefunded(false);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Appointment
     */
    public function setStatus(string $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Appointment
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Schedule
     */
    public function getSchedule(): Schedule
    {
        return $this->schedule;
    }

    /**
     * @param Schedule $value
     * @return $this
     */
    public function setSchedule(Schedule $value)
    {
        $this->schedule = $value;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     */
    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return bool
     */
    public function isRefunded(): bool
    {
        return $this->refunded;
    }

    /**
     * @param bool $refunded
     */
    public function setRefunded(bool $refunded): void
    {
        $this->refunded = $refunded;
    }
}
