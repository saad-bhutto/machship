<?php

namespace Technauts\Machship;

use BadMethodCallException;
use Technauts\Machship\Exceptions\InvalidOrMissingEndpointException;
use Technauts\Machship\Exceptions\ModelNotFoundException;
use Technauts\Machship\Helpers\Endpoint;
use Technauts\Machship\Models\AbstractModel;
use Technauts\Machship\Models\Company;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Technauts\Machship\Helpers\Products;
use Technauts\Machship\Models\CarrierService;
use Technauts\Machship\Models\Consignments;
use Technauts\Machship\Models\Warehouse;

/**
 * Class Machship.
 *
 * @property \Technauts\Machship\Helpers\Companies $companies
 *
 * @method \Technauts\Machship\Helpers\Customers customers(string $customer_id)
 */
class Machship extends Client
{
    public const DEFAULT_PAGE_NUMBER = 1;
    public const DEFAULT_PAGE_SIZE = 50;

    /**
     * The current endpoint for the API. The default endpoint is /orders/.
     *
     * @var string
     */
    public $api = 'authenticate/ping';

    /**
     * The current endpoint for the API. The default endpoint is /orders/.
     *
     * @var string
     */
    public $root = 'live.machship.com';

    /**
     * The cursors for navigating current endpoint pages, if supported.
     *
     * @var array $cursors
     */
    public $cursors = [
        'startIndex' => 1,
        'retrieveSize' => 27,
    ];

    /** @var array $ids */
    public $ids = [];

    /** @var int $type */
    public $endpoint_type = 0;

    /**
     * Methods / Params queued for API call.
     *
     * @var array
     */
    public $queue = [];

    /** @var string $base */
    protected $base = '';

    /** @var array $last_headers */
    protected $last_headers;

    /** @var MessageInterface $last_response */
    protected $last_response;

    /**
     * Our list of valid Machship endpoints.
     *
     * @var array
     */
    protected static $collections_endpoints = [
        'companies' => 'companies/getAll',
        'warehouses' => 'companyLocations/getAll',
        'permanentpickups' => 'companyLocations/getPermanentPickupsForCompanyLocation',
        'carrierservices' => 'api/carriers/GetAccessibleCarriers',
    ];

    /**
     * Our list of valid Machship findable endpoints.
     *
     * @var array
     */
    protected static $findable_endpoints = [
        'warehouses' => 'companyLocations/get?id=%s',
        'products' => 'api/companyItems/get?id=%s',
    ];

    /** @var array $cursored_enpoints */
    protected static $cursored_enpoints = [
        'warehouses' => 'companyLocations/GetAll',
        'products' => 'api/companyItems/GetAll',
        'consignments' => 'consignments/getAll',
    ];

    /** @var array $resource_models */
    protected static $resource_models = [
        'companies' => Company::class,
        'warehouses' => Warehouse::class,
        'carrierservices' => CarrierService::class,
        'products' => Products::class,
        'consignments' => Consignments::class,
    ];


    /**
     * Machship constructor.
     *
     * @param string $token
     * @param string $root
     */
    public function __construct($root, $token, $base = null)
    {
        $this->root = Util::normalizeDomain($root);
        $base_uri = "https://{$this->root}";

        $this->setBase($base);
        parent::__construct([
            'base_uri' => $base_uri,
            'headers' => [
                'token' => $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8;',
            ],
        ]);
    }

    /**
     * @param string $root
     * @param string $token
     *
     * @return Machship
     */
    public static function make($root, $token, $base = null)
    {
        return new static($root, $token, $base);
    }

