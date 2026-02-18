<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DailyEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DailyEntryRepository::class)]
#[ORM\Table(name: 'daily_entry')]
#[ORM\UniqueConstraint(name: 'uniq_user_date', columns: ['user_id', 'date'])]
#[ORM\Index(columns: ['user_id', 'date'], name: 'idx_user_date')]
class DailyEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $intention = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $completed = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $skipped = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date instanceof \DateTimeImmutable
            ? $date
            : \DateTimeImmutable::createFromMutable($date);

        return $this;
    }

    public function getIntention(): ?string
    {
        return $this->intention;
    }

    public function setIntention(string $intention): static
    {
        $this->intention = $intention;

        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(?bool $completed): static
    {
        $this->completed = $completed;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    public function setSkipped(bool $skipped): static
    {
        $this->skipped = $skipped;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
