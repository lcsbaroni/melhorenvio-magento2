<?php
namespace Lb\MelhorEnvios\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Lb\MelhorEnvios\Service\v2\MelhorEnviosService;

class MelhorEnvios extends \Magento\Shipping\Model\Carrier\AbstractCarrierOnline implements \Magento\Shipping\Model\Carrier\CarrierInterface
{
    const TRACKING_URL = "https://www.melhorrastreio.com.br/rastreio/";

    /**
     * @var string
     */
    protected $_code = 'melhorenvios';

    /**
     * @var \Lb\MelhorEnvios\Service\v2\MelhorEnviosService
     */
    protected $service;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    protected $additionalDaysForDelivery = 0;

    protected $freeShippingCarrierId;

    const MIN_LENGTH = 16;
    const MIN_WIDTH = 12;
    const MIN_HEIGHT = 2;

    /**
     * MelhorEnvios constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Xml\Security $xmlSecurity
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Shipping\Helper\Carrier $carrierHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $coreDate
     * @param \Magento\Framework\Module\Dir\Reader $configReader
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Math\Division $mathDivision
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Xml\Security $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Shipping\Helper\Carrier $carrierHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate,
        \Magento\Framework\Module\Dir\Reader $configReader,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Math\Division $mathDivision,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;

        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [
            'Correios_1' => 'Correios - PAC',
            'Correios_2' => 'Correios - SEDEX',
            'Jadlog_3' => 'Jadlog - Normal',
            'Jadlog_4' => 'Jadlog - Expresso',
            'Shippify_5' => 'Shippify - Expresso',
            'Jamef_7' => 'Jamef - Rodoviário',
        ];
    }

    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }


        $function = '/api/v2/me/shipment/calculate';
        $parameters = [
            'method' => \Zend\Http\Request::METHOD_GET,
            'data' => [
              "from" => [
                "postal_code" => $request->getPostcode(),
                "address" => $request->getCity(),
                "number" => $request->getRegion()
              ],
              "to" => [
                "postal_code" => $request->getDestPostcode(),
                "address" => $request->getDestRegionCode(),
                "number" => $request->getDestRegionId()
              ],
              "package" => $this->_createDimensions($request),
              "options" => [
                "insurance_value" => $request->getPackageValue(),
                "receipt" => false,
                "own_hand" => false,
                "collect" => false
              ],
              "services" => $this->getConfigData('availablemethods')
            ]
        ];

        $response = $this->getService()->doRequest($function, $parameters);
        $response = json_decode($response);

        if (empty($response)) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();

        $this->freeShippingCarrierId = $this->getConfigData('free_shipping_service');

        foreach ($response as $carrier) {
            if (isset($carrier->error)) {
                $this->getFreeShippingFallback($carrier);

                continue;
            }
            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
            $method = $this->_rateMethodFactory->create();

            $method->setCarrier($this->_code);

            $method->setCarrierTitle($this->getConfigData('name'));

            $method->setMethod($carrier->company->name . "_" . $carrier->id);
            $delivery_time = 0;
            $description = $carrier->company->name . " " .$carrier->name;
            if (property_exists($carrier, 'delivery_time')) {
              $delivery_time = ($this->additionalDaysForDelivery > 0 ? $this->additionalDaysForDelivery : $this->getConfigData('add_days'));
              $delivery_time += $carrier->delivery_time;

              $description = $carrier->company->name . " " .$carrier->name
              . sprintf($this->getConfigData('text_days'), $delivery_time);
            }
            $method->setMethodTitle($description);

            if ($this->getConfigData('free_shipping_enabled') &&
                $carrier->id == $this->freeShippingCarrierId
                && $request->getFreeShipping()
            ) {
                $carrier->price = 0;
                $carrier->discount = 0;

                $delivery_time += $this->getConfigData('add_days_free_shipping');
                $method->setMethodTitle($this->getConfigData('free_shipping_text') . sprintf($this->getConfigData('text_days'), $delivery_time));
            }

            $amount = $carrier->price;
            $method->setPrice($amount);
            $method->setCost($amount - $carrier->discount);

            $result->append($method);
        }

        return $result;
    }

    private function getFreeShippingFallback($carrier)
    {
        if ($carrier->id == $this->freeShippingCarrierId) {
            $this->freeShippingCarrierId = $this->getConfigData('free_shipping_service_fallback');
        }

    }

    /**
     * @param RateRequest $request
     * @return array
     */
    protected function _createDimensions(RateRequest $request)
    {
        $volume = 0;
        $items = $this->getItems($request->getAllItems());
        foreach($items as $item) {

            $qty = $item->getQty() ?: 1;
            $item = $item->getProduct();

            $this->additionalDaysForDelivery = (
              $this->additionalDaysForDelivery >= $this->_getShippingDimension($item, 'additional_days') ?
              $this->additionalDaysForDelivery : $this->_getShippingDimension($item, 'additional_days')
            );
            $volume += (
                $this->_getShippingDimension($item, 'height') *
                $this->_getShippingDimension($item, 'width') *
                $this->_getShippingDimension($item, 'length')
            ) * $qty;

        }

        $root_cubic = round(pow($volume, (1/3)));

        $width = ($root_cubic < self::MIN_WIDTH) ? self::MIN_WIDTH : $root_cubic;
        $height = ($root_cubic < self::MIN_HEIGHT) ? self::MIN_HEIGHT : $root_cubic;
        $length = ($root_cubic < self::MIN_LENGTH) ? self::MIN_LENGTH : $root_cubic;

        return [
            "weight" => $request->getPackageWeight(),
            "width" => $width,
            "height" => $height,
            "length" => $length
        ];
    }

