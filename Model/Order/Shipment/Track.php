<?php

namespace Lb\MelhorEnvios\Model\Order\Shipment;

class Track extends \Magento\Sales\Model\Order\Shipment\Track
{
    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $package = $objectManager->create(\Lb\MelhorEnvios\Model\ShippingInformation::class)
            ->getCollection()
            ->addFieldToFilter('tracking_number', $this->getTrackNumber())
            ->fetchItem();

        if ($package) {
            $package->delete();
        }

        return parent::delete();
    }
}
