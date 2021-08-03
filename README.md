# ozon-logistics-api

composer require miralexsky/ozon-logistics-api

Client for interactive with ozon-logistics API <br>
Library in development now, you could use it after few week...

<h2>Example:</h2>

use Miralexsky\OzonApi\OzonClient;

$client = new OzonClient(false);

try { <br>
  $token = $client->authorize(); <br>
} catch (OzonClientException $e) { <br>
  ... <br>
} <br>

$cities = $client->getCities();
