<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\RegisterHandler;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\DataObject;
use Magento\Framework\Reflection\FieldNamer;

class Converter
{
    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var FieldNamer
     */
    protected $fieldNamer;

    /**
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param FieldNamer $fieldNamer
     */
    public function __construct(
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        FieldNamer $fieldNamer
    ) {
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->fieldNamer = $fieldNamer;
    }

    /**
     * Convert array data
     *
     * @param array $data
     *
     * @return array
     */
    public function convert(array $data): array
    {
        $result = [];
        foreach ($data as $key => $item) {
            if (is_scalar($item)) {
                $result[$key] = $item;
            }
            if (is_array($item)) {
                $result[$key] = (new DataObject($item))->debug();
                continue;
            }
            if (is_object($item)) {
                $result[$key] = $this->convertObjectToArray($item);
                continue;
            }

            if (is_string($key)) {
                throw new \LogicException(sprintf('Unable to convert data for "%s"', $key));
            } else {
                throw new \LogicException('Unable to convert data');
            }
        }

        return $result;
    }

    /**
     * Convert object to array
     *
     * @param $object
     *
     * @return array
     */
    protected function convertObjectToArray($object)
    {
        if (!is_object($object)) {
            throw new \LogicException(sprintf('Convert failed because no object was passed'));
        }
        if ($object instanceof DataObject) {
            return $this->convertDataObjectToArray($object);
        }
        if ($object instanceof ExtensibleDataInterface) {
            return $this->convertExtensibleDataToArray($object);
        }

        throw new \LogicException(sprintf('Unable to convert "%s" object to array', get_class($object)));
    }

    /**
     * Convert DataObject to array
     *
     * @param DataObject $object
     *
     * @return array
     */
    protected function convertDataObjectToArray(DataObject $object): array
    {
        $data = $object->debug();
        if (!$object instanceof ExtensibleDataInterface) {
            return $data;
        }
        $entityExtension = $object->getExtensionAttributes();
        if (empty($entityExtension)) {
            return $data;
        }
        foreach (get_class_methods($entityExtension) as $method) {
            $fieldName = $this->fieldNamer->getFieldNameForMethodName($method);
            if (empty($fieldName)) {
                continue;
            }
            $value = $entityExtension->$method();
            if (!is_scalar($value)) {
                continue;
            }
            $data[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY][$fieldName] = $value;
        }

        return $data;
    }

    /**
     * Convert ExtensibleData object to array
     *
     * @param ExtensibleDataInterface $object
     *
     * @return array
     */
    protected function convertExtensibleDataToArray(ExtensibleDataInterface $object): array
    {
        return $this->extensibleDataObjectConverter
            ->toNestedArray($object, [], get_class($object));
    }
}
