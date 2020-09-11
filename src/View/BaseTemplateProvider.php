<?php

use SilverStripe\Control\Director;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Global template provider
 */
class BaseTemplateProvider implements TemplateGlobalProvider
{
    /**
     * @return array
     */
    public static function get_template_global_variables()
    {
        return array(
            'BoxIcon'
        );
    }

    /**
     * @link https://boxicons.com/
     * @param string $icon
     * @param string $style regular,solid,logos
     * @return DBHTMLText
     */
    public static function BoxIcon($icon, $style = null)
    {
        // aliases
        switch ($icon) {
            case 'email':
            case 'mail':
                $icon = 'envelope';
                break;
        }
        // style setup
        switch ($style) {
            case "solid":
                $prefix = 'bxs-';
                break;
            case 'logos':
                $prefix = 'bxl-';
                break;
            default:
                $prefix = 'bx-';
                $style = 'regular';
                break;
        }
        $htmlFragment = new DBHTMLText('Icon');
        $filename = Director::baseFolder() . '/base/images/box-icons/' . $style . '/' . $prefix . $icon . '.svg';
        if (!is_file($filename)) {
            return $htmlFragment;
        }
        $contents = file_get_contents($filename);
        $htmlFragment->setValue($contents);
        return $htmlFragment;
    }
}
