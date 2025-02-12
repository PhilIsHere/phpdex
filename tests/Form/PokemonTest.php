<?php

namespace App\Tests\Form;

use App\Entity\Pokemon;
use App\Entity\PokemonTypes;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PokemonTest extends WebTestCase {

    /**
     * @return void
     */
    public function testAdminBlock(): void {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        $userRepo = $em->getRepository(User::class);
        $pokeRepo = $em->getRepository(Pokemon::class);
        $noAdmin = $userRepo->findOneByEmail('hallo@hallo.de');
        $client->loginUser($noAdmin);
        $crawler = $client->request('GET', '/pokemon');
        $form = $crawler->filter('form[name="pokemon_form"]')->form([
            'pokemon_form[pokedexId]' => 117,
            'pokemon_form[name]' => 'Test',
            'pokemon_form[height]' => 70,
            'pokemon_form[pokemonType]' => ['25'],
            'pokemon_form[imageFile]' => null
        ], 'POST');
        $client->submit($form);
        $client->followRedirect();
//        $this->assertResponseRedirects('/pokemon?pokemonid=117', 301);
        $this->assertResponseStatusCodeSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @return void
     */
    public function testAdminEdit(): void {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        $userRepo = $em->getRepository(User::class);
        $pokeRepo = $em->getRepository(Pokemon::class);
        $admin = $userRepo->findOneByEmail('phil@elbformat.de');
        $client->loginUser($admin);
        $crawler = $client->request('GET', '/pokemon');
        $form = $crawler->filter('form[name="pokemon_form"]')->form([
            'pokemon_form[pokedexId]' => 117,
            'pokemon_form[name]' => 'Test',
            'pokemon_form[height]' => 70,
            'pokemon_form[pokemonType]' => ['25'],
            'pokemon_form[agreeTerms]' => true,
            'pokemon_form[imageFile]' => null
        ], 'POST');
        $client->submit($form);
        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200, $client->getResponse()->getStatusCode());
    }
}