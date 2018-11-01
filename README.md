Usage
=====

Add to yii2 config:

```php
[
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => \m8rge\pinba\PinbaLogTarget::class,
                    'pinbaHost' => 'pinba.engine.host.name.com', // required
                    'enabled' => true,
                    'serverName' => 'myapp.com', // especially usefull in cli scripts
                    'excludeScriptNames' => ['/debug/*'], // do not profile debug panel
                ],
            ],
        ],
    ],
],
```

Profile in code:
```php
\Yii::beginProfile($token, $category);
// ...
\Yii::endProfile($token, $category);
```