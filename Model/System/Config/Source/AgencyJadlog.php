<?php
namespace Lb\MelhorEnvios\Model\System\Config\Source;

use Lb\MelhorEnvios\Service\v2\MelhorEnviosService;

/**
 * Class Method
 *
 * @package Lb\MelhorEnvios\Model\System\Config\Source
 */
class AgencyJadlog
    implements \Magento\Framework\Option\ArrayInterface
{
    const JADLOG_COMPANY_ID = 2;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Lb\MelhorEnvios\Service\v2\MelhorEnviosService
     */
    protected $service;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function toOptionArray() {
        $function = '/api/v2/me/shipment/agencies?company='. self::JADLOG_COMPANY_ID . '&country=BR';
        $parameters = [
            'method' => \Zend\Http\Request::METHOD_GET,
            'data' => []
        ];

        $response = $this->getService()->doRequest($function, $parameters);
        $response = json_decode($response);

        if (empty($response)) {
            return [];
        }

        $result = [];
        foreach ($response as $item) {
            if ($item->status != 'available') {
                continue;
            }

            $result[$item->id] = $item->name . ' - ' . $item->address->address;
        }

        asort($result);

        return $result;
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
                $this->logger
            );
        }

        return $this->service;
    }

    /**
     * @param string $field
     * @return mixed
     */
    private function getConfigData(string $field)
    {
        $path = 'carriers/melhorenvios/' . $field;

        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

}
