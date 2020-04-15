<?php
namespace Lb\MelhorEnvios\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Lb\MelhorEnvios\Service\v2\MelhorEnviosService;

class MelhorEnvios extends \Magento\Shipping\Model\Carrier\AbstractCarrierOnline implements \Magento\Shipping\Model\Carrier\CarrierInterface
{
    const TRACKING_URL = "https://www.melhorrastreio.com.br/rastreio/";

    const CODE = "melhorenvios";

    /**
     * @var string
     */
    protected $_code = self::CODE;

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

    protected $shippingInformation;

    const MIN_LENGTH = 16;
    const MIN_WIDTH = 12;
    const MIN_HEIGHT = 2;
    const INSURANCE_VALUE = 5.00;

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
        \Lb\MelhorEnvios\Model\ShippingInformation $ShippingInformation,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->shippingInformation = $ShippingInformation;

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
                "insurance_value" => (bool) $this->getConfigData('insurance_value') ? $request->getPackageValue() : self::INSURANCE_VALUE, // valor declarado/segurado
                "receipt" => (bool) $this->getConfigData('receipt'), // aviso de recebimento
                "own_hand" => (bool) $this->getConfigData('own_hand'), // mão pŕopria
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
              $delivery_time = ($this->additionalDaysForDelivery > $this->getConfigData('add_days') ? $this->additionalDaysForDelivery : $this->getConfigData('add_days'));
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
            $this->service = new MelhorEnviosService(
                $this->getConfigData('token'),
                $this->getConfigData('environment'),
                $this->_logger
            );
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
     * @param $taxVat
     * @return string
     */
    private function getCustomerTaxVat($taxVat)
    {
        //if customer doesn't have document, use a fake. Required for Jadlog
        if (!$taxVat) {
            $taxVat = '85117687183'; //fake document
        }

        return $taxVat;
    }

    /**
     * @param array $street
     * @return array
     */
    private function buildAddress(array $street) : array
    {
        $keys = [
            'street',
            'number',
            'complement',
            'neighborhood'
        ];

        if (sizeof($keys) == sizeof($street)) {
            $result = array_combine($keys, $street);
        } else {
            $result = [
                'street' => $street[0],
                'number' => $street[1],
                'complement' => '',
                'neighborhood' => $street[2]
            ];
        }

        return $result;
    }

    /**
     * @param array $items
     * @return float
     */
    private function getInsuranceValue(array $items) : float
    {
        $value = self::INSURANCE_VALUE;
        if ($this->getConfigData('insurance_value')) {
            $value = 0.00;
            foreach ($items as $item) {
                $value += $item['price'];
            }
        }

        return $value;
    }

    /**
     * @param \Magento\Framework\DataObject $request
     * @return \Magento\Framework\DataObject
     */
    protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        $data = [
            'increment_id' => $request->getOrderShipment()->getIncrementId(),
            'shipping_id' => $request->getOrderShipment()->getId(),
            'package_id' => $request->getData('package_id'),
            'tracking_number' => '',
            'label_url' => ''
        ];

        $result = new \Magento\Framework\DataObject();

        $customerTaxVat = $this->getCustomerTaxVat($request->getOrderShipment()->getOrder()->getCustomerTaxVat());
        $shippingAddress = $request->getOrderShipment()->getOrder()->getShippingAddress();
        $shippingAddressParsed = $this->buildAddress($shippingAddress->getStreet());

        $addressFrom = explode(',', $request->getShipperAddressStreet1());
        $addressFrom[] = $request->getShipperAddressStreet2();
        $addressFrom = $this->buildAddress($addressFrom);

