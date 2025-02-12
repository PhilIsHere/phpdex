<?php

namespace App\Entity;

use App\Repository\PokemonRepository;
use App\Repository\PokemonTypesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PokemonTypesRepository::class)]
class PokemonTypes {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToMany(targetEntity: Pokemon::class, mappedBy: 'pokemonType')]
    private Collection $pokemon;

    #[ORM\Column(length: 255)]
    private string $typeName;

    public function __construct() {
        $this->pokemon = new ArrayCollection();
    }

    /**
     * @return Collection<int, Pokemon>
     */
    public function getPokemon(): Collection {
        return $this->pokemon;
    }

    /**
     * @param Pokemon $pokemon
     * @return $this
     */
    public function addPokemon(Pokemon $pokemon): self {
        if (!$this->pokemon->contains($pokemon)) {
            $this->pokemon->add($pokemon);
            $pokemon->addPokemonType($this);
        }

        return $this;
    }

    /**
     * @param Pokemon $pokemon
     * @return $this
     */
    public function removePokemon(Pokemon $pokemon): self {
        if ($this->pokemon->removeElement($pokemon)) {
            $pokemon->removePokemonType($this);
        }

        return $this;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getTypeName(): string {
        return $this->typeName;
    }

    /**
     * @param string $typeName
     */
    public function setTypeName(string $typeName): void {
        $this->typeName = $typeName;
    }

}
