<?php

namespace App\Controller;

use App\Entity\Pokemon;
use App\Entity\PokemonTypes;
use App\Form\PokemonAdminType;
use App\Form\PokemonFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController {
    #[Route('/admin', name: 'app_admin')]
    public function index(Request $request): Response {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/pokemon', name: 'app_admin_pokemon')]
    public function adminPokemon(Request $request, ManagerRegistry $doctrine): Response {
        $pkmnDb = $doctrine->getRepository(Pokemon::class);
        $typeDb = $doctrine->getRepository(PokemonTypes::class);
        $form = $this->createForm(PokemonFormType::class);
        $form->handleRequest($request);
        return $this->render('admin/pokemon.html.twig', [
            'allPkmn' => $pkmnDb->findAll(),
            'allTypes' => $typeDb->findAll(),
            'controller_name' => 'PokemonEditor',
        ]);
    }

    /**
     * @return void
     */
    #[Route('/admin/pokemon/{pokedexId}', name: 'app_admin_edit_pokemon')]
    public function editPokemon(int $pokedexId, Request $request, ManagerRegistry $doctrine): Response {
        if ($request->isMethod('POST')) {
            $pkmnDb = $doctrine->getRepository(Pokemon::class);
            $pkmn = $pkmnDb->findOneBy(['pokedexId' => $pokedexId]);
            $form = $this->createForm(PokemonFormType::class, $pkmn);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $doctrine->getManager();
                $pkmn->setChangedByAdmin(true);
                $em->persist($pkmn);
                $em->flush();
                $this->addFlash('success', $form->getData()->getName() . ' wurde erfolgreich bearbeitet. Die Sperre für User und API ist aktiv.');
                return $this->redirectToRoute('app_admin_pokemon');
            }
        }
        $pkmnDb = $doctrine->getRepository(Pokemon::class)->findOneBy(['pokedexId' => $pokedexId]);
        $typeDb = $doctrine->getRepository(PokemonTypes::class);
        $form = $this->createForm(PokemonFormType::class);
        return $this->render('admin/edit_pokemon.html.twig', [
            'pokedexId' => $pokedexId,
            'pokemon' => $pkmnDb,
            'allTypes' => $typeDb,
            'controller_name' => 'PokemonEditor',
            'pokemonForm' => $form->createView(),
        ]);
    }
}
