# Views

The [Veil library](https://github.com/bayfrontmedia/veil) is used as the template engine.
 
This library will be added to the services container as `veil` when the configuration array exists at `/config/veil.php`. 

It is recommended to place all your views and template files in the `/resources/views` directory.

The suggested Veil configuration is:

```
return [
    'base_path' => resources_path('/views'),
    'file_extension' => '.veil.php'
];
```