<?php

namespace LeKoala\Base\Forms\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;

/**
 * Utilities for fields
 *
 * @property \LeKoala\Base\Actions\CustomAction|\LeKoala\Base\Actions\CustomLink|\LeKoala\Base\Forms\AgGridField|\LeKoala\Base\Forms\AlertField|\LeKoala\Base\Forms\AllCheckboxSetField|\LeKoala\Base\Forms\BaseFileUploadField|\LeKoala\Base\Forms\BetterCheckboxSetField|\LeKoala\Base\Forms\CmsInlineFormAction|\LeKoala\Base\Forms\CmsInlineModal|\LeKoala\Base\Forms\ColorField|\LeKoala\Base\Forms\ColumnsField|\LeKoala\Base\Forms\CountryDropdownField|\LeKoala\Base\Forms\CountryPhoneField|\LeKoala\Base\Forms\FilePondField|\LeKoala\Base\Forms\FineUploadField|\LeKoala\Base\Forms\FlatpickrField|\LeKoala\Base\Forms\GoogleRecaptchaField|\LeKoala\Base\Forms\InputMaskCurrencyField|\LeKoala\Base\Forms\InputMaskCurrencyField_Readonly|\LeKoala\Base\Forms\InputMaskDateField|\LeKoala\Base\Forms\InputMaskDateTimeField|\LeKoala\Base\Forms\InputMaskField|\LeKoala\Base\Forms\InputMaskIntegerField|\LeKoala\Base\Forms\InputMaskNumericField|\LeKoala\Base\Forms\InputMaskPercentageField|\LeKoala\Base\Forms\InputMaskTimeField|\LeKoala\Base\Forms\InputMaskUrlField|\LeKoala\Base\Forms\JsonFormField|\LeKoala\Base\Forms\LiteralBlockField|\LeKoala\Base\Forms\NumericReadonlyField|\LeKoala\Base\Forms\PhoneField|\LeKoala\Base\Forms\RangeField|\LeKoala\Base\Forms\Select2MultiField|\LeKoala\Base\Forms\Select2SingleField|\LeKoala\Base\Forms\SimpleHasOneButtonField|\LeKoala\Base\Forms\SimpleHeaderField|\LeKoala\Base\Forms\SmartSortableUploadField|\LeKoala\Base\Forms\SmartUploadField|\LeKoala\Base\Forms\TimezoneDropdown|\LeKoala\Base\Forms\YesNoOptionsetField|\LeKoala\Base\Blocks\Fields\BlockButtonField|\LeKoala\Base\Blocks\Fields\BlockHTMLEditorField|\LeKoala\Base\Forms\Bootstrap\Tab|\LeKoala\Base\Forms\Bootstrap\TabSet|\LeKoala\Base\Forms\FullGridField\FullGridField|\SilverShop\HasOneField\HasOneButtonField|\SilverStripe\CampaignAdmin\AddToCampaignHandler_FormAction|\SilverStripe\CampaignAdmin\CampaignAdminList|\Bummzack\SortableFile\Forms\SortableUploadField|\JonoM\FocusPoint\Forms\FocusPointField|\SilverStripe\Admin\Forms\UsedOnTable|\SilverStripe\AssetAdmin\Forms\HistoryListField|\SilverStripe\AssetAdmin\Forms\PreviewImageField|\SilverStripe\AssetAdmin\Forms\UploadField|\SilverStripe\CMS\Forms\AnchorSelectorField|\SilverStripe\CMS\Forms\SiteTreeURLSegmentField|\SilverStripe\CMS\Forms\SiteTreeURLSegmentField_Readonly|\SilverStripe\Forms\CheckboxField|\SilverStripe\Forms\CheckboxField_Readonly|\SilverStripe\Forms\CheckboxSetField|\SilverStripe\Forms\CompositeField|\SilverStripe\Forms\ConfirmedPasswordField|\SilverStripe\Forms\CurrencyField|\SilverStripe\Forms\CurrencyField_Disabled|\SilverStripe\Forms\CurrencyField_Readonly|\SilverStripe\Forms\DatalessField|\SilverStripe\Forms\DateField|\SilverStripe\Forms\DateField_Disabled|\SilverStripe\Forms\DatetimeField|\SilverStripe\Forms\DropdownField|\SilverStripe\Forms\EmailField|\SilverStripe\Forms\FieldGroup|\SilverStripe\Forms\FileField|\SilverStripe\Forms\FormAction|\SilverStripe\Forms\FormField|\SilverStripe\Forms\GroupedDropdownField|\SilverStripe\Forms\HTMLReadonlyField|\SilverStripe\Forms\HeaderField|\SilverStripe\Forms\HiddenField|\SilverStripe\Forms\LabelField|\SilverStripe\Forms\ListboxField|\SilverStripe\Forms\LiteralField|\SilverStripe\Forms\LookupField|\SilverStripe\Forms\MoneyField|\SilverStripe\Forms\MultiSelectField|\SilverStripe\Forms\NullableField|\SilverStripe\Forms\NumericField|\SilverStripe\Forms\OptionsetField|\SilverStripe\Forms\PasswordField|\SilverStripe\Forms\PopoverField|\SilverStripe\Forms\PrintableTransformation_TabSet|\SilverStripe\Forms\ReadonlyField|\SilverStripe\Forms\SelectField|\SilverStripe\Forms\SelectionGroup|\SilverStripe\Forms\SelectionGroup_Item|\SilverStripe\Forms\SingleLookupField|\SilverStripe\Forms\SingleSelectField|\SilverStripe\Forms\Tab|\SilverStripe\Forms\TabSet|\SilverStripe\Forms\TextField|\SilverStripe\Forms\TextareaField|\SilverStripe\Forms\TimeField|\SilverStripe\Forms\TimeField_Readonly|\SilverStripe\Forms\ToggleCompositeField|\SilverStripe\Forms\TreeDropdownField|\SilverStripe\Forms\TreeDropdownField_Readonly|\SilverStripe\Forms\TreeMultiselectField|\SilverStripe\Forms\TreeMultiselectField_Readonly|\SilverStripe\Security\PermissionCheckboxSetField|\SilverStripe\Security\PermissionCheckboxSetField_Readonly|\SilverStripe\VersionedAdmin\Forms\DiffField|\SilverStripe\VersionedAdmin\Forms\HistoryViewerField|\UncleCheese\DisplayLogic\Forms\Wrapper|\SilverStripe\Forms\GridField\GridField|\SilverStripe\Forms\GridField\GridField_FormAction|\SilverStripe\Forms\GridField\GridState|\SilverStripe\Forms\HTMLEditor\HTMLEditorField|\SilverStripe\Forms\HTMLEditor\HTMLEditorField_Readonly|\LeKoala\Base\Forms\Extensions\BaseFieldExtension $owner
 */
class BaseFieldExtension extends Extension
{
    /**
     * Prevent ugly autocomplete to fill in emails and passwords
     * in your form fields
     *
     * @return void
     */
    public function preventAutocomplete()
    {
        $this->owner->setAttribute('autocomplete', 'new-password');
        $this->owner->setAttribute('readonly', 'readonly');
        $this->owner->setAttribute('onfocus', "this.removeAttribute('readonly');");
    }

    /**
     * Get tooltip (title attr)
     *
     * @return string
     */
    public function getTooltip()
    {
        return $this->owner->getAttribute('title');
    }

    /**
     * Set tooltip (as title attr)
     *
     * @param string $value
     * @return FormField
     */
    public function setTooltip($value)
    {
        $this->owner->setAttribute('title', $value);
        $this->owner->setAttribute('data-toggle', 'tooltip');
        //TODO: figure out why the javascript is not properly triggered
        return $this->owner;
    }
}
