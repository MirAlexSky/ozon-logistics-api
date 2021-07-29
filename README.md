# ozon-logistics-api
Client for interactive with ozon-logistics API

Example:

use Miralexsky\OzonApi\OzonClient;

$client = new OzonClient(false);

try {
  $token = $client->authorize();
} catch (OzonClientException $e) {
  ...
}

$cities = $client->getCities();