    /**
     * Get a resource using the assigned endpoint ($this->endpoint).
     *
     * @param array $query
     * @param string $append
     *
     * @return array
     * @throws GuzzleException
     *
     * @throws InvalidOrMissingEndpointException
     */
    public function get($query = [], $append = '')
    {
        if (isset(static::$cursored_enpoints[$this->api])) {
            $query = [
                'startIndex' => $this->cursors['startIndex'],
                'retrieveSize' => $this->cursors['retrieveSize'],
            ];
        }

        // Do request and store response in variable
        $response = $this->request(
            $method = 'GET',
            $uri = $this->uri($append),
            $options = ['query' => $query]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        // If response has Link header, parse it and set the cursors
        if ($response->hasHeader('Link')) {
            $this->cursors = static::parseLinkHeader($response->getHeader('Link')[0]);
        }
        // If we don't have Link on a cursored endpoint then it was the only page. Set cursors to null to avoid breaking next.
        elseif (in_array($this->api, self::$cursored_enpoints, true)) {
            $this->cursors = [
                'startIndex' => null,
                'retrieveSize' => null,
            ];
        }


        if (isset($data['errors']) && ! is_null($data['errors'])) {
            return $data['errors'];
        }

        return $data['object'] ?? $data;
    }

    /**
     * @param array $query
     * @param string $append
     *
     * @return array|null
     *
     * @throws GuzzleException
     * @throws InvalidOrMissingEndpointException
     */
    public function next($query = [], $append = '')
    {
        // Only allow use of next on cursored endpoints
        if (! isset(static::$cursored_enpoints[$this->api])) {
            // Util::isLaravel() && \Log::warning('vendor:dan:shopify:get', ['Use of cursored method on non-cursored endpoint.']);
            return [];
        }

        // If cursors haven't been set, then just call get normally.
        if (count($this->cursors) == 0) {
            $data = $this->get($query, $append) ;
            $this->cursors['startIndex'] += 1;
            return $data ;
        }

        // Only limit key is allowed to exist with cursor based navigation
        foreach (array_keys($query) as $key) {
            if ($key !== 'retrieveSize') {
                // Util::isLaravel() && \Log::warning('vendor:dan:shopify:get', ['Limit param is not allowed with cursored queries.']);
                return [];
            }
        }

        // If cursors have been set and next hasn't been set, then return null.
        if (empty($this->cursors['startIndex'] + 1)) {
            return [];
        }

        // If cursors have been set and next has been set, then return get with next.
        $query['startIndex'] = $this->cursors['startIndex'] + 1;
        $data = $this->get($query, $append) ;
        $this->cursors['startIndex'] += 1;
        return  $data;
    }

    /**
     * Post to a resource using the assigned endpoint ($this->api).
     *
     * @param array|AbstractModel $payload
     * @param string $append
     *
     * @return array|AbstractModel
     * @throws GuzzleException
     *
     * @throws InvalidOrMissingEndpointException
     */
    public function post($payload = [], $append = '')
    {
        return $this->postOrPush('POST', $payload, $append);
    }

    /**
     * Update a resource using the assigned endpoint ($this->api).
     *
     * @param array|AbstractModel $payload
     * @param string $append
     *
     * @return array|AbstractModel
     * @throws GuzzleException
     *
     * @throws InvalidOrMissingEndpointException
     */
    public function put($payload = [], $append = '')
    {
        return $this->postOrPush('PUT', $payload, $append);
    }

    /**
     * @param $post_or_post
     * @param array  $payload
     * @param string $append
     *
     * @throws InvalidOrMissingEndpointException
     * @throws GuzzleException
     *
     * @return mixed
     */
    private function postOrPush($post_or_post, $payload = [], $append = '')
    {
        $payload = $this->normalizePayload($payload);
        $api = $this->api;
        $uri = $this->uri($append);

        $json = $payload instanceof AbstractModel
            ? $payload->getPayload()
            : $payload;

        $response = $this->request(
            $method = $post_or_post,
            $uri,
            $options = compact('json')
        );

        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data[static::apiEntityProperty($api)])) {
            $data = $data[static::apiEntityProperty($api)];

            if ($payload instanceof AbstractModel) {
                $payload->syncOriginal($data);

                return $payload;
            }
        }

