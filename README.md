# WIP Machship-SDK

An object-oriented approach towards using the Machship API.


[![Latest Version on Packagist](https://img.shields.io/packagist/v/saad-bhutto/machship.svg?style=flat-square)](https://packagist.org/packages/saad-bhutto/machship)
[![Total Downloads](https://img.shields.io/packagist/dt/saad-bhutto/machship.svg?style=flat-square)](https://packagist.org/packages/saad-bhutto/machship)

> Offical Documentation: [click here](https://demo.machship.com/swagger/index.html)


## Supported Objects / Endpoints:

* [Companies](#)
* [Warehouses](#)
* [CarrierServices](#)
* [Consignments](#)
* [Product](#)
* [Fulfillment](#)

## Installation

You can install the package via composer:

```bash
composer require saad-bhutto/machship
```

## Usage

The package is built around endpoint classes, which are converted to modals.

The API can be initialize using these 

``` php
// initializing the object
$api = Machship::make(MACHSHIP_TOKEN);
```

### [Companies](#)
Get all companies that the current user can access

``` php
// get the object
$api->companies()->all();
```

Find a single company by ID
``` php
// get the object
$api->companies()->findById($id);
```
### [Warehouses/CompanyLocations](#)
Get all Warehouses/CompanyLocations that the current user can access

``` php
// get the object
$api->warehouses()->all();
```

Find a single warehouses by ID
``` php
// get the object
$api->warehouses()->findById($id);
```

### [CarrierServices](#)
Get all CarrierServices that the current user can access

``` php
// get the object
$api->carrierservices()->all();
```

Find a single carrierservices by ID

``` php
// get the object
$api->carrierservices()->findById($id);
```

### [Consignments](#)
Get all the active Consignments that the current user can access in machship
N.B. this is a cursored endpoint, i.e. it has paginations enabled

``` php
// get the first page of consignments object
$api->consignments()->all();
```
`next()` can be used iteratively to get the objects from next page
``` php
// get the next page of consignments object
$api->consignments()->next();
```

Find a single consignment by ID

``` php
// get the object
$api->consignments()->findById($id);
```

### [Producsts/Items](#)
Get all the active Producsts/Items that the current user can access in machship
N.B. this is a cursored endpoint, i.e. it has paginations enabled

``` php
// get the first page of consignments object
$api->products()->all();
```
`next()` can be used iteratively to get the objects from next page
``` php
// get the next page of products object
$api->products()->next();
```

Find a single consignment by ID

``` php
// get the object
$api->products()->findById($id);
```


### Testing

WIP WIP WIP WIP WIP WIP WIP WIP 

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email saadbhutto@ymail.com instead of using the issue tracker.

## Credits

- [Saad Bhutto](https://github.com/saad-bhutto)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
