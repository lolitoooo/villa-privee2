<?php

namespace App\Entity;

use App\Repository\OptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OptionRepository::class)]
#[ORM\Table(name: '`option`')]
class Option
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'option', targetEntity: ReservationOption::class)]
    private Collection $reservationOptions;

    public function __construct()
    {
        $this->reservationOptions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Collection<int, ReservationOption>
     */
    public function getReservationOptions(): Collection
    {
        return $this->reservationOptions;
    }

    public function addReservationOption(ReservationOption $reservationOption): static
    {
        if (!$this->reservationOptions->contains($reservationOption)) {
            $this->reservationOptions->add($reservationOption);
            $reservationOption->setOption($this);
        }

        return $this;
    }

    public function removeReservationOption(ReservationOption $reservationOption): static
    {
        if ($this->reservationOptions->removeElement($reservationOption)) {
            if ($reservationOption->getOption() === $this) {
                $reservationOption->setOption(null);
            }
        }

        return $this;
    }
}
