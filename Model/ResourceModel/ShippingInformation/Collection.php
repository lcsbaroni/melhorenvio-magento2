<?php

namespace Lb\MelhorEnvios\Model\ResourceModel\ShippingInformation;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Lb\MelhorEnvios\Model\ShippingInformation', 'Lb\MelhorEnvios\Model\ResourceModel\ShippingInformation');
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }
}
