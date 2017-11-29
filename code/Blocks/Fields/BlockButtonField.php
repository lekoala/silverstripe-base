<?php
namespace LeKoala\Base\Blocks\Fields;

use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;

class BlockButtonField extends CompositeField
{
    protected $titleField;
    protected $linkField;
    protected $newWindowField;

    public function __construct($name, $title = null, $value = null)
    {
        $children = new FieldList();

        $titleField = TextField::create(
            "{$name}[Title]",
            'Title'
        );
        $children->push($titleField);

        $linkField = TextField::create(
            "{$name}[Link]",
            'Link'
        );
        $children->push($linkField);


        $newWindowField = CheckboxField::create(
            "{$name}[NewWindow]",
            'Open in new window?'
        );
        $children->push($newWindowField);

        parent::__construct($children);

        $this->setName($name);

        if ($title === null) {
            $this->title = self::name_to_label($name);
        } else {
            $this->title = $title;
        }

        if ($value !== null) {
            $this->setValue($value);
        }
    }


    public function setValue($value, $data = null)
    {
        if (is_array($value)) {
            if (isset($value['Title'])) {
                $this->getTitleField()->setValue($value['Title']);
            }
            if (isset($value['Link'])) {
                $this->getLinkField()->setValue($value['Link']);
            }
            if (isset($value['NewWindow'])) {
                $this->getNewWindowField()->setValue($value['NewWindow']);
            }
        }
        return parent::setValue($value, $data);
    }

    public function getTitleField()
    {
        $name = $this->name;
        return $this->fieldByName("{$name}[Title]");
    }
    public function getLinkField()
    {
        $name = $this->name;
        return $this->fieldByName("{$name}[Link]");
    }
    public function getNewWindowField()
    {
        $name = $this->name;
        return $this->fieldByName("{$name}[NewWindow]");
    }

}
