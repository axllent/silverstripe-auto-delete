<?php
namespace Axllent\AutoDelete;

use SilverStripe\Assets\Folder;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\ChangeSetItem;
use SilverStripe\Versioned\Versioned;

class AutoDeleteExtension extends Extension
{
    /**
     * Return whether the file is in use
     *
     * @return bool
     */
    public function inUse()
    {
        return $this->owner->findAllRelatedData()
            ->exclude(
                'ClassName', [
                    ChangeSetItem::class,
                    Member::class,
                    Folder::class,
                ]
            )
            ->count() > 0;
    }

    /**
     * Delete the file if not in use elsewhere
     *
     * This can be used in an onAfterDelete() on the relating object
     *
     * @return void
     */
    public function deleteIfUnused()
    {
        if (!$this->owner->inUse()) {
            if ($this->owner->hasMethod('deleteFile')) {
                $this->owner->deleteFile();
            }
            $this->owner->delete();
        }
    }

    /**
     * Array of File IDs to delete (if not in use)
     *
     * @var array
     */
    private static $_has_ones_to_delete = [];

    /**
     * Queue IDs of replaced files
     *
     * @return void
     */
    public function onBeforeWrite()
    {
        if ($this->owner->hasExtension(Versioned::class)) {
            // we don't run on versioned tables
            return;
        }

        $ad = $this->owner->config()->get('auto_delete');

        if (empty($ad) || !is_array($ad)) {
            return;
        }

        if ($this->owner->exists()) {
            foreach ($ad as $type) {
                $object_type = $this->owner->getRelationType($type);

                if (!$object_type || $object_type != 'has_one') {
                    continue;
                }

                $changed = $this->owner->getChangedFields();
                if (!empty($changed[$type . 'ID'])
                    && !empty($changed[$type . 'ID']['before'])
                ) {
                    $class = $this->owner->getRelationClass($type);
                    array_push(
                        self::$_has_ones_to_delete,
                        [$class, $changed[$type . 'ID']['before']]
                    );
                }
            }
        }
    }

    /**
     * Delete the queued files if no longer in use
     *
     * @return void
     */
    public function onAfterWrite()
    {
        foreach (self::$_has_ones_to_delete as $ho) {
            list($class, $id) = $ho;
            if ($f = $class::get()->byID($id)) {
                $f->deleteIfUnused();
            }
        }
    }

    /**
     * Event handler called after deleting from the database.
     *
     * @return void
     */
    public function onAfterDelete()
    {
        $ad = $this->owner->config()->get('auto_delete');

        if (empty($ad) || !is_array($ad)) {
            return;
        }

        foreach ($ad as $type) {
            $object_type = $this->owner->getRelationType($type);

            if (!$object_type) {
                continue;
            }

            if ($object_type == 'has_one') {
                $o = $this->owner->$type();
                if ($o->exists()) {
                    $o->deleteIfUnused();
                }
            } elseif ($object_type == 'has_many') {
                foreach ($this->owner->$type() as $o) {
                    $o->deleteIfUnused();
                }
            }
        }
    }
}
