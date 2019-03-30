<?php

namespace Lb\MelhorEnvios\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;

class ShippingInformation extends AbstractDb
{
    /**
     * Initialize table nad PK name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('melhor_envios_shipping_information', 'entity_id');
    }

    /**
     *  Load an object by id
     *
     * @return $this
     */
    public function loadById(\Lb\MelhorEnvios\Model\ShippingInformation $model, $id)
    {
        if ($id) {
            $this->load($model, $id);
        }

        return $this;
    }
}
