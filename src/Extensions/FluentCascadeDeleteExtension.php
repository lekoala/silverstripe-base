<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\Versioned\Versioned;
use TractorCow\Fluent\Model\Locale;

/**
 * Class FluentCascadeDeleteExtension
 *
 * This extension ensures that all localized-entries of a record are deleted, once the main record gets deleted.
 * This is a workaround/fix for the following issue: https://github.com/tractorcow-farm/silverstripe-fluent/issues/438
 *
 * Apply to the same model as you applied the fluent extension to. Example:
 * ```
 * DataObjectName:
 *  extensions:
 *    - LeKoala\Base\Extensions\FluentCascadeDeleteExtension
 *    - TractorCow\Fluent\Extension\FluentVersionedExtension
 * ```
 *
 * This extension works for versioned and unversioned records.
 *
 * ONLY NEED FOR FLUENT V4, SEE DELETION POLICIES WITH FLUENT 5
 *
 * @property \LeKoala\Base\Extensions\FluentCascadeDeleteExtension $owner
 */
class FluentCascadeDeleteExtension extends Extension
{
    public function updateDeleteTables(&$queriedTables)
    {
        // Ensure a locale exists
        $locale = Locale::getCurrentLocale();
        if (!$locale) {
            return;
        }

        $localisedTables = $this->owner->getLocalisedTables();
        $tableClasses = ClassInfo::ancestry($this->owner, true);

        // Delete all locale versions
        foreach ($tableClasses as $class) {
            // Check main table name
            $table = DataObject::getSchema()->tableName($class);

            // If table isn't localised, skip
            if (!isset($localisedTables[$table])) {
                continue;
            }

            // Remove _Localised record
            $localisedTable = $this->owner->getLocalisedTable($table);

            if ($this->owner->hasExtension(Versioned::class) && Versioned::get_stage() == Versioned::LIVE) {
                $localisedTable .= '_Live';
            }

            $localisedDelete = SQLDelete::create(
                "\"{$localisedTable}\"",
                [
                    '"RecordID"' => $this->owner->ID,
                ]
            );
            $localisedDelete->execute();
        }
    }
}
