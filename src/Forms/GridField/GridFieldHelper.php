<?php

namespace LeKoala\Base\Forms\GridField;

use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Exception;

/**
 * Due to the dynamic nature of the config, your IDE will not recognize
 * classes. This helper helps you to get properly recognized components
 */
class GridFieldHelper
{
    /**
     * @param  $config
     * @return GridFieldFilterHeader
     */
    public static function getGridFieldFilterHeader(GridFieldConfig $config)
    {
        //@phpstan-ignore-next-line
        return $config->getComponentByType(GridFieldFilterHeader::class);
    }

    /**
     * @param GridFieldConfig $config
     * @return GridFieldDataColumns
     */
    public static function getGridFieldDataColumns(GridFieldConfig $config)
    {
        //@phpstan-ignore-next-line
        return $config->getComponentByType(GridFieldDataColumns::class);
    }

    /**
     * @param GridFieldConfig $config
     * @return GridFieldPaginator
     */
    public static function getGridFieldPaginator(GridFieldConfig $config)
    {
        //@phpstan-ignore-next-line
        return $config->getComponentByType(GridFieldPaginator::class);
    }

    /**
     * @param GridFieldConfig $config
     * @param GridField $gridField
     * @param array $fields
     * @return GridFieldEditableColumns
     */
    public static function makeEditableColumns(GridFieldConfig $config, GridField $gridField, $fields = [])
    {
        $editable = new GridFieldEditableColumns();
        $columns = self::getGridFieldDataColumns($config);
        $displayFields = $columns->getDisplayFields($gridField);
        $newDisplayFields = [];
        foreach ($displayFields as $field => $fieldTitle) {
            $newDisplayFields[$field] = [
                'title' => $fieldTitle,
                'field' => ReadonlyField::class,
            ];
        }
        foreach ($fields as $field) {
            $newDisplayFields[$field] =  $field;
        }
        $editable->setDisplayFields($newDisplayFields);
        $config->removeComponentsByType(GridFieldDataColumns::class);
        $config->addComponent($editable);
        return $editable;
    }

    public static function makeEmailClickable(GridFieldDataColumns $cols, $field = 'Email')
    {
        $formatting = $cols->getFieldFormatting();
        $formatting[$field] = function ($v, $item) {
            if (!$v || !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                return $v;
            }
            return '<a href="mailto:' . $v . '">' . $v . '</a>';
        };
        $cols->setFieldFormatting($formatting);
    }

    /**
     * @param GridField|\SilverStripe\Forms\FormField $gridField
     * @return GridFieldConfig
     */
    public static function getConfig($gridField)
    {
        if (!($gridField instanceof GridField)) {
            throw new Exception("Not a gridfield");
        }
        return $gridField->getConfig();
    }

    public static function makeSimpleGridField(GridField $gridField = null)
    {
        if (!$gridField) {
            return;
        }

        $config = self::getConfig($gridField);
        $config->removeComponentsByType(GridFieldAddNewButton::class);
        $config->removeComponentsByType(GridFieldAddExistingSearchButton::class);
        $config->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
        $config->removeComponentsByType(GridFieldFilterHeader::class);
    }
}
