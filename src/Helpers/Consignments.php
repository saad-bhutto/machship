<?php

namespace Technauts\Machship\Helpers;

use Technauts\Machship\Exceptions\InvalidOrMissingEndpointException;

/**
 * Class for Consignments Endpoint.
 */
class Consignments extends Endpoint
{
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
            case 'notes':
                $client = $this->client;

                if (! isset($client->ids)) {
                    throw new InvalidOrMissingEndpointException(
                        'The notes endpoint on consignments requires a consignment ID. e.g. $api->consignments(123)->notes->get()'
                    );
                }
                $client->queue[] = ['id' => $client->ids[0] ?? null];
                $client->api = 'consignmentnotes';
                $client->ids = [];
                return $this;
            default:
                return parent::__call($method, $parameters);
        }

    }



}
