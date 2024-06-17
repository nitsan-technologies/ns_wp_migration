<?php

namespace NITSAN\NsWpMigration\Command;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use NITSAN\NsWpMigration\Controller\PostController;
use Symfony\Component\Console\Input\InputInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;

class ImportCsvCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp('Import CSV Data')->addArgument(
            'beUserId',
            InputArgument::REQUIRED,
            'Add BE User ID',
        )->addArgument(
            'filepath',
            InputArgument::REQUIRED,
            'Path of files')
        ->addArgument(
            'storage',
            InputArgument::REQUIRED,
            'Storage Page of Uid'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output,): int
    {
        $error = 0;
        $beUser = (int)$input->getArgument('beUserId');
        $backendUserRepository = GeneralUtility::makeInstance(BackendUserRepository::class);
        $argumentsMessage = 'nswpmigration:import [beUserId] [filepath] [storageId]';
        if (empty($backendUserRepository->findByUid($beUser))){
            $output->writeln("Error : Backend User not found : ". $argumentsMessage);
            $error = 1;
        }

        $filepath = $input->getArgument('filepath');
        if (!file_exists($filepath)) {
            $output->writeln("Error : Profile Valid File Path : ". $argumentsMessage);
            $error = 1;
        }

        $pid = $input->getArgument('storage');
        $pageRepository = GeneralUtility::makeInstance((PageRepository::class));
        if (empty($pageRepository->getPage((int)$pid))) {
            $output->writeln("Error : Provide Valid Storage Page ID : s". $argumentsMessage);
            $error = 1;
        }

        if ($error == 0) {
            $data = [];
            if (!empty($filepath)) {
                $handle = fopen($filepath, 'r');
                $columns = fgetcsv($handle, 10000, ",");
                $record = 1;
                
                while (($row = fgetcsv($handle, 10000, ",")) !== false) {
                    $data[$record] = array_combine($columns, $row);
                    $record++;
                }
            }
            
            try {
                GeneralUtility::makeInstance(PostController::class)->createPagesAndBlog($data, (int)$pid, 'page', $beUser);
                $output->writeln("Record Imported successfully");
                return Command::SUCCESS;
            } catch (\Exception $th) {
                $output->writeln($th->getMessage());
                return Command::FAILURE;
            }

        } else {
            return Command::FAILURE;
        }
    }
}