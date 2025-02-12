<?php

namespace App\Service;

use App\Entity\Pokemon;
use App\Entity\PokemonTypes;

interface IApiCall {
    /**
     * @param int $pkmnId
     * @return Pokemon|null
     *
     */
    public function getPokemon(int $pkmnId): ?Pokemon;

    /**
     * @param int $id
     * @return PokemonTypes
     */
    public function getPokemonType(int $id): PokemonTypes;
}