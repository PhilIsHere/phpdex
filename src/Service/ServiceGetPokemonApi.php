<?php

namespace App\Service;

use App\Entity\Pokemon;
use App\Entity\PokemonTypes;
use App\Factory\PokemonFactory;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;


class ServiceGetPokemonApi implements IApiCall {
    const PKMN_URL = "https://pokeapi.co/api/v2/";
    const PKMN_SPECIES_URL = "https://pokeapi.co/api/v2/pokemon-species/"; //For German Names
    const CACHE_TIME = 28800; // 8 hours


    /**
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $em
     */
    public function __construct(private readonly LoggerInterface $logger, private readonly EntityManagerInterface $em,
                                private readonly IUploaderClass  $uploader) {
    }

    /**
     * @param int $pkmnId The Pokedex Number of the Pokemon >0 && <=905
     * @return Pokemon|null
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws InvalidArgumentException
     */
    public function getPokemon(int $pkmnId): ?Pokemon {
        $apiPokemon = [];
        try {
            $cache = new FilesystemAdapter();
            $apiPokemon = $cache->get('pkmnObject', function (ItemInterface $item) use ($pkmnId) {
                $httpClient = HttpClient::create();
                $item->expiresAfter(self::CACHE_TIME);
                return $httpClient->request('GET', self::PKMN_URL . 'pokemon/' . $pkmnId);
            });
            $apiSpecies = $cache->get('pkmnNames', function (ItemInterface $item) use ($pkmnId) {
                $httpClient = HttpClient::create();
                $item->expiresAfter(self::CACHE_TIME);
                return $httpClient->request('GET', self::PKMN_SPECIES_URL . $pkmnId);
            });
            $apiPokemon = $apiPokemon->toArray();
            $apiSpecies = $apiSpecies->toArray();
        } catch (Exception $exception) {
            $this->logger->critical('Exception in file: ' . __FILE__);
            $this->logger->critical($exception->getMessage());
        }
        return PokemonFactory::createPokemonByApi($apiPokemon, $apiSpecies, $this->em, $this->uploader);
    }

    /**
     * @param int $id
     * @return PokemonTypes
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getPokemonType(int $id): PokemonTypes {
        $httpClient = HttpClient::create();
        $response = $httpClient->request('GET', self::PKMN_URL . 'type/' . $id)->toArray();
        $pokemonType = new PokemonTypes();
        $pokemonType->setTypeName(ucfirst($response['name']));
        return $pokemonType;
    }
}