<?php
namespace Lb\MelhorEnvios\Model\Adminhtml\Attribute\Validation;


/**
 * Class Mapping
 *
 * @package Lb\MelhorEnvios\Model\Adminhtml\Attribute\Validation
 */
class Mapping
    extends \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
{

    protected $_scopeCode;

    /**
     * Mapping constructor.
     *
     * @param \Magento\Backend\Block\Store\Switcher                        $switcher
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $config
     * @param \Magento\Framework\App\Cache\TypeListInterface               $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Backend\Block\Store\Switcher                   $switcher,
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface      $config,
        \Magento\Framework\App\Cache\TypeListInterface          $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->_scopeCode = $switcher->getWebsiteId();
    }

    /**
     * Validates attribute mapping entries
     * @return $this
     * @throws \Exception
     */
    public function save()
    {
        $mappingValues = (array)$this->getValue(); //get the value from our config
        $attributeCodes = [];
        if ($this->_config->getValue('carriers/melhorenvios/active',\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->_scopeCode)) {
            foreach ($mappingValues as $value) {
                if (in_array($value['attribute_code'], $attributeCodes)) {
                    throw new \Exception(__('Cannot repeat Magento Product size attributes'));
                }

                $attributeCodes[] = $value['attribute_code'];
            }
        }

        return parent::save();
    }

}
