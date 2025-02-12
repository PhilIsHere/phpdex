<?php


namespace App\Controller;

use App\Entity\Pokemon;
use App\Form\PokemonFormType;
use App\Repository\PokemonTypesRepository;
use App\Service\IApiCall;
use App\Service\IUploaderClass;
use App\Service\ServiceUploader;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function Composer\Autoload\includeFile;


class PokemonController extends AbstractController {
    private RequestStack $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack, private Security $security) {
        $this->requestStack = $requestStack;
    }

    /*
     * Auf der GET Route dürfen nur angezeigte Elemente genutzt werden, keine speichernde elemente.
     */
    /**
     * @param Request $request
     * @param ManagerRegistry $doctrine
     * @return Response
     * Zeigt alle bisher angelegten Pokemon an.
     */
    #[Route('/pokemon', name: 'get_pokemon', methods: 'GET')]
    public function pkmnname(Request $request, ManagerRegistry $doctrine, IApiCall $getPokemonApi): Response {
        if ($request->query->has('pokemonid')) {
            $createdPkmn = $doctrine->getRepository(Pokemon::class)->findBy(['pokedexId' => $request->query->get('pokemonid')]);
            return $this->render('pokemon/confirm.html.twig', [
                'pokedexId' => $createdPkmn[0]->getPokedexId(),
                'name' => $createdPkmn[0]->getName(),
                'height' => $createdPkmn[0]->getHeight(),
                'type' => $createdPkmn[0]->getPokemonType()->toArray(),
                'image' => $createdPkmn[0]->getImage(),
            ]);
        }
        $session = $this->requestStack->getSession();
        $pkmnDb = $doctrine->getRepository(Pokemon::class);
        $form = $this->createForm(PokemonFormType::class);
        $form->handleRequest($request);
        try {
            if ($session->get('pokedexId')) {
                $session->remove('pokedexId');
            }

            if (!$pkmnDb) {
                $this->addFlash('error', 'Die Datenbank ist leer.');
            }
        } catch (Exception $exception) {
            $this->addFlash('error', $exception->getMessage());
        } finally {
            return $this->render('pokemon/pokemon_get_and_create.html.twig', [
                'allPkmn' => $pkmnDb->findAll(),
                'controller_name' => 'PokemonController',
                'pokemonForm' => $form->createView(),
            ]);
        }
    }

    /**
     * @param Request $request
     * @param ManagerRegistry $doctrine
     * @return Response
     * Erstellt eine Eingabemaske wo der User ein Pokemon anlegen kann.
     * Es wird in der Datenbank gespeichert und der Name des zuletzt angelegten Pokemon wird in der Session vermerkt.
     */
    //Eine POST Route hat nie ein eigenes Template, sondern ein redirect!
    #[Route('/pokemon', name: 'post_pokemon', methods: 'POST')]
    public function index(Request  $request, ManagerRegistry $doctrine, IUploaderClass $uploader,
                          IApiCall $remotePkmn): Response {
        $isAdmin = in_array("ROLE_ADMIN", $this->security->getUser()->getRoles());
        $listOfPokemon = $doctrine->getRepository(Pokemon::class)->findAll();

        $form = $this->createForm(PokemonFormType::class, new Pokemon());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();
            $pokemonForm = $form->getData();
            $pkmnManager = $doctrine->getManager();
            $localPokemon = $doctrine->getRepository(Pokemon::class)->findOneBy(['pokedexId' => $pokemonForm->getPokedexId()]);

            if ($localPokemon && ($localPokemon->getChangedByAdmin() && !$isAdmin)) {
                $this->addFlash('errors', 'Du hast keine Berechtigung, dieses Pokemon zu bearbeiten!');
                return $this->redirectToRoute('get_pokemon', status: 301);
            } else if ($localPokemon) {
                $localPokemon->removeAllPokemonTypes($localPokemon);
                $typeName = $form->get('pokemonType')->getData();
                foreach ($typeName as $type) {
                    $localPokemon->addPokemonType($type);
                }
                $localPokemon->setChangedByAdmin($isAdmin);
                $localPokemon->setName($pokemonForm->getName());
                $localPokemon->setHeight($pokemonForm->getHeight());
                if ($uploadedFile) {
                    $newFilename = $uploader->upload($uploadedFile);
                    $newFilename = str_replace('images/pokemon/', '', $newFilename);
                    $localPokemon->setImage($newFilename);
                } else {
                    $newFilename = $uploader->uploadUrl($remotePkmn->getPokemon($pokemonForm->getPokedexId())->getImage(), $pokemonForm->getName());
                    $newFilename = str_replace('images/pokemon/', '', $newFilename);
                    $localPokemon->setImage($newFilename);
                }
                $pkmnManager->persist($localPokemon);
                $pkmnManager->flush();
                $this->addFlash('success', $pokemonForm->getName() . ' wurde erfolgreich bearbeitet!');
                return $this->redirect('/pokemon?pokemonid=' . (int)$form->getData()->toArray()['pokedexId']);
            }
            /** @var Pokemon $pokemonForm */
            $typeName = $form->get('pokemonType')->getData();
            foreach ($typeName as $type) {
                $pokemonForm->addPokemonType($type);
            }
            $pokemonForm->setChangedByAdmin($isAdmin);
            if ($uploadedFile) {
                $newFilename = $uploader->upload($uploadedFile);
                $newFilename = str_replace('images/pokemon/', '', $newFilename);
                $pokemonForm->setImage($newFilename);
            } else {
                $newFilename = $uploader->uploadUrl($remotePkmn->getPokemon($pokemonForm->getPokedexId())->getImage(), $pokemonForm->getName());
                $newFilename = str_replace('images/pokemon/', '', $newFilename);
                $pokemonForm->setImage($newFilename);
            }
            $pkmnManager->persist($pokemonForm);
            $pkmnManager->flush();
            $this->addFlash('success', $pokemonForm->getName() . ' wurde erfolgreich angelegt!');
            return $this->redirect('/pokemon?pokemonid=' . (int)$form->getData()->toArray()['pokedexId']);

        }
        return $this->render('pokemon/pokemon_get_and_create.html.twig', [
            'allPkmn' => $listOfPokemon,
            'controller_name' => 'PokemonController',
            'pokemonForm' => $form->createView(),
        ]);
    }

}
