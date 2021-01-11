<?php

namespace Technauts\Machship\Models;

/**
 * Modal class for Companies
 */
class Company extends AbstractModel
{
    /** @var string $resource_name */
    public static $resource_name = 'company';

    /** @var string $resource_name_many */
    public static $resource_name_many = 'companies';

    /** @var array $casts */
    protected $casts = [
        'parentCompanyId' => 'integer',
        'id' => 'integer',
        'name' => 'string',
        'accountCode' => 'string',
        'displayName' => 'string',
    ];
}
