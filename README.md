# villermen/data-handling
PHP library with general data sanitization and formatting utilities.
Contains utility methods not otherwise contained in framework components that I see myself needing often.
My "functions.php", if you will.

## Examples
```php
Clean::text('<p>dirty &emdash; nasty<br>html</p>'); // "dirty — nasty html"
Clean::text('<p>dirty &emdash; nasty<br>html</p>', ['br']); // "dirty — nasty<br>html"
Clean::alphanumeric('proteïne shake'); // "proteineshake"
Clean::alphanumeric('proteïne shake', ' '); // "proteine shake"
Clean::slug('ßÈÿ žÐ-'); // "ssey-zdj"
Clean::digits("1 abc 23\n4") // "1234"

Path::format('/././//.///path//to\\file'); // "/path/to/file"
Path::merge('/root/directory', '/relative/path'); // "/root/directory/relative/path"
Path::makeRelative('/root/directory', '/root/directory/relative/path') // "relative/path"
Path::formatFilesize(1436549120); // "1.34 GiB"

Filter::match('file23.txt', 'file*.txt') // true
```

## Installation
```shell
composer require villermen/data-handling
```
