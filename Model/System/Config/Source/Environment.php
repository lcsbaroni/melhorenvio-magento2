<?php
namespace Lb\MelhorEnvios\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

final class Environment implements ArrayInterface
{
    /**
     * PRODUCTION type code
     * @const string
     */
    const PRODUCTION = 'production';
    /**
     * SANDBOX type code
     * @const string
     */
    const SANDBOX = 'sandbox';
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Production'),
                'value' => self::PRODUCTION
            ],
            [
                'label' => __('Sandbox'),
                'value' => self::SANDBOX
            ]
        ];
    }
}
