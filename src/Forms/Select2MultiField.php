<?php
namespace LeKoala\Base\Forms;

use SilverStripe\ORM\Relation;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Forms\ListboxField;

/**
 * @link https://select2.org
 */
class Select2MultiField extends ListboxField
{
    use Select2;
    use ConfigurableField;

    /**
     * @param DataObject|DataObjectInterface $record The record to save into
     */
    public function saveInto(DataObjectInterface $record)
    {
        $fieldName = $this->getName();
        if (empty($fieldName) || empty($record)) {
            return;
        }

        /* @var $relation Relation */
        $relation = $record->hasMethod($fieldName) ? $record->$fieldName() : null;

        // Detect DB relation or field
        $items = $this->getValueArray();
        if ($relation && $relation instanceof Relation) {
            foreach ($items as $idx => $item) {
                // If the item is a string, it's not an ID and needs to be created
                if (!is_numeric($item)) {
                    $cb = $this->onNewTag;
                    if (!$cb) {
                        throw new Exception("Please define a onNewTag callback");
                    }
                    $items[$idx] = $cb($item);
                }
            }
            // Save ids into relation
            $relation->setByIDList(array_values($items));
        } elseif ($record->hasField($fieldName)) {
            // Save dataValue into field
            $record->$fieldName = $this->stringEncode($items);
        }
    }
}
