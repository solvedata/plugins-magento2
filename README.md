# Solve's Magento Extension

## Development environment

Requires docker & docker-compose.

### Setup

1. Run the install script at the root of the repository
    
    `./install.sh`
2. During the installation project, you will need to enter the next information:
    * Directory to setup the Magento project. This is relative to your current directory, and we recommend placing this directory outside of the git repo. For example `../magento`.

    * The magento version to install. If you do not specify a version, the latest version will be installed. We are currently testing with `2.3.5`.

    * Magento authentication keys. See [Magento's documention](https://devdocs.magento.com/guides/v2.4/install-gde/prereq/connect-auth.html) for how to create/retrive these keys.

### Enabling cron jobs

Run the following command from the Magento project directory (not the repository root)

`./vendor/solvedata/plugins-magento2/docker/tools.sh run_cron`

### Helpful Commands

Run the following command Magento project directory (not the repository root) to print all available commands

`./vendor/solvedata/plugins-magento2/docker/tools.sh`

### Helpful links

- http://solvedata.local - Main Site

### Running unit tests

**Installing phpunit**
We are using an older version of phpunit to avoid dependency conflicts with Magento's dependencies.
```
composer require --ignore-platform-reqs --dev phpunit/phpunit ^6
```

**Running tests**
```
./vendor/phpunit/phpunit/phpunit ./vendor/solvedata/plugins-magento2/tests/
```

## Handle a new Magento event
1. Add a new event to `etc/events.xml` and describe which observer class will be processed.
See [Magento's documentation on events and observers](https://devdocs.magento.com/guides/v2.4/extension-dev-guide/events-and-observers.html).
    ```xml
    <event name="customer_register_success">
        <observer name="prepare_customer_register_success_data"
                  instance="SolveData\Events\Observer\Customer\RegisterObserver" />
    </event>
    ```
1. Create new register handler class in the `Model/Event/RegisterHandler` directory and extending the `SolveData\Events\Model\Event\RegisterHandler\EventAbstract` base class.
    ```php
    class RegisterSuccess extends EventAbstract
    {
        public function prepareData(Observer $observer): EventAbstract
        {
            /** @var CustomerInterface $customer */
            $customer = $observer->getEvent()->getCustomer();
    
            $this->setAffectedEntityId((int)$customer->getId())
                ->setPayload(['customer' => $customer]);
    
            return $this;
        }
    }
    ```
1. Create new observer file in `Observer` folder and extend it from `SolveData\Events\Observer\ObserverAbstract` class. Specify your class as handler.
    ```php
    class RegisterObserver extends ObserverAbstract
    {
        /**
        * @param Config $config
        * @param EventRepository $eventRepository
        * @param Logger $logger
        * @param RegisterSuccess $handler
        */
        public function __construct(
            Config $config,
            EventRepository $eventRepository,
            Logger $logger,
            RegisterSuccess $handler // <-- Your register handler
        ) {
            parent::__construct($config, $eventRepository, $logger, $handler);
        }
    }
    ```
1. Add a new event to the `SolveData\Events\Model\ConfigEventMutationConfig` class and describe which mutation classes will be processed.
    ```php
    function getMutationsForEvents(): array
    {
        return [
            // ... Other events here
            'customer_register_success' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CustomerRegisterSuccess::class
            ]
        ];
    }
    ```
1. Create new mutation file in `Model/Event/Transport/Adapter/GraphQL/Mutation` folder and extend it from `SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract` class.
   Write mutation query in `const QUERY = ...` and prepare variables in `getVariables` function.
    ```php
    class CustomerRegisterSuccess extends MutationAbstract
    {
        const QUERY = <<<'GRAPHQL'
    mutation createOrUpdateProfile($input: ProfileInput!) {
        createOrUpdateProfile(input: $input) {
            id,
            emails
        }
    }
    GRAPHQL;
    
        public function getVariables(): array
        {
            $event = $this->getEvent();
            $payload = $event['payload'];
            $variables = [];

            // Preparing variables
    
            return $variables;
        }
    }
    ```
1. Run `php bin/magento setup:upgrade`
1. Run `php bin/magento setup:di:compile`
1. Go to Solve configs in Admin Panel `Stores > Configuration > Services > Solve Data`
1. Enable you new event in `Enabled Events`
1. Click "Save Config"

## CLI commands to import data
* Import customers to Solve
  `solve:import:customers [--from [FROM]] [--to [TO]]`
* Import orders to Solve
  `solve:import:orders [--from [FROM]] [--to [TO]]`

Attributes `--from` and `--to` is optional.
