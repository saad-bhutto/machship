<?php

namespace Technauts\Machship\Models;

/**
 * Modal class for location
 */
class Location extends AbstractModel
{
    /** @var string $resource_name */
    public static $resource_name = 'location';

    /** @var string $resource_name_many */
    public static $resource_name_many = 'locations';

    /** @var array $casts */
    protected $casts = [
        "postcode" => 'integer',
        "state" => "array",
        "timeZone" => 'string',
        "suburb" => 'string',
        "country" => "array",
        "description" => 'string',
        "locationType" => 'integer',
    ];
}
