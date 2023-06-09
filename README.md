# <a href="https://movemoveapp.com" target="_blank"><img src="https://avatars2.githubusercontent.com/u/69967331?s=20&v=4" width="20"></a> Laravel Postmangen Package

[//]: # ([![Build Status]&#40;https://app.travis-ci.com/movemoveapp/laravel-dadata.svg?branch=master&#41;]&#40;https://app.travis-ci.com/movemoveapp/laravel-dadata&#41;)

[//]: # ([![Latest Stable Version]&#40;https://poser.pugx.org/movemoveapp/laravel-dadata/v&#41;]&#40;//packagist.org/packages/movemoveapp/laravel-dadata&#41;)

[//]: # ([![Total Downloads]&#40;https://poser.pugx.org/movemoveapp/laravel-dadata/downloads&#41;]&#40;//packagist.org/packages/movemoveapp/laravel-dadata&#41;)

[//]: # ([![License]&#40;https://poser.pugx.org/movemoveapp/laravel-dadata/license&#41;]&#40;//packagist.org/packages/movemoveapp/laravel-dadata&#41;)

*Laravel Postmangen Package* - [Laravel](https://github.com/laravel/laravel) пакет генерации Postman коллекции запросов в формате JSON файла на основе запросов, выполняемых во время PHPUnit тестов.

## Установка
Вы можете установить пакет через composer:

```shell script
composer require movemoveapp/laravel-postmangen
``` 

Публикация конфигурационного файла. Выполните `artisan` команду

```shell script
php artisan vendor:publish --provider="MoveMoveIo\Postmangen\PostmangenServiceProvider"
```

Настройка проекта осществляется через файлы `.env` и `phpunit.xml` вашего проекта. Необходимо указать `POSTMANGEN_TMP` - путь для генерации промежуточных файлов относительно корня проекта:
```shell
POSTMANGEN_TMP=postman/
```
A также добавить секцию `<extensions>` в `phpunit.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd" bootstrap="vendor/autoload.php" colors="true">
  ...
  <extensions>
    <bootstrap class="MoveMoveIo\Postmangen\PostmangenPhpunitExtension">
        <parameter name="outputDir" value="postman/"/>
    </bootstrap>
  </extensions>
  ...
</phpunit>
```

И, наконец, необходимо добавить `PostmangenMiddleware` класс самым первым в список `middleware` (`app/Http/Kernel.php`):

```injectablephp
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
class Kernel extends HttpKernel
{
    protected $middleware = [
        \MoveMoveIo\Postmangen\Phpunit\Phpunit\Phpunit\Phpunit\Middleware\PostmangenMiddleware::class,
        // ...
    ];
    // ...
}
```

Теперь после каждого запуска тестов PHPUnit с использованием `phpunit.xml` в указанной директории будет генерироваться 
JSON файл `<APP_NAME>.postman_collection.json`. 