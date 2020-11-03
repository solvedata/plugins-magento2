<?php

declare(strict_types=1);

namespace SolveData\Events\Console\Command;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Data\Collection\AbstractDb;
use SolveData\Events\Model\EventRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ImportAbstract extends Command
{
    const PAGE_SIZE = 1000;

    const OPTION_FROM = 'from';

    const OPTION_TO = 'to';

    protected $collectionFactory;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @param EventRepository $eventRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param string|null $name
     */
    public function __construct(
        EventRepository $eventRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        string $name = null
    ) {
        $this->eventRepository = $eventRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;

        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->addOption(
            self::OPTION_FROM,
            null,
            InputOption::VALUE_OPTIONAL,
            'From'
        );
        $this->addOption(
            self::OPTION_TO,
            null,
            InputOption::VALUE_OPTIONAL,
            'To'
        );

        parent::configure();
    }

    /**
     * Place items to solve_event table
     *
     * @param array $items
     *
     * @return int The number of affected rows
     */
    abstract protected function placeItems(array $items): int;


    /**
     * Prepare and return search criteria
     *
     * @param InputInterface $input
     *
     * @return AbstractDb
     *
     * @throws \Exception
     */
    protected function getCollection(InputInterface $input): AbstractDb
    {
        if (empty($this->collectionFactory)) {
            throw new \Exception('CollectionFactory is empty');
        }
        /** @var AbstractDb $collection */
        $collection = $this->collectionFactory->create();
        $from = $input->getOption(self::OPTION_FROM);
        $select = $collection->getSelect();
        if (!empty($from) && is_numeric($from)) {
            $select->where('entity_id >= ?', (int)$from);
        }
        $to = $input->getOption(self::OPTION_TO);
        if (!empty($to) && is_numeric($to)) {
            $select->where('entity_id <= ?', (int)$to);
        }
        $collection->setOrder('entity_id', SortOrder::SORT_DESC);
        $collection->setPageSize(self::PAGE_SIZE);

        return $collection;
    }

    /**
     * Import collection to solve_event table
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int The number of affected rows
     *
     * @throws \Exception
     */
    protected function import(InputInterface $input, OutputInterface $output)
    {
        $collection = $this->getCollection($input);
        $pages = $collection->getLastPageNumber();
        $progressBar = new ProgressBar($output, (int)$pages);
        $progressBar->start();
        $progressBar->setMessage('Importing...');

        $page = 1;
        $affectedRows = 0;
        while ($page <= $pages) {
            $collection->setCurPage($page++);
            $collection->clear();
            $affectedRows += $this->placeItems($collection->getItems());
            $progressBar->advance();
        }
        $progressBar->setMessage('Done!');
        $progressBar->finish();
        $output->writeln('');

        return $affectedRows;
    }
}
