# Creating Reviews in Solve via Custom Hooks

This example extends the Solve Magento extension to handle Magento reviews by emitting a custom event in Solve.

## Example Walkthrough

### Persisting event into the Solve's queue

1. An strategy for who to persist the the `review_save_after` event is defined.
    
    See [Model/Event/ReviewSave.php](Model/Event/ReviewSave.php).

1. A Magento observer is defined to handle the `review_save_after` event by invoking the previously defined `ReviewSave` strategy.

    See [Observer/ReviewSavedAfter.php](Observer/ReviewSavedAfter.php) and [etc/events.xml](etc/events.xml).

### Handling the event to send to Solve's GraphQL API

1. Firstly we need to define the logic to send the GraphQL request from the event persisted into the event queue.

    See [Model/GraphQL/CreateReviewMutation.php](Model/GraphQL/CreateReviewMutation.php).

1. Then we need to override the configuration that maps the event names to the GraphQL mutation(s) that process them. We can do that by using [Magento's interceptor plugins](https://devdocs.magento.com/guides/v2.4/extension-dev-guide/plugins.html) to decorate the behavior of the extension's `SolveData\Events\Model\Config\EventMutationConfig` class.

    See [Model/Config/EventMutationConfigPlugin.php](Model/Config/EventMutationConfigPlugin.php) and [etc/di.xml](etc/di.xml).
