<?php

namespace Technauts\Machship\Helpers;

use Technauts\Machship\Exceptions\InvalidOrMissingEndpointException;
use Technauts\Machship\Machship;

/**
 * Class Endpoint.
 *
 * @mixin Machship
 *
 * @property string endpoint
 * @property array ids
 */
abstract class Endpoint
{
    /** @var string[] $endpoints */
    protected static $endpoints = [
        'companies',
    ];

    /** @var Machship $client */
    protected $client;

    /**
     * Endpoint constructor.
     *
     * @param Machship $client
     */
    public function __construct(Machship $client)
    {
        $this->client = $client;
    }

    /**
     * Set our endpoint by accessing it via a property.
     *
     * @param string $property
     *
     * @return $this
     */
    public function __get($property)
    {
        // If we're accessing another endpoint
        if (in_array($property, static::$endpoints)) {
            $client = $this->client;

            if (empty($client->ids)) {
                throw new InvalidOrMissingEndpointException('Calling   from ' . $this->client->api . ' requires an id');
            }

            $last = array_reverse($client->ids)[0] ?? null;
            array_unshift($client->queue, [$client->api, $last]);
            $client->api = $property;
            $client->ids = [];

            return $client->__get($property);
        }

        return $this->$property ?? $this->client->__get($property);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, static::$endpoints)) {
            if ($parameters === []) {
                throw new InvalidOrMissingEndpointException('Calling ' . $method . ' from ' . $this->client->api . ' requires an id');
            }
            $last = array_reverse($this->client->ids)[0] ?? null;
            array_unshift($this->client->queue, [$this->client->api, $last]);
            $this->client->api = $method;
            $this->client->ids = [];

            return $this->client->$method(...$parameters);
        }

        if (in_array($method, ['increment', 'decrement'])) {
            return $this->$method(...$parameters);
        }

        return $this->client->$method(...$parameters);
    }
}
