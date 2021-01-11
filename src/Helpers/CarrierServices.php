<?php

namespace Technauts\Machship\Helpers;

/**
 * Class CarrierServices for a Warehouses.
 */
class CarrierServices extends Endpoint
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
}
