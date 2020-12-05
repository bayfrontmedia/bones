# Translation

The [Translation](https://github.com/bayfrontmedia/translation) library is used to handle language translation.

This library will be added to the services container as `translate` if enabled using a valid configuration array as `/config/translation.php`.

**Example:**
```
return [
    'enabled' => true,
    'adapter' => 'Local',
    'root_path' => resources_path('/translations'),
    'locale' => do_filter('translation.locale', 'en') // Filtered default locale
];
``` 