# Silverstripe Auto-Delete

## EXPERIMENTAL!!! ##

An extension to add functionality to DataObjects to:

- Delete unused `$has_one` relationships when the `$has_one` changes (eg: a new file is uploaded). This functionality will not run directly from and `Versioned` DataObjects.
- Delete unused `$has_one` & `$has_many` relationships when a DataObject is deleted.

The extension will only delete objects if they are not in use elsewhere.

All DataObjects get two additional new functions which you can integrate into other things:

- `inUse()` - used to detect whether an object is in use somewhere. Note: It only detects if it is used from non-versioned and published relationships. Draft & modified states are not detected!
- `deleteIfUnused()` - used to delete a DataObject if not in use somewhere. If the object being deleted had a `deleteFile()` method, then that will be run too (ie: file deletion).


## Usage

In your DataObject, add a `private static $auto_delete = [....];` variable, defining which related `$has_one` and `$has_many` DataObjects to automatically delete, for example:

```php
class Slide extends DataObject {
    private static $db = [
        'Title' => 'Varchar(50)',
    ];

    private static $has_one = [
        'Image' => Image::class,
    ];

    /**
     * $has_one & $has_many auto-delete definitions
     *
     * @var array
     */
    private static $auto_delete = [
        'Image',
    ];
}
```
This will automatically delete the `Image` (if unused elsewhere) if the `Slide` is deleted, or delete the original `Image` if a new `Image` is uploaded.


## Requirements

- Silverstripe ^4.5
- silverstripe/assets ^1.7.0


## Installation via Composer

You can install it via composer with

```
composer require axllent/silverstripe-auto-delete
```
