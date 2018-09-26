<?php
namespace Lb\MelhorEnvios\Block\Adminhtml\System\Config\Fieldset;

/**
 * Class Mapping
 *
 * @package Lb\MelhorEnvios\Block\Adminhtml\System\Config\Fieldset
 */
class Mapping
    extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    private $attributeCollection;

    /**
     * Mapping constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $attributeCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $attributeCollection,
        array $data = []
    )
    {
        $this->addColumn('melhorenvios', array(
            'label' => __('MelhorEnvíos'),
            'style' => 'width:120px',
        ));
        $this->addColumn('magentoproduct', array(
            'label' => __('Product Attribute'),
            'style' => 'width:120px',
        ));

        $this->addColumn('unit', array(
            'label' => __('Attribute Unit'),
            'style' => 'width:120px',
        ));

        $this->setTemplate('array_dropdown.phtml');
        $this->attributeCollection = $attributeCollection;

        parent::__construct($context);
    }

    /**
     * @return $this
     */
    public function _getAttributes()
    {
        $attributes = $this->attributeCollection
            ->addFieldToFilter('is_visible', 1)
            ->addFieldToFilter('frontend_input', ['nin' => ['boolean', 'date', 'datetime', 'gallery', 'image', 'media_image', 'select', 'multiselect', 'textarea']])
            ->load();


        return $attributes;
    }

    /**
     * @return array
     */
    public function _getStoredMappingValues()
    {
        $prevValues = [];
        foreach ($this->getArrayRows() as $key => $_row) {
            $prevValues[$key] = ['attribute_code' => $_row->getData('attribute_code'), 'unit' => $_row->getData('unit')];
        }

        return $prevValues;
    }

    /**
     * @return array
     */
    public function _getMeLabel()
    {
        return [__('Length'), __('Width'), __('Height'), __('Weight'), __('Additional days')];
    }
}
