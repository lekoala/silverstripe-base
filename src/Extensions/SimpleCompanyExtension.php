<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\ORM\DataExtension;

/**
 * A simple company extension for Member
 *
 * You might want to use a more complete company representation for situations where members can belong to multiple companies
 * or if you need more details about the company
 *
 * @property \LeKoala\Base\Extensions\SimpleCompanyExtension $owner
 * @property string $CompanyName
 * @property string $VatNumber
 */
class SimpleCompanyExtension extends DataExtension
{
    private static $db = [
        "CompanyName" => "Varchar(255)",
        "VatNumber" => "Varchar(255)",
    ];

    public function IsIndividual()
    {
        return $this->owner->CompanyName == '';
    }

    public function IsCompany()
    {
        return $this->owner->CompanyName != '';
    }
}
