<?php

namespace App\Command;

use App\Repository\CommentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommentCleanupCommand extends Command
{
    protected static $defaultName = 'app:comment:cleanup';

    /** @var CommentRepository */
    private CommentRepository $repository;

    /**
     * StepInfoCommand constructor.
     * @param CommentRepository $repository
     */
    public function __construct(CommentRepository $repository)
    {
        $this->repository = $repository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Deletes rejected and spam comments from the database')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if($input->getOption('dry-run')) {
            $io->note('Dry mode enabled');

            $count = $this->repository->countOldRejected();
        } else {
            $count = $this->repository->deleteOldRejected();
        }

        $io->success(sprintf('Deleted %d old rejected/spam comments', $count));

        return 0;
    }
}