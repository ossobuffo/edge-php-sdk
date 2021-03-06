<?php

namespace Apigee\Mint;

use Apigee\Util\CacheFactory;
use Apigee\Util\OrgConfig;
use Apigee\Exceptions\ParameterException;

class MonetizationPackage extends Base\BaseObject
{

    /**
     * MonetizationPackage Id
     * @var string
     */
    private $id;

    /**
     * Organization
     * @var \Apigee\Mint\Organization
     */
    private $organization;

    /**
     * Monetization Package Name
     * @var string
     */
    private $name;

    /**
     * Display Name
     * @var string
     */
    private $displayName;

    /**
     * Description
     * @var string
     */
    private $description;

    /**
     * status
     * @var string
     */
    private $status;

    /**
     * Products in this package
     * @var array items in this array are instances of \Apigee\Mint\Product
     */
    private $products = array();

    /**
     * Virtual currency to be purchased as part of monetization package
     * @var \Apigee\Mint\DataStructures\SupportedCurrency
     */
    private $virtualCurrency;

    /**
     * Class constructor
     * @param \Apigee\Util\OrgConfig $config
     */
    public function __construct(OrgConfig $config)
    {
        $base_url = '/mint/organizations/' . rawurlencode($config->orgName) . '/monetization-packages';
        $this->init($config, $base_url);

        $this->wrapperTag = 'monetizationPackage';
        $this->idField = 'id';
        $this->idIsAutogenerated = false;

        $this->initValues();
    }

    /**
     * Implements BaseObject::instantiateNew()
     */
    public function instantiateNew()
    {
        return new MonetizationPackage($this->config);
    }

    /**
     * Implements BaseObject::loadFromRawData($data, $reset = false)
     *
     * @param array $data
     * @param bool $reset
     */
    public function loadFromRawData($data, $reset = false)
    {
        if ($reset) {
            $this->initValues();
        }

        if (isset($data['virtualCurrency'])) {
            $this->virtualCurrency = new DataStructures\SupportedCurrency($data['virtualCurrency']);
        }

        if (isset($data['organization'])) {
            $organization = new Organization($this->config);
            $organization->loadFromRawData($data['organization']);
            $this->organization = $organization;
        }

        if (isset($data['product'])) {
            foreach ($data['product'] as $product_item) {
                $product = new Product($this->config);
                $product->loadFromRawData($product_item);
                $this->products[] = $product;
            }
        }

        $excluded_properties = array('organization', 'product', 'virtualCurrency');
        foreach (array_keys($data) as $property) {
            if (in_array($property, $excluded_properties)) {
                continue;
            }

            // form the setter method name to invoke setXxxx
            $setter_method = 'set' . ucfirst($property);

            if (method_exists($this, $setter_method)) {
                $this->$setter_method($data[$property]);
            } else {
                self::$logger->notice('No setter method was found for property "' . $property . '"');
            }
        }
    }

    /**
     * Implements BaseObject::initValues()
     */
    protected function initValues()
    {
        $this->description = null;
        $this->displayName = null;
        $this->id = null;
        $this->name = null;
        $this->products = array();
        $this->status = null;
        //$this->payment_due_days = null;
        //$this->price_points = array();
        //$this->supports_refund = null;
        //$this->transaction_success_criteria = null;
        //$this->developer = null;
    }

    /**
     * Implements BaseObject::__toString()
     * @return string
     */
    public function __toString()
    {
        $obj = array();
        $properties = array_keys(get_object_vars($this));
        $excluded_properties = array_merge(array_keys(get_class_vars(get_parent_class($this))));
        foreach ($properties as $property) {
            if (in_array($property, $excluded_properties)) {
                continue;
            }
            if (isset($this->$property)) {
                if (is_object($this->$property)) {
                    $obj[$property] = json_decode((string)$this->$property, true);
                } else {
                    $obj[$property] = $this->$property;
                }
            }
        }
        return json_encode($obj);
    }

    /*
     * Declared methods
     */

    /**
     * Fetches packages with published rate plans which are available to a
     * developer.
     *
     * @param string $developer_id
     *
     * @return array
     */
    public function getPackagesWithPublishedRatePlans($developer_id)
    {
        $options = array(
            'query' => array(
                'current' => 'true',
                'allAvailable' => 'true',
            ),
        );
        $url = '/mint/organizations/'
            . rawurlencode($this->config->orgName)
            . '/developers/'
            . rawurlencode($developer_id)
            . '/monetization-packages';
        $this->setBaseUrl($url);
        $this->get(null, 'application/json; charset=utf-8', array(), $options);
        $this->restoreBaseUrl();
        $response = $this->responseObj;

        $return_objects = array();

        foreach ($response['monetizationPackage'] as $response_data) {
            $obj = $this->instantiateNew($this->config);
            $obj->loadFromRawData($response_data);
            $return_objects[] = $obj;
        }
        return $return_objects;
    }

    // getters

    /**
     * Get MonetizationPackage Id
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get Organization
     * @return \Apigee\Mint\Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Get Monetization Package Name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get Display Name
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Get Description
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get Status
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get Products in this package
     * @return array items in this array are instances of \Apigee\Mint\Product
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Get Virtual currency to be purchased as part of monetization package
     * @return \Apigee\Mint\DataStructures\SupportedCurrency
     */
    public function getVirtualCurrency()
    {
        return $this->virtualCurrency;
    }

    // setters

    /**
     * Set MonetizationPackage Id
     * @var string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set Organization
     * @var \Apigee\Mint\Organization $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * Set Monetization Package Name
     * @var string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Display Name
     * @var string $display_name
     */
    public function setDisplayName($display_name)
    {
        $this->displayName = $display_name;
    }

    /**
     * Set Description
     * @var string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Set Status
     * @var string $status
     * @throws \Apigee\Exceptions\ParameterException
     */
    public function setStatus($status)
    {
        $status = strtoupper($status);
        if (!in_array($status, array('CREATED', 'INACTIVE', 'ACTIVE'))) {
            throw new ParameterException('Invalid monetization package status value: ' . $status);
        }
        $this->status = $status;
    }

    /**
     * Add Product to this package
     * @var \Apigee\Mint\Product
     */
    public function addProduct($product)
    {
        $this->products[] = $product;
    }

    /**
     * Remove all product from this package
     */
    public function clearProducts()
    {
        $this->products = array();
    }

    /**
     * Set Virtual currency to be purchased as part of monetization package
     * @var \Apigee\Mint\DataStructures\SupportedCurrency
     */
    public function setVirtualCurrency($curr)
    {
        $this->virtualCurrency = $curr;
    }

    public function load($id = null)
    {
        if (!isset($id)) {
            $id = $this->{$this->idField};
        }
        if (!isset($id)) {
            throw new ParameterException('No object identifier was specified.');
        }
        $cache_manager = CacheFactory::getCacheManager();
        $data = $cache_manager->get('package:' . $id, null);
        if (!isset($data)) {
            $url = rawurlencode($id);
            $this->get($url);
            $data = $this->responseObj;
            $cache_manager->set('package:' . $id, $data);
        }
        $this->initValues();
        $this->loadFromRawData($data);
    }
}
