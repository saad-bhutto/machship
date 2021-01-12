<?php

namespace Technauts\Machship\Helpers;

/**
 * Endpoint Class for Products.
 */
class Products extends Endpoint
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
