<?php

namespace LeKoala\Base\Admin;

use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataObject;
use SilverStripe\Admin\ModelAdmin;
use LeKoala\Base\Subsite\SubsiteHelper;
use SilverStripe\Forms\GridField\GridField;
use LeKoala\Base\Helpers\ClassHelper;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;

/**
 * Improved ModelAdmin
 * - Built in subsite support
 * - Helpers
 */
abstract class BaseModelAdmin extends ModelAdmin
{
    /**
     * Do not delete from list by default
     *
     * @config
     * @var boolean
     */
    private static $can_delete_from_list = false;

    /**
     * @return int
     */
    public function getSubsiteId()
    {
        return SubsiteHelper::currentSubsiteID();
    }

    public static function getRequiredPermissions()
    {
        // This is needed to avoid BaseModelAdmin to be displayed as a valid permission
        if (get_called_class() == self::class) {
            return false;
        }
        return parent::getRequiredPermissions();
    }

    /**
     * Get the record asked by CustomLink or CMSInlineAction
     *
     * @return bool|DataObject
     */
    public function getRequestedRecord()
    {
        $request = $this->getRequest();
        $ModelClass = $request->getVar('ModelClass');
        $ID = $request->getVar('ID');
        if ($ID) {
            return DataObject::get_by_id($ModelClass, $ID);
        }
        return false;
    }

    public function getList()
    {
        $list = parent::getList();
        $singl = singleton($this->modelClass);
        $config = $singl->config();

        // Sort by custom sort order
        if ($config->model_admin_sort) {
            $list = $list->sort($config->model_admin_sort);
        }

        return $list;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        $gridField = $this->getGridField($form);
        $gridField->getConfig()->removeComponentsByType(GridFieldDeleteAction::class);
        if (self::config()->can_delete_from_list) {
            $gridField->getConfig()->addComponent(new GridFieldDeleteAction(false));
        }

        return $form;
    }

    /**
     * @return string
     */
    protected function getSanitisedModelClass()
    {
        return $this->sanitiseClassName($this->modelClass);
    }

    /**
     * Get gridfield for current model
     * Makes it easy for your ide
     *
     * @param Form $form
     * @return GridField
     */
    public function getGridField(Form $form)
    {
        return $form->Fields()->dataFieldByName($this->getSanitisedModelClass());
    }

    /**
     * Render a dialog
     *
     * @param array $customFields
     * @return string
     */
    protected function renderDialog($customFields = null)
    {
        // Set empty content by default otherwise it will render the full page
        if (empty($customFields['Content'])) {
            $customFields['Content'] = '';
        }
        return $this->renderWith('Silverstripe\\Admin\\CMSDialog', $customFields);
    }

    /**
     * @param DataObject|ArrayData $record
     * @return string
     */
    public static function getEditLink($record)
    {
        $URLSegment = static::config()->url_segment;
        $recordClass = $record->ClassName;
        $sanitisedClass = ClassHelper::sanitiseClassName($recordClass);
        $ID = $record->ID;
        return "/admin/$URLSegment/$sanitisedClass/EditForm/field/$sanitisedClass/item/$ID/edit";
    }
}
