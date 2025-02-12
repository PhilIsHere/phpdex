<?php

namespace App\Command;

use App\Entity\Pokemon;
use App\Repository\PokemonRepository;
use App\Service\IApiCall;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\Exception\ClientException;

#[AsCommand(
    name: 'pokemon:add-missing',
    description: 'Add a short description for your command',
)]
class PokemonAddMissingCommand extends Command {
    /**
     * @param IApiCall $apiInterface
     * @param EntityManagerInterface $em
     */
    public function __construct(private readonly IApiCall $apiInterface, private readonly EntityManagerInterface $em) {
        parent::__construct('pokemon:add-missing');
    }

    /**
     * @return void
     */
    protected function configure(): void {
        $this
            ->addArgument('pkmnId', InputArgument::OPTIONAL, 'Number of Pokemon')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run without writing to local Database');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * Checks Local Database for Missing Pokemon and adds them automatically, if user gives no Input.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {

        $io = new SymfonyStyle($input, $output);
        /** @var PokemonRepository $localPokemonRepository */
        $localPokemonRepository = $this->em->getRepository(Pokemon::class);

        $pkmnId = $input->getArgument('pkmnId');
        if ($pkmnId === null) {
            $io->caution('Es wurde keine Pokedex-ID angegeben. Das nächste Pokemon wird importiert!');
            $localIdArray = $localPokemonRepository->getAllPokedexIds();
            foreach ($localIdArray as $key => $value) {
                if ($value['pokedexId'] !== $key + 1) {
                    $pkmnId = $key + 1;
                    break;
                }
            }
        }

        /** @var Pokemon $remotePokemon */
        try {
            $remotePokemon = $this->apiInterface->getPokemon($pkmnId);
        } catch (ClientException $clientException) {
            $io->error('Es können keine weiteren Pokemon automatisch importiert werden!');
            $io->error($clientException);
            return Command::FAILURE;
        } catch (Exception $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }
        $compareToDatabase = $this->em->getRepository(Pokemon::class)->findOneBy(array('pokedexId' => $pkmnId));

        if ($compareToDatabase) {
            $io->error('Das Pokemon mit der Pokedex-ID: ' . $pkmnId . ' existiert bereits in der lokalen Datenbank!'
                . PHP_EOL . 'Das Pokemon heißt: ' . $compareToDatabase->getName() . '. Bitte wähle eine andere ID!');
            return Command::FAILURE;
        } else {
            $io->success('Das Pokemon mit der Pokedex-ID: ' . $pkmnId . ' existiert noch nicht in der lokalen Datenbank!'
                . PHP_EOL . 'Das Pokemon heißt: ' . $remotePokemon->getName() . ' und wird nun importiert.');
            sleep(3);
            if ($input->getOption('dry-run')) {
                $io->success('Das Pokemon ' . $remotePokemon->getName() . ' wurde erfolgreich in die Datenbank importiert!' . PHP_EOL .
                    'Die Pokedex-ID des Pokemon lautet: ' . $remotePokemon->getPokedexId() . PHP_EOL .
                    'Das Pokemon ist ' . $remotePokemon->getHeight() . 'cm groß.');
                sleep(3);
                $io->caution('Es wurde nur ein Testlauf durchgeführt. Es wurde nichts in die lokale Datenbank geschrieben!');
                return Command::SUCCESS;
            } elseif ($input->getOption('dry-run') === false) {
                $this->em->persist($remotePokemon);
                $this->em->flush();
                $io->success('Das Pokemon ' . $remotePokemon->getName() . ' wurde erfolgreich in die Datenbank importiert!' . PHP_EOL .
                    'Die Pokedex-ID des Pokemon lautet: ' . $remotePokemon->getPokedexId() . PHP_EOL .
                    'Das Pokemon ist ' . $remotePokemon->getHeight() . 'cm groß.');
                return Command::SUCCESS;
            }
        }

        return Command::SUCCESS;
    }
}
