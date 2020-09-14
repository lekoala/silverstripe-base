<?php

namespace LeKoala\Base\Dev;

use PageController;
use SilverStripe\Forms\Form;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\DateTimeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\CheckboxSetField;

/**
 * A typography test page
 *
 * @link https://github.com/axllent/silverstripe-typography/blob/master/src/TypographyController.php
 * @link https://github.com/sunnysideup/silverstripe-typography
 */
class TypographyController extends PageController
{
    public function index(HTTPRequest $request = null)
    {
        $this->Title = 'Typography test page';
        $this->ExtraMeta .= '<meta name="robots" content="noindex, nofollow" />';

        return $this->renderWith(array('Typography', 'Page'));
    }
    public function RandomImage()
    {
        return Image::get()->sort('RAND()')->first();
    }
    public function TypoForm()
    {
        $array = array('green', 'yellow', 'blue', 'pink', 'orange');
        $form = new Form(
            $this,
            'TestForm',
            $fields = FieldList::create(
                HeaderField::create('HeaderField1', 'HeaderField Level 1', 1),
                LiteralField::create('LiteralField', '<p>All fields up to EmailField are required and should be marked as such</p>'),
                TextField::create('TextField1', 'Text Field Example 1'),
                TextField::create('TextField2', 'Text Field Example 2'),
                TextField::create('TextField3', 'Text Field Example 3'),
                TextField::create('TextField4', ''),
                HeaderField::create('FieldGroupHdr', 'First/last name FieldGroup'),
                FieldGroup::create(
                    TextField::create('FirstName1', 'First Name'),
                    TextField::create('LastName1', 'Last Name')
                ),
                HeaderField::create('HeaderField2b', 'Field with right title', 2),
                TextareaField::create('TextareaField', 'Textarea Field')
                    ->setColumns(45)
                    ->setRightTitle('This is the right title'),
                EmailField::create('EmailField', 'Email address'),
                HeaderField::create('HeaderField2c', 'HeaderField Level 2', 2),
                DropdownField::create('DropdownField', 'Dropdown Field', array(0 => '-- please select --', 1 => 'test AAAA', 2 => 'test BBBB')),
                OptionsetField::create('OptionSF', 'Optionset Field', $array),
                CheckboxSetField::create('CheckboxSF', 'Checkbox Set Field', $array),
                CurrencyField::create('CurrencyField', 'Bling bling', '$123.45'),
                HeaderField::create('HeaderField3', 'Other Fields', 3),
                NumericField::create('NumericField', 'Numeric Field '),
                DateField::create('DateField', 'Date Field'),
                DateTimeField::create('DateTimeField', 'Date and Time Field'),
                CheckboxField::create('CheckboxField', 'Checkbox Field')
            ),
            $actions = FieldList::create(
                FormAction::create('submit', 'Submit Button')
            ),
            $requiredFields = RequiredFields::create(
                'TextField1',
                'TextField2',
                'TextField3',
                'ErrorField1',
                'ErrorField2',
                'EmailField',
                'TextField3',
                'RightTitleField',
                'CheckboxField',
                'CheckboxSetField'
            )
        );
        $form->setMessage('warning message', 'warning');
        return $form;
    }
    public function TestForm($data)
    {
        $this->redirectBack();
    }
}
