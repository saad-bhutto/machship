<?php

namespace Technauts\Machship\Helpers;

use Technauts\Machship\Exceptions\InvalidOrMissingEndpointException;

/**
 * Class for Carrier Endpoint.
 */
class Carriers extends Endpoint
{
    /**
     * @param string $endpoint
     *
     * @throws InvalidOrMissingEndpointException
     *
     * @return $this|Endpoint
     */
    public function __get($endpoint)
    {
        $client = $this->client;
        $client->setBase('api');
        return $this;
    }

        /**
     * Set ids for one uri() call.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws BadMethodCallException
     *
     * @return $this
     */
    public function __call($method, $parameters)
    {
        switch ($method) {
            case 'carrierservices':
                $client = $this->client;

                if (! isset($client->ids)) {
                    throw new InvalidOrMissingEndpointException(
                        'The carrierservices endpoint on carriers requires a consignment ID. e.g. $api->carriers(123)->carrierservices->get()'
                    );
                }
                $client->setBase('api');
                $client->queue[] = ['id' => $client->ids[0] ?? null];
                $client->api = 'carrierservices';
                $client->ids = [];

                return $this;
            default:
                return parent::__call($method, $parameters);
        }
    }
}
