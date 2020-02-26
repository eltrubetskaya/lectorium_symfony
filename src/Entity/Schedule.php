<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ScheduleRepository")
 */
class Schedule
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $day;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="time")
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="time")
     */
    private $endTime;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @var Doctor
     * @ORM\ManyToOne(targetEntity="Doctor", inversedBy="schedule")
     */
    private $doctor;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Appointment", mappedBy="schedule")
     *
     */
    private $appointments;

    public function __construct()
    {
        $this->enabled = true;
        $this->appointments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDay(): ?string
    {
        return $this->day;
    }

    public function setDay(string $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return Doctor
     */
    public function getDoctor(): Doctor
    {
        return $this->doctor;
    }

    /**
     * @param Doctor $doctor
     */
    public function setDoctor(Doctor $doctor): void
    {
        $this->doctor = $doctor;
    }

    /**
     * @return \DateTime
     */
    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }

    /**
     * @param \DateTime $value
     * @return $this
     */
    public function setStartTime(\DateTime $value)
    {
        $this->startTime = $value;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndTime(): \DateTime
    {
        return $this->endTime;
    }

    /**
     * @param \DateTime $value
     * @return $this
     */
    public function setEndTime(\DateTime $value)
    {
        $this->endTime = $value;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getAppointments(): ArrayCollection
    {
        return $this->appointments;
    }

    /**
     * @param Appointment $appointment
     *
     * @return $this
     */
    public function addAppointment(Appointment $appointment)
    {
        $appointment->setSchedule($this);
        $this->appointments->add($appointment);

        return $this;
    }

    /**
     * @param Appointment $appointment
     */
    public function removeAppointment(Appointment $appointment)
    {
        $this->appointments->removeElement($appointment);
    }
}
