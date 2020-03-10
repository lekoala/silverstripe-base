<?php

namespace LeKoala\Base\Forms\GridField;

use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;

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
        return $config->getComponentByType(GridFieldFilterHeader::class);
    }

    /**
     * @param GridFieldConfig $config
     * @return GridFieldDataColumns
     */
    public static function getGridFieldDataColumns(GridFieldConfig $config)
    {
        return $config->getComponentByType(GridFieldDataColumns::class);
    }
}
