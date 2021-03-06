<?php
namespace Apigee\Mint;

use DateTime;
use DateTimeZone;
use Apigee\Exceptions\ResponseException;
use Apigee\Mint\Exceptions\MintApiException;
use Apigee\Exceptions\ParameterException;
use Apigee\Util\CacheFactory;
use Apigee\Util\OrgConfig;

class DeveloperRatePlan extends Base\BaseObject
{
    /**
     * The plan is currently active for the developer and can be used for
     * API calls.
     */
    const STATUS_ACTIVE = 'Active';

    /**
     * The plan will be active at a future date, and cannot be used until
     * that date.
     */
    const STATUS_FUTURE = 'Future';

    /**
     * The plan haa been ended by the provider, or the developer has ended
     * the plan.
     */
    const STATUS_ENDED = 'Ended';

    private $developer_or_company_id;

    /**
     * @var string
     * Format YYYY-MM-DD
     */
    private $startDate;

    /**
     * @var string
     * Format YYYY-MM-DD
     */
    private $endDate;

    /**
     * @var string
     */
    private $id;

    private $nextRecurringFeeDate;

    /**
     * @var \Apigee\Mint\RatePlan
     */
    private $ratePlan;

    /**
     * @var string
     * Format YYYY-MM-DD
     */
    private $renewalDate;


    public function __construct($developer_or_company_id, OrgConfig $config)
    {

        $base_url = '/mint/organizations/'
            . rawurlencode($config->orgName)
            . '/developers/'
            . rawurlencode($developer_or_company_id)
            . '/developer-accepted-rateplans';
        $this->init($config, $base_url);
        $this->developer_or_company_id = $developer_or_company_id;

        $this->idField = 'id';
        $this->idIsAutogenerated = true;
        $this->wrapperTag = 'developerRatePlan';

        $this->initValues();
    }

    public function getList($page_num = null, $page_size = 20)
    {
        $cache_manager = CacheFactory::getCacheManager();
        $data = $cache_manager->get('developer_accepted_rateplan:' . $this->developer_or_company_id, null);
        if (!isset($data)) {
            $this->get();
            $data = $this->responseObj;
            $cache_manager->set('developer_accepted_rateplan:' . $this->developer_or_company_id, $data);
        }
        $return_objects = array();
        foreach ($data[$this->wrapperTag] as $response_data) {
            $obj = $this->instantiateNew();
            $obj->loadFromRawData($response_data);
            $return_objects[] = $obj;
        }
        return $return_objects;
    }

    /**
     * Implements Base\BaseObject::initValues().
     *
     * @return void
     */
    protected function initValues()
    {
        $this->startDate = null;
        $this->endDate = null;
        $this->id = null;
        $this->ratePlan = null;
    }

    /**
     * Implements Base\BaseObject::instantiateNew().
     *
     * @return DeveloperRatePlan
     */
    public function instantiateNew()
    {
        return new DeveloperRatePlan($this->developer_or_company_id, $this->config);
    }

    /**
     * Implements Base\BaseObject::loadFromRawData().
     *
     * @param array $data
     * @param bool $reset
     */
    public function loadFromRawData($data, $reset = false)
    {
        if ($reset) {
            $this->initValues();
        }
        $excluded_properties = array('ratePlan', 'developer');
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

        if (isset($data['ratePlan']) && is_array($data['ratePlan']) && count($data['ratePlan']) > 0) {
            if (isset($data['ratePlan']['monetizationPackage']['id'])) {
                $m_package_id = $data['ratePlan']['monetizationPackage']['id'];
                $this->ratePlan = new RatePlan($m_package_id, $this->config);
                $this->ratePlan->loadFromRawData($data['ratePlan']);
            }
        }
    }

    public function forceSave()
    {
        $url = '/mint/organizations/'
            . rawurlencode($this->config->orgName)
            . '/developers/'
            . rawurlencode($this->developer_or_company_id)
            . '/developer-rateplans';
        try {
            $obj = array(
                'developer' => array('id' => $this->developer_or_company_id),
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'ratePlan' => array('id' => $this->ratePlan->getId()),
                'suppressWarning' => true
            );
            $this->setBaseUrl($url);
            $this->post(null, $obj);
            $this->restoreBaseUrl();
        } catch (ResponseException $re) {
            if (MintApiException::isMintExceptionCode($re)) {
                throw new MintApiException($re);
            }
            throw $re;
        }
    }

    public function save($save_method = 'update')
    {
        $url = '/mint/organizations/'
            . rawurlencode($this->config->orgName)
            . '/developers/'
            . rawurlencode($this->developer_or_company_id)
            . '/developer-rateplans';
        $obj = array(
            'developer' => array('id' => $this->developer_or_company_id),
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'ratePlan' => array('id' => $this->ratePlan->getId()),
        );
        try {
            $this->setBaseUrl($url);
            if ($save_method == 'create') {
                $this->post(null, $obj);
            } elseif ($save_method == 'update') {
                $obj['id'] = $this->id;
                $this->put($this->getId(), $obj);
            } else {
                throw new ParameterException('Unsupported save method argument: ' . $save_method);
            }
            $this->restoreBaseUrl();
        } catch (ResponseException $re) {
            $e = MintApiException::factory($re);
            throw $e;
        }
    }

    public function delete()
    {
        $baseUrl = '/mint/organizations/'
            . rawurlencode($this->config->orgName)
            . '/developers/'
            . rawurlencode($this->developer_or_company_id)
            . '/developer-rateplans/'
            . rawurlencode($this->id);
        $this->setBaseUrl($baseUrl);
        $this->httpDelete(null);
        $this->restoreBaseUrl();
    }

