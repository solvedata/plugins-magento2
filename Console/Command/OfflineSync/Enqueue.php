<?php

declare(strict_types=1);

namespace SolveData\Events\Console\Command\OfflineSync;

use Magento\Framework\Console\Cli;
use SolveData\Events\Model\Event;
use SolveData\Events\Model\EventRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Enqueue extends Command
{
    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @param EventRepository $eventRepository
     * @param string|null $name
     */
    public function __construct(
        EventRepository $eventRepository,
        string $name = null
    ) {
        $this->eventRepository = $eventRepository;

        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('solve:offlinesync:enqueue');
        $this->setDescription('Enqueue event to send through to Solve Data.');

        $this->addOption(
            'name',
            null,
            InputOption::VALUE_REQUIRED,
            'Name of the event type'
        );
        $this->addOption(
            'payload',
            null,
            InputOption::VALUE_REQUIRED,
            'Payload'
        );
        $this->addOption(
            'entity-id',
            null,
            InputOption::VALUE_REQUIRED,
            'Affected entity ID'
        );
        $this->addOption(
            'increment-id',
            null,
            InputOption::VALUE_REQUIRED,
            'Affected increment ID'
        );
        $this->addOption(
            'store-id',
            null,
            InputOption::VALUE_REQUIRED,
            'Store ID'
        );

        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $event = [
                'name'                  => $input->getOption('name'),
                'status'                => Event::STATUS_NEW,
                'payload'               => $input->getOption('payload'),
                'affected_entity_id'    => (int)$input->getOption('entity-id'),
                'affected_increment_id' => $input->getOption('increment-id'),
                'store_id'              => (int)$input->getOption('store-id'),
            ];

            $eventModel = $this->eventRepository->create();
            $eventModel->createEvents([$event]);

            $output->writeln('<info>Successfully enqueued event</info>');
        } catch (\Throwable $t) {
            $output->writeln(sprintf('<error>Error: %s</error>', $t->getMessage()));
            
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
