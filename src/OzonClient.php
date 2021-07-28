<?php

namespace Miralexsky\OzonApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Miralexsky\OzonApi\OzonTDO\OzonClientException;
use Miralexsky\OzonApi\OzonTDO\OzonError;
use Miralexsky\OzonApi\OzonTDO\OzonResponse;

class OzonClient
{
    const ID_TEST = 'ApiTest_11111111-1111-1111-1111-111111111111';
    const SECRET_TEST = 'SRYksX3PBPUYj73A6cNqbQYRSaYNpjSodIMeWoSCQ8U=';

    const URI = 'https://api.ozon.ru/principal-integration-api/v1/';
    const AUTH_URI = 'https://api.ozon.ru/principal-auth-api/connect/token';

    const TEST_URI = 'https://api-stg.ozonru.me/principal-integration-api/v1/';
    const TEST_AUTH_URI = 'https://api-stg.ozonru.me/principal-auth-api/connect/token';

    private $client;

    private $uri = '';
    private $auth_uri = '';

    private $client_id = '';
    private $client_secret = '';

    public $client_token = null;
    public $token_expires_in = null;

    private $guzzle_options = [];

    public function __construct($isProd)
    {
        if ($isProd) {
            $this->enableProduction();
        } else {
            $this->disableProduction();
            $this->setCredentialsToTest();
        }

        $this->client = new Client();
    }

    public function setGuzzleOptions($options)
    {
        $this->guzzle_options = $options;
    }

    public function getGuzzleOptions()
    {
        return $this->guzzle_options;
    }

    /**
     * @throws OzonClientException|GuzzleException
     */
    public function authorize()
    {
        try {
            $guzzleResponse = $this->client->request('POST', $this->auth_uri, [
                'form_params' => [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->client_id,
                    'client_secret' => $this->client_secret
                ],
                'headers'     => ['Content-Type' => 'application/x-www-form-urlencoded']
            ]);

            $response = json_decode($guzzleResponse->getBody(), true);
        } catch (GuzzleException $e) {
            throw $e;
            // throw new OzonClientException('Error while trying to authorize to Ozon-logistics');
        } catch (\Exception $e) {
            throw $e;
            // throw new OzonClientException('Error while trying to authorize to Ozon-logistics');
        }

        if (isset($response) && $response != null) {
            $token = isset($response['access_token']) ? $response['access_token'] : null;
            $this->token_expires_in = isset($response['$expires_in']) ? $response['$expires_in'] : null;

            if (!$token) {
                throw new OzonClientException('Ozon response doesn\'t contain token');
            }

            $this->client_token = $token;

            return $token;

        } else {
            throw new OzonClientException('Error while trying to authorize to Ozon-logistics');
        }
    }

    public function setToken($token)
    {
        $this->client_token = $token;
    }

    /**
     * @throws OzonClientException
     * @throws GuzzleException
     */
    public function makeRequest($method, $url, $requestOptions = [])
    {
        if (!$this->client_id || !$this->client_secret) {
            throw new OzonClientException('Client_id or client_secret haven\'t been set');
        }

        if (!$this->client_token) {
            throw new OzonClientException('Client_token haven\'t been set, you should use authorize() or setToken() method');
        }

        $requestOptions['headers'] = [
            'Authorization' => "Bearer $this->client_token",
        ];

        $request_data = isset($request['body']) ? json_decode($request['body']) :
            (isset($request['query']) ? $request['query'] : '');

        $ozonResponse = new OzonResponse();
        $ozonResponse->request_data = $request_data;
        $ozonResponse->request = $requestOptions;

        try {

            $guzzleResponse = $this->client->request($method, "$this->uri$url", $requestOptions);

        } catch (RequestException $e) {

            $ozonResponse->exception = $e;

            if (!$e->getResponse()) {

                $ozonResponse->setErrorMessage('Error while trying to make request, no response');
                return $ozonResponse;
            }

            $ozonResponse->guzzleResponse = $e->getResponse();

            if ($e->getResponse()->getStatusCode() === 401) {
                $ozonResponse->setErrorMessage('Error while trying to make request, 401, not authorized');
                return $ozonResponse;
            }

            $body = $e->getResponse()->getBody();

            if (!$body) {
                $ozonResponse->setErrorMessage('Error while trying to make request, response has no body');
                return $ozonResponse;
            }

            $ozonResponse->response_content = $body;

            $body_data = json_decode($body, true);

            if (!$body_data) {
                $ozonResponse->setErrorMessage('Error while trying to make request, response are not jsonable');
                return $ozonResponse;
            }

            $ozonResponse->response_data = $body_data;

            if ($body_data['errorCode']) {
                return $this->getErrorResponse($ozonResponse);
            } else {
                $ozonResponse->setErrorMessage('Error while trying to make request, response has no errorCode');
                return $ozonResponse;
            }

        } catch (GuzzleException $e) {
            $ozonResponse->exception = $e;
            return $ozonResponse;
        } catch (\Exception $e) {
            $ozonResponse->exception = $e;
            return $ozonResponse;
        }

        $ozonResponse->guzzleResponse = $guzzleResponse;

        return $this->getSuccessResponse($ozonResponse);
    }

