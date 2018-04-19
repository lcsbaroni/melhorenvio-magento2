<?php
namespace Lb\MelhorEnvios\Model\System\Config\Source;

/**
 * Class Method
 *
 * @package Lb\MelhorEnvios\Model\System\Config\Source
 */
class Method
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */

    protected $_methodsOptions = [
        ['value' => 1, 'label' => 'Correios - PAC'],
        ['value' => 2, 'label' => 'Correios - Sedex'],
        ['value' => 3, 'label' => 'Jadlog - Normal'],
        ['value' => 4, 'label' => 'Jadlog - Expresso'],
        ['value' => 5, 'label' => 'Shippify - Expresso'],
        ['value' => 7, 'label' => 'Jamef - Rodoviário'],
    ];

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Return key sorted shop item categories
     * @return array
     */
    public function toOptionArray()
    {
        if (isset($this->_methodsOptions)) {
            return $this->_methodsOptions;
        }
        return [];
    }

    /**
     * @return array
     */
    public function getAvailableCodes() {
        $methods = $this->toOptionArray();
        $codes = [];
        foreach ($methods as $method) {
            $codes[] = $method['value'];
        }
        return $codes;
    }

}
