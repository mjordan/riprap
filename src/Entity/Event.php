<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 */
class Event
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
    private $event_uuid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $event_type;

    /**
     * @ORM\Column(type="text")
     */
    private $resource_id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $datestamp;

    /**
     * @ORM\Column(type="text")
     */
    private $hash_algorithm;

    /**
     * @ORM\Column(type="text")
     */
    private $hash_value;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $event_outcome;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventUuid(): ?string
    {
        return $this->event_uuid;
    }

    public function setEventUuid(string $event_uuid): self
    {
        $this->event_uuid = $event_uuid;

        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->event_type;
    }

    public function setEventType(string $event_type): self
    {
        $this->event_type = $event_type;

        return $this;
    }

    public function getResourceId(): ?string
    {
        return $this->resource_id;
    }

    public function setResourceId(string $resource_id): self
    {
        $this->resource_id = $resource_id;

        return $this;
    }

    public function getDatestamp(): ?\DateTimeInterface
    {
        return $this->datestamp;
    }

    public function setDatestamp(\DateTimeInterface $datestamp): self
    {
        $this->datestamp = $datestamp;

        return $this;
    }

    public function getHashAlgorithm(): ?string
    {
        return $this->hash_algorithm;
    }

    public function setHashAlgorithm(string $hash_algorithm): self
    {
        $this->hash_algorithm = $hash_algorithm;

        return $this;
    }

    public function getHashValue(): ?string
    {
        return $this->hash_value;
    }

    public function setHashValue(string $hash_value): self
    {
        $this->hash_value = $hash_value;

        return $this;
    }

    public function getEventOutcome(): ?string
    {
        return $this->event_outcome;
    }

    public function setEventOutcome(string $event_outcome): self
    {
        $this->event_outcome = $event_outcome;

        return $this;
    }
}
