<?php

namespace Lb\MelhorEnvios\Plugin;

class PrintShippingLabelPlugin
{
    public function afterGetPrintLabelButton(\Magento\Shipping\Block\Adminhtml\View\Form $subject)
    {
        $shippinhMethod = $subject->getShipment()->getOrder()->getShippingMethod();

        if (strstr($shippinhMethod, \Lb\MelhorEnvios\Model\Carrier\MelhorEnvios::CODE)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $package = $objectManager->create(\Lb\MelhorEnvios\Model\ShippingInformation::class)
                ->getCollection()
                ->addFieldToFilter('increment_id', $subject->getShipment()->getIncrementId())
                ->addFieldToFilter('shipping_id', $subject->getShipment()->getId())
                ->fetchItem();

            $url = 'https://melhorenvio.com.br';
            if ($package) {
                $url = $package->getLabelUrl();
            }

            return $subject->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Button'
            )->setData(
                ['label' => __('Print Shipping Label'), 'onclick' => 'window.open(\'' . $url . '\')']
            )->toHtml();
        }

        return $subject;
    }
}
