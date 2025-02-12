<?php

namespace App\Command;

use App\Entity\Pokemon;
use App\Entity\PokemonTypes;
use App\Service\IApiCall;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pokemon:fix:types',
    description: 'Checks the local Database for wrong or missing Pokemontypes and fixes them via the PokeAPI',
)]
class PokemonFixTypesCommand extends Command {
    /**
     * @param IApiCall $apiInterface
     * @param EntityManagerInterface $em
     */
    public function __construct(private readonly IApiCall $apiInterface, private readonly EntityManagerInterface $em) {
        parent::__construct('pokemon:fix-types');
    }

    /**
     * @param PokemonTypes $a
     * @param PokemonTypes $b
     * @return int
     */
    public function compareTypes(PokemonTypes $a, PokemonTypes $b): int {
        if ($a === $b) {
            return 0;
        }
        return ($a > $b) ? 1 : -1;

    }

    /**
     * @return void
     */
    protected function configure(): void {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * Fixes wrong or missing PokemonTypes in the local Database
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        /** @var Pokemon $localPkmnRepository */
        $localPkmnRepository = $this->em->getRepository(Pokemon::class)->findAll();
        $localTypeRepository = $this->em->getRepository(PokemonTypes::class);

        /** @var Pokemon $localPokemon */
        foreach ($localPkmnRepository as $key => $localPokemon) {
            $remotePokemon = $this->apiInterface->getPokemon($localPokemon->getPokedexId());
            $remoteType = $remotePokemon->getPokemonType();
            $localType = $localPokemon->getPokemonType();
            //Checks if the local Pokemon has the same Types and amount of Tyoes as the remote Pokemon
            $typeDiff = array_udiff($remoteType->toArray(), $localType->toArray(), array($this, 'compareTypes')) && !(count($remoteType) == count($localType));

            if ($typeDiff && !$localPokemon->getChangedByAdmin()) {
                $io->info($localPokemon->getName() . ' hat keine oder falsche Typen!');
                $localPokemon->removeAllPokemonTypes($localPokemon);
                sleep(2);
                foreach ($remoteType as $value => $item) {
                    $remoteTypeName = $item->getTypeName();
                    $localTypeName = $localTypeRepository->findOneBy(array('typeName' => $remoteTypeName));
                    $localPokemon->addPokemonType($localTypeName);
                    $io->info('Für ' . $localPokemon->getName() . ' wurde der Typ: ' . $remoteTypeName . ' gefunden!');
                }
                sleep(1);
                $this->em->flush();
                $io->success('Für ' . $localPokemon->getName() . ' wurden neue Typen gefunden und in die Datenbank eingetragen!');
                sleep(3);
            }
        }
        $io->success('Alle Vorgänge erfolgreich abgeschlossen!');
        return Command::SUCCESS;
    }
}