    /**
     * @param OzonResponse $ozonResponse
     * @return OzonResponse
     */
    public function getErrorResponse($ozonResponse)
    {
        $data = $ozonResponse->response_data;
        $code = isset($data['errorCode']) ? $data['errorCode'] : null;
        $message = isset($data['message']) ? $data['message'] : null;
        $arguments = isset($data['arguments']) ? $data['arguments'] : null;

        $ozonResponse->setErrorMessage("Заказ не обработан. Ошибка: $message ($code)");

        foreach ($arguments as $argErrors) {
            $error = new OzonError();
            $error->error_message = implode(', ', $argErrors) . "; ";
            $ozonResponse->pushOzonError($error);
        }

        return $ozonResponse;
    }

    /**
     * @param ozonResponse $ozonResponse
     * @return OzonResponse
     */
    public function getSuccessResponse($ozonResponse)
    {
        $guzzleResponse = $ozonResponse->guzzleResponse;
        $responseContent = $guzzleResponse->getBody();
        if (!$responseContent) {
            $ozonResponse->setErrorMessage('Response has no body');
            return $ozonResponse;
        }

        $ozonResponse->response_content = $responseContent;
        $response_data = json_decode($responseContent, true);

        if (!$response_data) {
            $ozonResponse->setErrorMessage('Response body are not jsonable');
            return $ozonResponse;
        }

        $ozonResponse->response_data = $response_data;
        $ozonResponse->success = true;

        return $ozonResponse;
    }

    public function getCities()
    {
        $ozonResponse = $this->makeRequest('GET', 'delivery/cities');

        return $ozonResponse;
    }

    public function getVariants($city)
    {
        $query = [
            'cityName' => $city
        ];

        $ozonResponse = $this->makeRequest('GET', 'delivery/variants', [
            'query' => $query,
        ]);

        return $ozonResponse;
    }

    public function getVariantsByAddress($address = 'Ижевск', $type = 'PickPoint')
    {
        $json = json_encode([
            'deliveryType' => $type,
            'address'      => $address,
            'radius'       => 50,
            'packages'     => [
                [
                    'count'      => 1,
                    'dimensions' => [
                        'wight'  => 1000,
                        'length' => 50,
                        'height' => 50,
                        'width'  => 50,
                    ],
                    'price'      => 1000,
                ]
            ]
        ]);

        $ozonResponse = $this->makeRequest('POST', 'delivery/variants/byaddress', [
            'body' => $json
        ]);

        return $ozonResponse;
    }

    public function calculate($fromPlaceId, $variant_id, $weight)
    {
        $query = [
            'deliveryVariantId' => $variant_id,
            'weight'            => $weight ?: 1,
            'fromPlaceId'       => $fromPlaceId,
        ];

        $ozonResponse = $this->makeRequest('GET', 'delivery/calculate', [
            'query'           => $query,
            'timeout'         => 2,
            'connect_timeout' => 2,
        ]);

        return $ozonResponse;
    }

    public function getTime($fromPlaceId, $deliveryVariantId)
    {
        $json = [
            'fromPlaceId'       => $fromPlaceId,
            'deliveryVariantId' => $deliveryVariantId,
        ];

        $ozonResponse = $this->makeRequest('GET', 'delivery/time', [
            'query'           => $json,
            'timeout'         => 2,
            'connect_timeout' => 2,
        ]);

        return $ozonResponse;
    }

    public function getTariffInfo()
    {
        $ozonResponse = $this->makeRequest('GET', 'tariff/list');
        return $ozonResponse;
    }

    public function uploadSending($order)
    {
        $json = json_encode($order);

        $ozonResponse = $this->makeRequest('POST', 'order', [
            'body' => $json
        ]);

        return $ozonResponse;
    }

    public function getLabelFile($posting_id)
    {
        $query = [
            'postingId' => $posting_id
        ];

        $ozonResponse = $this->makeRequest('GET', 'posting/ticket', [
            'query' => $query
        ]);

        return $ozonResponse;
    }

    public function getTracking($track)
    {
        $query = [
            'postingNumber' => $track
        ];

        $ozonResponse = $this->makeRequest('GET', 'tracking/bypostingnumber', [
            'query' => $query
        ]);

        return $ozonResponse;
    }

    private function disableProduction()
    {
        $this->uri = static::TEST_URI;
        $this->auth_uri = static::TEST_AUTH_URI;
    }

    private function enableProduction()
    {
        $this->uri = static::URI;
        $this->auth_uri = static::AUTH_URI;
    }

    private function setCredentials($client_id, $client_secret)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }

    private function setCredentialsToTest()
    {
        $this->client_id = static::ID_TEST;
        $this->client_secret = static::SECRET_TEST;
    }

}