<?php

namespace Technauts\Machship\Helpers;

/**
 * Class for CarrierServices Endpoint.
 */
class Carrierservices extends Endpoint
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