    /**
     * Implements Base\BaseObject::__toString().
     *
     * @return string
     */
    public function __toString()
    {
        $endDate = $startDate = '';
        $endDateTime = $this->getEndDateTime();
        $startDateTime = $this->getStartDateTime();
        if ($endDateTime instanceof DateTime) {
            $endDate = $endDateTime->format('Y-m-d H:i:s');
        }
        if ($startDateTime instanceof DateTime) {
            $startDate = $startDateTime->format('Y-m-d H:i:s');
        }

        $obj = array(
            'developer' => array('id' => $this->developer_or_company_id),
            'endDate' => $endDate,
            'startDate' => $startDate,
            'id' => $this->id,
            'ratePlan' => null
        );
        if (isset($this->ratePlan)) {
            $obj['ratePlan'] = array('id' => $this->ratePlan->getId());
        }

        return json_encode($obj);
    }

    /* Accessors */

    public function getDeveloperId()
    {
        return $this->developer_or_company_id;
    }

    /**
     * Get start date as a DateTime object in org's timezone.
     * @return \DateTime The start date
     */
    public function getStartDateTime()
    {
        return $this->convertToDateTime($this->startDate);
    }

    /**
     * Get end date as a DateTime object in org's timezone.
     * @return \DateTime The end date or null if not set
     */
    public function getEndDateTime()
    {
        $org_timezone = new DateTimeZone($this->getRatePlan()->getOrganization()->getTimezone());
        $today = new DateTime('today', $org_timezone);
        $start_date = $this->getStartDateTime();
        $end_date = $this->convertToDateTime($this->endDate);
        // COMMERCE-558: If there is an end date and it has already started,
        // and the plan was also ended today, shift end_date to start_date.
        // TODO: Look into this, I believe this logic is wrong -cnovak
        if (is_object($end_date) && $start_date <= $today && $end_date < $start_date) {
            $end_date = $start_date;
        }
        return $end_date;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRatePlan()
    {
        return $this->ratePlan;
    }

    /**
     * Get renewal date as a DateTime object in org's timezone.
     * @return \DateTime The renewal date or null if not set
     */
    public function getRenewalDateTime()
    {
        return $this->convertToDateTime($this->renewalDate);
    }

    /**
     * Get next recurring fee date date as a DateTime object in org's timezone.
     * @return \DateTime the recurring fee date or null if not set
     */
    public function getNextRecurringFeeDateTime()
    {
        return $this->convertToDateTime($this->nextRecurringFeeDate);
    }

    public function getStatus()
    {
        $org_timezone = new DateTimeZone($this->getRatePlan()->getOrganization()->getTimezone());
        $today = new DateTime('today', $org_timezone);

        // If rate plan ended before today, the status is ended.
        $plan_end_date = $this->getRatePlan()->getEndDateTime();
        if (!empty($plan_end_date) && $plan_end_date < $today) {
            return DeveloperRatePlan::STATUS_ENDED;
        }
        // If the developer ended the plan before today, the plan has ended.
        $developer_plan_end_date = $this->getEndDateTime();
        if (!empty($developer_plan_end_date) && $developer_plan_end_date < $today) {
            return DeveloperRatePlan::STATUS_ENDED;
        }

        // If the start date is later than today, it is a future plan.
        $developer_plan_start_date = $this->getStartDateTime();
        if (!empty($developer_plan_start_date) && $developer_plan_start_date > $today) {
            return DeveloperRatePlan::STATUS_FUTURE;
        }

        return DeveloperRatePlan::STATUS_ACTIVE;
    }

    public function isCancelable()
    {
        $org_timezone = new DateTimeZone($this->getRatePlan()->getOrganization()->getTimezone());
        $start_date = $this->getStartDateTime();
        $today = new DateTime('today', $org_timezone);

        if ($start_date->getTimestamp() > $today->getTimestamp()) {
            return true;
        }
        return false;
    }

    /* Setters */

    public function setStartDate($start_date)
    {
        $this->startDate = $start_date;
    }

    public function setEndDate($end_date)
    {
        $this->endDate = $end_date;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setRatePlan($rate_plan)
    {
        $this->ratePlan = $rate_plan;
    }

    public function setRenewalDate($renewal_date)
    {
        $this->renewalDate = $renewal_date;
    }

    public function setNextRecurringFeeDate($next_recurring_fee_date)
    {
        $this->nextRecurringFeeDate = $next_recurring_fee_date;
    }

    public function setDeveloperId($dev)
    {
        $this->developer_or_company_id = $dev;
    }

    /**
     * Convert date string to DateTime object in proper timezone.
     *
     * To get the proper date, the date needs to be converted from
     * UTC time to the org's timezone.
     *
     * @param $date_string string The date in the Edge API format of 'Y-m-d H:i:s'
     * @return \DateTime The date as a DateTime object or NULL if not set.
     */
    private function convertToDateTime($date_string)
    {
        if (empty($date_string)) {
            return null;
        }
        $org_timezone = new DateTimeZone($this->getRatePlan()->getOrganization()->getTimezone());
        $utc_timezone = new DateTimeZone('UTC');

        // Get UTC datetime of date string.
        $date_utc = DateTime::createFromFormat('Y-m-d H:i:s', $date_string, $utc_timezone);

        if ($date_utc == false) {
            return null;
        }

        // Convert to org's timezone.
        return  $date_utc->setTimezone($org_timezone);
    }
}
