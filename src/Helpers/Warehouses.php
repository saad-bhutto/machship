<?php

namespace Technauts\Machship\Helpers;

use Technauts\Machship\Exceptions\InvalidOrMissingEndpointException;

/**
 * Class Warehouses.
 */
class Warehouses extends Endpoint
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
        switch ($endpoint) {
            case 'permanentpickups':
                $client = $this->client;

                if (! isset($client->ids)) {
                    throw new InvalidOrMissingEndpointException(
                        'The orders endpoint on customers requires a customer ID. e.g. $api->warehouses(123)->permanentpickups->get()'
                    );
                }
                $client->queue[] = ['key' => $client->api, 'id' => $client->ids[0] ?? null];
                $client->api = 'permanentpickups';
                $client->ids = [];
                return $this;
            default:
                return parent::__get($endpoint);
        }
    }
}
