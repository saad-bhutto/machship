<?php

namespace Technauts\Machship\Models;

/**
 * Modal class for Product
 */
class Product extends AbstractModel
{
    /** @var string $resource_name */
    public static $resource_name = 'product';

    /** @var string $resource_name_many */
    public static $resource_name_many = 'products';


    /** @var array $casts */
    protected $casts = [
        "itemType" =>  'integer',
        "typeString" => 'string',
        "name" => 'string',
        "quantity" => 'integer',
        "sku" => 'string',
        "nameAndSku" => 'string'
    ];
}
