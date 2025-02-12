<?php

namespace App\Factory;


use App\Entity\Pokemon;
use App\Entity\PokemonTypes;
use App\Service\IUploaderClass;
use Doctrine\ORM\EntityManagerInterface;

class PokemonFactory {
    private function __construct() {
    }

    /**
     * @param array $pkmnResponse //Contains the Pokemon Object from the API
     * @param array $speciesResponse //Contains the Pokemon Species Object for localized Names
     * @param EntityManagerInterface $em
     * @return Pokemon The response from API. CAUTITION: Height*10 because PokeAPI gives the height in decimeter
     */
    public static function createPokemonByApi(array                  $pkmnResponse, array $speciesResponse,
                                              EntityManagerInterface $em, IUploaderClass $uploader): Pokemon {
        $apiPokemon = new Pokemon();
        $apiPokemon->setName(ucfirst($speciesResponse['names'][5]['name']));
        $apiPokemon->setHeight($pkmnResponse['height'] * 10);
        $apiPokemon->setPokedexId($pkmnResponse['id']);
        $newFilename = $uploader->uploadUrl($pkmnResponse['sprites']['other']['official-artwork']['front_default'], ucfirst($speciesResponse['names'][5]['name']));
        $newFilename = str_replace('public/images/pokemon/', '', $newFilename);
        $apiPokemon->setImage($newFilename);
        $pokemonTypeRepo = $em->getRepository(PokemonTypes::class);
        foreach ($pkmnResponse['types'] as $key => $type) {
            $localType = $pokemonTypeRepo->findOneBy(array('typeName' => ucfirst($pkmnResponse['types'][$key]['type']['name'])));
            $apiPokemon->addPokemonType($localType);
        }
        return $apiPokemon;
    }
}