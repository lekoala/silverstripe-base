<?php

namespace LeKoala\Base\Extensions;

use SilverStripe\Versioned\RecursivePublishable as BaseRecursivePublishable;

/**
 * This is if your upload fails
 *
 * @link https://github.com/silverstripe/silverstripe-asset-admin/pull/842
 * It's much better to fix asset-admin than RecursivePublishable anyway
 *
 * Until merged in stable
 * @link https://github.com/silverstripe/silverstripe-versioned/pull/184
 * @property \LeKoala\Base\Extensions\RecursivePublishable $owner
 */
class RecursivePublishable extends BaseRecursivePublishable
{
    /**
     * Find objects which own this object.
     * Note that objects will only be searched in the same stage as the given record.
     *
     * @param bool $recursive True if recursive
     * @param ArrayList $list List to add items to
     * @param array $lookup List of reverse lookup rules for owned objects
     * @return ArrayList list of objects
     */
    public function findOwnersRecursive($recursive, $list, $lookup)
    {
        // First pass: find objects that are explicitly owned_by (e.g. custom relationships)
        /** @var DataObject $owner */
        $owner = $this->owner;
        $owners = $owner->findRelatedObjects('owned_by', false);

        // Second pass: Find owners via reverse lookup list
        if ($this->owner->isInDB()) {
            foreach ($lookup as $ownedClass => $classLookups) {
                // Skip owners of other objects
                if (!is_a($this->owner, $ownedClass)) {
                    continue;
                }
                foreach ($classLookups as $classLookup) {
                    // Merge new owners into this object's owners
                    $ownerClass = $classLookup['class'];
                    $ownerRelation = $classLookup['relation'];
                    $result = $this->owner->inferReciprocalComponent($ownerClass, $ownerRelation);
                    $owner->mergeRelatedObjects($owners, $result);
                }
            }
        }

        // Merge all objects into the main list
        $newItems = $owner->mergeRelatedObjects($list, $owners);

        // If recursing, iterate over all newly added items
        if ($recursive) {
            foreach ($newItems as $item) {
                /** @var RecursivePublishable|DataObject $item */
                $item->findOwnersRecursive(true, $list, $lookup);
            }
        }

        return $list;
    }
}