    /**
     * Return items for further shipment rate evaluation. We need to pass children of a bundle instead passing the
     * bundle itself, otherwise we may not get a rate at all (e.g. when total weight of a bundle exceeds max weight
     * despite each item by itself is not)
     *
     * @return array
     */
    private function getItems($allItems)
    {
        $items = [];
        foreach ($allItems as $item) {
            /* @var $item Mage_Sales_Model_Quote_Item */
            if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                // Don't process children here - we will process (or already have processed) them below
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately()) {
                foreach ($item->getChildren() as $child) {
                    if (!$child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                        $items[] = $child;
                    }
                }
            } else {
                // Ship together - count compound item as one solid
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param $item
     * @param $type
     * @return int
     */
    public function _getShippingDimension($item, $type)
    {
        $attributeMapped = $this->getConfigData('attributesmapping');
        $attributeMapped = json_decode($attributeMapped, true) ?: unserialize($attributeMapped);
        $dimension = 0;
        $value = $item->getData($attributeMapped[$type]['attribute_code']);
        if ($value) {
            $dimension = $value;
        }

        return $dimension;
    }

    /**
     * @return MelhorEnviosService
     */
    public function getService() : MelhorEnviosService
    {
        if (!$this->service instanceof MelhorEnviosService) {
            $this->service = new MelhorEnviosService($this->getConfigData('token'), $this->_logger);
        }

        return $this->service;
    }

    /**
     * @return bool
     */
    public function isTrackingAvailable(){
        return true;
    }

    /**
     * @param string $number
     * @return \Magento\Shipping\Model\Tracking\Result\Status
     */
    public function getTrackingInfo($number)
    {

        $tracking = $this->_trackStatusFactory->create();
        $tracking->setCarrier($this->_code);
        $tracking->setCarrierTitle($this->getConfigData('name'));
        $tracking->setTracking($number);
        $tracking->setUrl(self::TRACKING_URL . $number);

        return $tracking;
    }

    /**
     * @param \Magento\Framework\DataObject $request
     * @return null;
     */

    protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        $this->setRequest($request);
    }
}
