<?php

namespace Miralexsky\OzonApi\Examples;

use Miralexsky\OzonApi\OzonClient;
use Miralexsky\OzonApi\OzonDTO\OzonClientException;

class Example
{
    public function getCities()
    {
        $client = new OzonClient(false);
        try {
            $token = $client->authorize();
        } catch (OzonClientException $e) {
            echo "Something went wrong, token cant be fetched\n";
            var_dump($e);
        }

        $cities = $client->getCities();
        var_dump($cities);
    }
}