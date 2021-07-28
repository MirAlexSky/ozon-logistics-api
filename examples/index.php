<?php

require_once '../vendor/autoload.php';
require_once '../src/OzonClient.php';
require_once '../src/OzonDTO/OzonResponse.php';
require_once '../src/OzonDTO/OzonError.php';
require_once '../src/OzonDTO/OzonClientException.php';
require_once 'Example.php';

use Miralexsky\OzonApi\Examples\Example;

$example = new Example();
$example->getCities();
