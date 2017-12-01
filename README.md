# NTISyncBundle


## Requirements

Below are a list of things that need to be considered in order to implement this bundle:

1. Entities need to have a method called `getUpdatedOn()` in order for this bundle to work properly. As the name implies, it should return the `\DateTime` of when the 
object was last updated and cannot be null or `0000-00-00 00:00:00`.
2. Entities to be synced must have a repository implementing the `SyncRepositoryInterface`. (see below for more information)
3. The mapping (`SyncMapping`) needs to be configured foreach entity as it is the list used as reference for the lookup 

## Background process


## Implementation

The idea behind the synchronization process is that every object that is going to be synchronized should implement the `SyncRepositoryInterface` in its repository.

```
/**
 * Interface SyncRepositoryInterface
 * @package NTI\SyncBundle\Interfaces
 */
interface SyncRepositoryInterface {
    /**
     * This function should return a plain array containing the results to be sent to the client
     * when a sync is requested. The container is also passed as a parameter in order to give additional
     * flexibility to the repository when making decision on what to show to the client. For example, if the user
     * making the request only has access to a portion of the data, this can be handled via the container in this method
     * of the repository.
     * 
     * The resulting structure should be the following:
     * 
     * array(
     *      "data" => (array of objects),
     *      SyncState::REAL_LAST_TIMESTAMP => (last updated_on date from the array of objects),
     * )
     *     
     *
     * @param $timestamp
     * @param ContainerInterface $container
     * @param array $serializationGroups
     * @return mixed
     */
    public function findFromTimestamp($timestamp, ContainerInterface $container, $serializationGroups = array());
```

Besides implementing the interface, in the database `nti_sync_mapping` the mapping for each class that is going to be synchronized should be configured along with a name.

When a third party makes a request is made to the controller using the following structure:

```
POST /nti/sync/
Content-Type: application/json
{
    "mappings": [
        { "mapping": "[MAPPING_NAME]", "timestamp": [LAST_TIMESTAMP_CHECKED] }
    ]
}
```
Note: This request can also be done using a query and GET instead.

After receiving the request, if a mapping with the specified name exists, the system will call the repository's findFromTimestamp implementation and return the following result (Using a Product entity as an example):

```
{
    "[MAPPING_NAME]": {
        "changes": [
            {
                "id": 2,
                "productId": "POTATOBAG",
                "name": "Potato bag",
                "description": "Bag of potatoes",
                "price": "32.99",
                "cost": "0",
                "createdOn": "11/30/2017 04:22:49 PM",
                "updatedOn": "11/30/2017 04:22:49 PM"
            },
            ...
        ],
        "deletes": [
            {
                "id": 2,
                "mapping": {
                    "id": 2,
                    "name": "Product",
                    "class": "AppBundle\\Entity\\Product\\Product"
                },
                "classId": 137,
                "timestamp": 1512080746
            },
            ...
        ],
        "_real_last_timestamp": 1512092445
    }
}

```

The server will return the both the `changes` and the `deletes`. The `changes` will contain the `data` portion of the array returned by
the repository's implementation of `SyncRepositoryInterface`. The `deletes` will contain the list of `SyncDeleteState` that were recorded since the 
specified timestamp.

The `_real_last_timestamp` should be used as it can help with paginating the results for a full-sync and help the client
get the real last timestamp of the last object in the response. This has to be obtained in the repository and can be done
by simply looping through the array of objects and getting the latest updatedOn.

From this point on, the client must keep a track of the `_real_last_timestamp` in order to perform a sync in the future. 
