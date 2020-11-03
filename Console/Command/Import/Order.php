<?php

declare(strict_types=1);

namespace SolveData\Events\Console\Command\Import;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Console\Cli;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use SolveData\Events\Console\Command\ImportAbstract;
use SolveData\Events\Model\EventRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Order extends ImportAbstract
{
    /**
     * @param CollectionFactory $collectionFactory
     * @param EventRepository $eventRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param string|null $name
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        EventRepository $eventRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        string $name = null
    ) {
        $this->collectionFactory = $collectionFactory;

        parent::__construct(
            $eventRepository,
            $searchCriteriaBuilder,
            $sortOrderBuilder,
            $name
        );
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('solve:import:orders');
        $this->setDescription('Import orders to Solve Data.');

        parent::configure();
    }

    /**
     * Place items to solve_event table
     *
     * @param array $items
     *
     * @return int The number of affected rows
     */
    protected function placeItems(array $items): int
    {
        $event = $this->eventRepository->create();

        return $event->placeOrders($items);
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
            $affectedRows = $this->import($input, $output);
            $output->writeln(sprintf(
                '<info>You have added to queue %d order(s) for import to Solve Data.</info>',
                $affectedRows
            ));
        } catch (\Throwable $t) {
            $output->writeln(sprintf('<error>%s</error>', $t->getMessage()));

            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