        return $data;
    }

    /**
     * Delete a resource using the assigned endpoint ($this->api).
     *
     * @param array|string $query
     *
     * @throws GuzzleException
     * @throws InvalidOrMissingEndpointException
     *
     * @return array
     */
    public function delete($query = [])
    {
        $response = $this->request(
            $method = 'DELETE',
            $uri = $this->uri(),
            $options = ['query' => $query]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $id
     *
     * @throws GuzzleException
     * @throws InvalidOrMissingEndpointException
     * @throws ModelNotFoundException
     *
     * @return AbstractModel|null
     */
    public function find($id)
    {
        try {
            $data = $this->get();

            $model = Util::searchByValue($id, $data);
            if (is_null($model) && in_array($this->api, static::$cursored_enpoints, true)) {
                $search = true;
                while ($search) {
                    $data = $this->next();
                    $model = Util::searchByValue($id, $data);
                    $search = is_null($model);
                }
            }
            if (is_null($model)) {
                // check if there is data present
                return null;
            }
            if (isset(static::$resource_models[$this->api])) {
                $class = static::$resource_models[$this->api];
                return is_null($model) ? null : new $class($model);
            }
        } catch (ClientException $clientException) {
            if ($clientException->getResponse()->getStatusCode() === 404) {
                $msg = sprintf('Model(%s) not found for `%s`', $id, $this->api);
                throw new ModelNotFoundException($msg);
            }

            throw $clientException;
        }
    }
    /**
     * @param $id
     *
     * @throws GuzzleException
     * @throws InvalidOrMissingEndpointException
     * @throws ModelNotFoundException
     *
     * @return AbstractModel|null
     */
    public function findById($id)
    {
        try {
            if (!isset(static::$findable_endpoints[$this->api])) {
                return $this->find($id);
            }
            $data = $this->get(['id' => $id], $args = $id);
            if (isset(static::$resource_models[$this->api])) {
                $class = static::$resource_models[$this->api];

                if (isset($data[$class::$resource_name])) {
                    $data = $data[$class::$resource_name];
                }

                return (count($data) === 0 || is_null($data['object']) ) ? null : new $class($data);
            }
        } catch (ClientException $clientException) {
            if ($clientException->getResponse()->getStatusCode() === 404) {
                $msg = sprintf(
                    'Model(%s) not found for `%s`',
                    $id,
                    $this->api
                );

                throw new ModelNotFoundException($msg);
            }

            throw $clientException;
        }
    }

    /**
     * Return an array of models or Collection (if Laravel present).
     *
     * @param string|array $ids
     *
     * @throws GuzzleException
     * @throws InvalidOrMissingEndpointException
     *
     * @return array
     */
    public function findMany($ids)
    {
        // TODO implemention required
        // if (is_array($ids)) {
        //     $ids = implode(',', array_filter($ids));
        // }

        // return $this->all(compact('ids'));
    }

    /**
     * Machship limits to 250 results.
     *
     * @param array $query
     * @param string $append
     *
     * @throws GuzzleException
     * @throws InvalidOrMissingEndpointException
     *
     * @return array
     */
    public function all($query = [], $append = '')
    {
        $data = $this->get($query, $append);

        if (static::$resource_models[$this->api]) {
            $class = static::$resource_models[$this->api];

            if (isset($data[$class::$resource_name_many])) {
                $data = $data[$class::$resource_name_many];
            }

            $data = array_map(static function ($arr) use ($class) {
                return new $class($arr);
            }, $data);

            return $data;
        }

        return $data;
    }

    /**
     * Post to a resource using the assigned endpoint ($this->api).
     *
     * @param AbstractModel $model
     *
     * @throws GuzzleException
     * @throws InvalidOrMissingEndpointException
     *
     * @return AbstractModel
     */
    public function save(AbstractModel $model)
    {
        // Filtered by uri() if falsy
        // $id = $model->getAttribute($model::$identifier);

        $this->api = $model::$resource_name_many;

        $response = $this->request(
            // $method = $id ? 'PUT' : 'POST', /. endable this to send PUT request
            $method = 'POST',
            $uri = $this->uri(),
            $options = ['json' => $model->getPayload()]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data[$model::$resource_name])) {
            $data = $data[$model::$resource_name];
        }

        $model->syncOriginal($data);

        return $model;
    }

    /**
     * @param AbstractModel $model
     *
     * @throws GuzzleException
     * @throws InvalidOrMissingEndpointException
     *
     * @return bool
     */
    public function destroy(AbstractModel $model)
    {
        $response = $this->delete($model->getOriginal($model::$identifier));
        $success = is_array($response);
        if ($success && count($response) === 0) {
            $model->exists = false;
        }

        return $success;
    }

    /**
     * @param array $query
     *
     * @throws GuzzleException
     * @throws InvalidOrMissingEndpointException
     *
     * @return int
     */
    public function count($query = [])
    {
        $data = $this->get($query, 'count');

        return count($data) === 1
            ? array_values($data)[0]
            : $data;
    }

    /**
     * @param string $append
     *
     * @throws InvalidOrMissingEndpointException
     *
     * @return string
     */
    public function uri($append = '')
    {
        $uri = static::makeUri($this->api, $this->ids, $this->queue, $append, $this->base);
        $this->ids = [];
        $this->queue = [];

        return $uri;
    }

    /**
     * @return string
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * @param string|null $base
     *
     * @return $this
     */
    public function setBase($base = null)
    {
        if (is_null($base)) {
            $this->base = '';
            return $this;
        }

        $this->base = $base;

        return $this;
    }

    /**
     * @param string $api
     * @param array  $ids
     * @param array  $queue
     * @param string $append
     * @param string $base
     *
     * @throws InvalidOrMissingEndpointException
     *
     * @return string
     */
    private static function makeUri($api, $ids = [], $queue = [], $append = '', $base = 'apiv2')
    {

        //0= V2 Endpoint
        //1= Cursored Endpoint
        //2= Findable Endpoint
        //3= V1 Endpoint
        if (isset(static::$collections_endpoints[$api])) {
            $api_endpoint = static::$collections_endpoints[$api];
        } elseif (isset(static::$cursored_enpoints[$api])) {
            $api_endpoint = static::$cursored_enpoints[$api] ;
        }

        //V1 Endpoint
        if (str_starts_with($api_endpoint, 'api')) {
            // forcefully maupulateing V1 Endpoint Urls
            $base = 'api';
            $api_endpoint = str_replace('api/', '', $api_endpoint);
        }

        //Findable Endpoint
        // if ($append !== '') {
        //     $api_endpoint = static::$findable_endpoints[$api];
        //     return "/{$base}/".str_replace('%s', $append, $api_endpoint);
        // }
        $count = substr_count($api_endpoint, '%');
        $endpoint = $api_endpoint ;
        if ($count === count($ids)) {
            // Is it an entity endpoint?
            $endpoint = vsprintf($api_endpoint, $ids);

        } elseif ($count && $append !== '') {
            // Is it a findable endpoint?
            $endpoint = vsprintf($api_endpoint, [$append]);

        } elseif ($count === (count($ids) + 1)) {
            // Is it a collection endpoint?
            $id = array_shift($queue);
            $endpoint = vsprintf($api_endpoint, [$id['id']]);
        } else {
            // Is it just plain wrong?
            // $msg = sprintf( 'You did not specify enough ids for endpoint `%s`, ids(%s).', $api_endpoint, implode($ids));
            // throw new InvalidOrMissingEndpointException($msg);
        }

        $endpoint = "/{$base}/{$endpoint}";
        $endpoint = str_replace('//', '/', $endpoint);
        $endpoint = ($append !== '') ? Util::appendQueryStringToURL($endpoint, $append) : $endpoint;

        return $endpoint;
    }

    /**
     * @param $payload
     *
     * @return mixed
     */
    private function normalizePayload($payload)
    {
        if ($payload instanceof AbstractModel) {
            return $payload;
        }

        if (! isset($payload['id'])) {
            $count = count($args = array_filter($this->ids));
            if ($count) {
                $last = $args[$count - 1];
                if (is_numeric($last)) {
                    $payload['id'] = $last;
                }
            }
        }

        $entity = $this->getApiEntityProperty();

        return [$entity => $payload];
    }

    /**
     * @return string
     */
    private function getApiEntityProperty()
    {
        return static::apiEntityProperty($this->api);
    }

    /**
     * @param string $api
     *
     * @return string
     */
    private static function apiEntityProperty($api)
    {
        /** @var AbstractModel $model */
        $model = static::$resource_models[$api];

        return $model::$resource_name;
    }

    /**
     * Set our endpoint by accessing it like a property.
     *
     * @param string $endpoint
     *
     * @return $this|Endpoint
     * @throws \Exception
     */
    public function __get($endpoint)
    {
        if (array_key_exists($endpoint, static::$collections_endpoints)) {
            $this->api = $endpoint;
        } else if (array_key_exists($endpoint, static::$cursored_enpoints)) {
            $this->api = $endpoint;
        }

        $className = "Technauts\Machship\\Helpers\\".Util::studly($endpoint);
        if (class_exists($className)) {
            return new $className($this);
        }

        // If user tries to access property that doesn't exist, scold them.
        throw new \RuntimeException('Property does not exist on API');
    }

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
        if (array_key_exists($method, static::$collections_endpoints) || array_key_exists($method, static::$cursored_enpoints)) {
            $this->ids = $parameters;
            return $this->__get($method);
        }
        $msg = sprintf('Method %s does not exist.', $method);

        throw new BadMethodCallException($msg);
    }

    /**
     * @param $responseStack
     *
     * @throws ReflectionException
     *
     * @return Helpers\Testing\MachshipMock
     */
    // public static function fake($responseStack = [])
    // {
    //     return new Helpers\Testing\MachshipMock($responseStack);
    // }

    /**
     * Wrapper to the $client->request method.
     *
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return mixed|ResponseInterface
     */
    public function request($method, $uri = '', array $options = [])
    {
        $this->last_response = $response = parent::request($method, $uri, $options);
        $this->last_headers = $response->getHeaders();

        return $response;
    }

    /**
     * @param callable $request
     *
     * @return array
     */
    public function rateLimited(callable $request)
    {
        try {
            return $request($this);
        } catch (ClientException $clientException) {
            if ($clientException->getResponse()->getStatusCode() === 429) {
                return $this->rateLimited($request);
            }

            throw $clientException;
        }
    }

    /**
     * @return array
     */
    protected function lastHeaders()
    {
        return $this->last_headers;
    }

    /**
     * @return MessageInterface
     */
    protected function lastResponse()
    {
        return $this->last_response;
    }

    /**
     * @param $linkHeader
     *
     * @return array
     */
    protected static function parseLinkHeader($linkHeader)
    {
        $cursors = [];

        foreach (explode(',', $linkHeader) as $link) {
            $data = explode(';', trim($link));
            $matches = [];
            if (preg_match('/page_info=[A-Za-z0-9]+/', $data[0], $matches)) {
                $page_info = str_replace('page_info=', '', $matches[0]);
                $rel = str_replace('"', '', str_replace('rel=', '', trim($data[1])));
                $cursors[$rel] = $page_info;
            }
        }

        return $cursors;
    }
}
