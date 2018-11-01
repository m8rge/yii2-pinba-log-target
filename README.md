Usage
=====

Add to yii2 config:

```php
[
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => \app\components\PinbaLogTarget::class,
                    'enabled' => true,
                    'pinbaHost' => 'pinba.engine.host.name.com',
                    'serverName' => 'myapp.com', // especially usefull in cli scripts
                    'excludeScriptNames' => ['/debug/*'], // do not profile debug panel
                ],
            ],
        ],
    ],
],
```