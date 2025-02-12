<?php

namespace App\Command;

use App\Entity\Pokemon;
use App\Entity\PokemonTypes;
use App\Service\IApiCall;
use App\Service\IUploaderClass;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pokemon:fix:all',
    description: 'Fixes all Pokemon via the PokeAPI',
)]
class PokemonFixAllCommand extends Command {

    /**
     * @param IApiCall $apiInterface
     * @param EntityManagerInterface $em
     */
    public function __construct(private readonly IApiCall               $apiInterface,
                                private readonly EntityManagerInterface $em,
                                private readonly IUploaderClass         $uploader) {
        parent::__construct('pokemon:fix:all');
    }

    public function compareTypes(PokemonTypes $a, PokemonTypes $b): int {
        if ($a === $b) {
            return 0;
        }
        return ($a > $b) ? 1 : -1;

    }

    protected function configure(): void {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int { //Todo: Do all fix
        $io = new SymfonyStyle($input, $output);
        /** @var Pokemon $localPkmnRepository */
        $localPkmnRepository = $this->em->getRepository(Pokemon::class)->findAll();
        $localTypeRepository = $this->em->getRepository(PokemonTypes::class);

        /** @var Pokemon $localPokemon */
        foreach ($localPkmnRepository as $key => $localPokemon) {
            $remotePokemon = $this->apiInterface->getPokemon($localPokemon->getPokedexId());
            $remoteType = $remotePokemon->getPokemonType();
            $localType = $localPokemon->getPokemonType();
            $typeDiff = array_udiff($remoteType->toArray(), $localType->toArray(), array($this, 'compareTypes'));

            if ($localPokemon->getChangedByAdmin()) {
                $io->warning('Pokemon ' . $localPokemon->getName() . ' was changed by an Admin. Skipping...');
                continue;
            }
            if ($typeDiff) {
                $io->info($localPokemon->getName() . ' hat keine oder falsche Typen!');
                sleep(2);
                foreach ($remoteType as $value => $item) {
                    $remoteTypeName = $item->getTypeName();
                    $localTypeName = $localTypeRepository->findOneBy(array('typeName' => $remoteTypeName));
                    $localPokemon->addPokemonType($localTypeName);
                    $io->info('Für ' . $localPokemon->getName() . ' wurde der Typ: ' . $remoteTypeName . ' gefunden!');
                }
                sleep(1);
                $localPokemon->setHeight($remotePokemon->getHeight());
                $localPokemon->setPokedexId($remotePokemon->getPokedexId());
                $localPokemon->setImage($remotePokemon->getImage());
                $this->em->flush();
                $io->success('Das Pokemon ' . $localPokemon->getName() . ' wurde korrigiert!');
                sleep(3);
            }
        }
        $io->success('Alle Vorgänge erfolgreich abgeschlossen!');
        return Command::SUCCESS;
    }
}
