<?php

namespace App\Command;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

#[AsCommand(name: 'app:sync-migrate')]
class SynchronizedMigrationCommand extends Command
{
    private readonly LockInterface $lock;

    public function __construct(LockFactory $lockFactory)
    {
        parent::__construct();
        $this->lock = $lockFactory->createLock('database-migration');
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Doctrine migration with lock')
            ->addOption('conn', null, InputOption::VALUE_REQUIRED, 'The database connection to use for this command.')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command.');
    }

    /**
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();

        $this->lock->acquire(true);

        $arguments = [
            'command' => 'doctrine:migrations:migrate',
        ];
        if ($input->getOption('em')) {
            $arguments['--em'] = $input->getOption('em');
        }
        if ($input->getOption('conn')) {
            $arguments['--conn'] = $input->getOption('conn');
        }
        if ($input->getOption('shard')) {
            $arguments['--shard'] = $input->getOption('shard');
        }
        $arrayInput = new ArrayInput([...$arguments]);
        $arrayInput->setInteractive(false);

        return $application->run($arrayInput, $output);
    }
}
