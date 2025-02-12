<?php

namespace App\Command;

use App\Entity\PokemonTypes;
use App\Repository\PokemonTypesRepository;
use App\Service\IApiCall;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\Exception\ClientException;

#[AsCommand(
    name: 'pokemon:import-types',
    description: 'Imports all Pokemon-Types to local Database with PokeAPI',
)]
class PokemonImportTypesCommand extends Command {
    /**
     * @param IApiCall $apiInterface
     * @param EntityManagerInterface $em
     */
    public function __construct(private readonly IApiCall $apiInterface, private readonly EntityManagerInterface $em) {
        parent::__construct('pokemon:import-types');
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        /** @var PokemonTypesRepository $typesRepository */
        $typesRepository = $this->em->getRepository(PokemonTypes::class);
        for ($i = 0; $i < 1000; $i++) {
            try {
                $type = $this->apiInterface->getPokemonType($i + 1);
                $typesRepository->add($type, true);
            } catch (ClientException) {
                break;
            } catch (Exception $exception) {
                $io->error($exception->getMessage());
                return Command::FAILURE;
            }
            $io->success('Der Typ ' . $type->getTypeName() . ' wurde erfolgreich importiert');
            sleep(2);
        }
        $io->success('All Types have been imported');
        return Command::SUCCESS;
    }
}
