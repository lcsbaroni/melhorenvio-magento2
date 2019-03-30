<?php
namespace Lb\MelhorEnvios\Model;

use Magento\Framework\Model\AbstractModel;

class ShippingInformation extends AbstractModel
{

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ResourceModel\ShippingInformation $resource = null,
        ResourceModel\ShippingInformation\Collection $resourceCollection = null,
        array $data = []
    ) {

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    protected function _construct()
    {
        $this->_init('Lb\MelhorEnvios\Model\ResourceModel\ShippingInformation');
        $this->setIdFieldName('entity_id');
    }
}
