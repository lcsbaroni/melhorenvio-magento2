<?php
namespace Lb\MelhorEnvios\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Lb\MelhorEnvios\Service\v2\MelhorEnviosService;

class MelhorEnvios extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
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

    const MIN_LENGTH = 16;
    const MIN_WIDTH = 12;
    const MIN_HEIGHT = 2;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_logger = $logger;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['melhorenvios' => $this->getConfigData('name')];
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

        foreach ($response as $carrier) {
            if (isset($carrier->error)) {
                continue;
            }
            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
            $method = $this->_rateMethodFactory->create();

            $method->setCarrier('melhorenvios');

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
                $carrier->id == $this->getConfigData('free_shipping_service')
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

    /**
     * @param RateRequest $request
     * @return array
     */
    protected function _createDimensions(RateRequest $request)
    {
        $volume = 0;
        $items = $this->getAllItems($request->getAllItems());
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
    protected function getAllItems($allItems)
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
        $attributeMapped = unserialize($attributeMapped);

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
}