        $function = '/api/v2/me/cart';
        $parameters = [
            'method' => \Zend\Http\Request::METHOD_POST,
            'data' => [
                "service" => explode('_', $request->getShippingMethod())[1],
                "agency" => $this->getConfigData('jadlog_agency'), // id da agência de postagem (obrigatório se for JadLog)
                "from" => [
                    "name" => $request->getShipperContactCompanyName(),
                    "phone" => $request->getShipperContactPhoneNumber(),
                    "document" => strlen($this->getConfigData('taxvat')) > 11 ? '' : $this->getConfigData('taxvat'),
                    "company_document" => strlen($this->getConfigData('taxvat')) < 11 ? '' : $this->getConfigData('taxvat'), // cnpj (obrigatório se não for Correios)
                    "state_register" => $this->getConfigData('state_register'), // inscrição estadual (obrigatório se não for Correios) pode ser informado "isento"
                    "postal_code" => $request->getShipperAddressPostalCode(),
                    "address" => $addressFrom['street'],
                    "complement" => $addressFrom['complement'],
                    "number" => $addressFrom['number'],
                    "district" => $addressFrom['neighborhood'],
                    "city" => $request->getShipperAddressCity(),
                    "state_abbr" => $request->getShipperAddressStateOrProvinceCode(),
                    "country_id" => $request->getShipperAddressCountryCode(),
                ],
                "to" => [
                    "name" => $request->getRecipientContactPersonName(),
                    "phone" => $request->getRecipientContactPhoneNumber(), // telefone com ddd (obrigatório se não for Correios)
                    "email" => $request->getData('recipient_email'),
                    "document" => $customerTaxVat, // obrigatório se for transportadora e não for logística reversa
                    "company_document" => "", // (opcional) (a menos que seja transportadora e logística reversa)
                    "state_register" => "", // (opcional) (a menos que seja transportadora e logística reversa)
                    "address" => $shippingAddressParsed['street'],
                    "complement" => $shippingAddressParsed['complement'],
                    "number" => $shippingAddressParsed['number'],
                    "district" => $shippingAddressParsed['neighborhood'],
                    "city" => $request->getRecipientAddressCity(),
                    "state_abbr" => $request->getRecipientAddressStateOrProvinceCode(),
                    "country_id" => $request->getRecipientAddressCountryCode(),
                    "postal_code" => $request->getRecipientAddressPostalCode(),
                ],
                "products" => $this->getListProducts($request->getData('package_items')),
                "package" => $this->getShippingPackages($request->getData('package_params')),
                "options" => [ // opções
                    "insurance_value" => $this->getInsuranceValue($request->getPackageItems()), // valor declarado/segurado
                    "receipt" => (bool) $this->getConfigData('receipt'), // aviso de recebimento
                    "own_hand" => (bool) $this->getConfigData('own_hand'), // mão pŕopria
                    "collect" => false, // coleta
                    "reverse" => false, // logística reversa (se for reversa = true, ainda sim from será o remetente e to o destinatário)
                    "non_commercial" => true, // envio de objeto não comercializável (flexibiliza a necessidade de pessoas júridicas para envios com transportadoras como Latam Cargo, porém se for um envio comercializável a mercadoria pode ser confisca pelo fisco)
                ]
            ]
        ];

        $this->_prepareShipmentRequest($request);

        try {
            $response = $this->getService()->doRequest($function, $parameters);
            $response = json_decode($response);
        } catch (\Exception $e) {
            return $result->setErrors("Não foi possível adicionar a etiqueta ao carrinho - " . $e->getMessage());
        }

        $shippingOrderID = $response->id;

        $function = "/api/v2/me/shipment/checkout";
        $parameters = [
            'method' => \Zend\Http\Request::METHOD_POST,
            'data' => [
                "orders" => [ // lista de etiquetas (opcional)
                    $shippingOrderID
                ],
                "wallet" => $response->price
            ]
        ];

        try {
            $response = $this->getService()->doRequest($function, $parameters);
            $response = json_decode($response);
        } catch (\Exception $e) {
            return $result->setErrors("Não foi possível comprar a etiqueta - " . $e->getMessage());
        }

        $function = "/api/v2/me/shipment/generate";
        $parameters = [
            'method' => \Zend\Http\Request::METHOD_POST,
            'data' => [
                "orders" => [
                    $shippingOrderID
                ]
            ]
        ];

        try {
            $response = $this->getService()->doRequest($function, $parameters);
            $response = json_decode($response);
        } catch (\Exception $e) {
            return $result->setErrors("Não foi possível gerar a etiqueta - " . $e->getMessage());
        }

        $function = "/api/v2/me/shipment/print";
        $parameters = [
            'method' => \Zend\Http\Request::METHOD_POST,
            'data' => [
                "mode" => "public",
                "orders" => [
                    $shippingOrderID
                ]
            ]
        ];

        try {
            $response = $this->getService()->doRequest($function, $parameters);
            $response = json_decode($response);
        } catch (\Exception $e) {
            return $result->setErrors("Não foi possível imprimir a etiqueta - " . $e->getMessage());
        }

        $labelUrl = $response->url;

        $function = "/api/v2/me/shipment/tracking";
        $parameters = [
            'method' => \Zend\Http\Request::METHOD_POST,
            'data' => [
                "orders" => [
                    $shippingOrderID
                ]
            ]
        ];

        try {
            $response = $this->getService()->doRequest($function, $parameters);
            $response = json_decode($response);
        } catch (\Exception $e) {
            return $result->setErrors("Não foi possível encontrar o tracking - " . $e->getMessage());
        }

        $result->setShippingLabelContent($labelUrl);
        $result->setTrackingNumber($response->$shippingOrderID->tracking);
        $data['tracking_number'] = $response->$shippingOrderID->tracking;
        $data['label_url'] = $labelUrl;

        $this->shippingInformation->setData($data)->save();

        return $result;
    }

    /**
     * @param array $packageItems
     * @return array
     */
    private function getListProducts(array $packageItems) : array
    {
        $products = [];

        // lista de produtos para preenchimento da declaração de conteúdo
        foreach ($packageItems as $item) {
            $products[] = [
                "name" => $item['name'], // nome do produto (max 255 caracteres)
                "quantity" => $item['qty'], // quantidade de items desse produto
                "unitary_value" => $item['price'], // R$ 4,50 valor do produto
                "weight" => $item['weight'], // peso 1kg, opcional
            ];
        }

        return $products;
    }

    /**
     * @param $packageParams
     * @return array
     */
    private function getShippingPackages($packageParams) : array
    {
        $package = [];

        foreach ($packageParams as $package) {
            $package = [
                "weight" => $package['weight'],
                "width" => $package['width'],
                "height" => $package['height'],
                "length" => $package['length']
            ];
        }

        return $package;
    }
}
