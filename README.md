# Pushprom Yii 2 client

This is a Yii 2 client for for [Pushprom](https://github.com/messagebird/pushprom). It provides a thin layer on top of the [Pushprom PHP Client](https://github.com/messagebird/pushprom-php-client).

[![Latest Stable Version](https://poser.pugx.org/messagebird/pushprom-yii2-client/v/stable.svg)](https://packagist.org/packages/messagebird/pushprom-yii2-client)
[![License](https://poser.pugx.org/messagebird/pushprom-yii2-client/license.svg)](https://packagist.org/packages/messagebird/pushprom-yii2-client)

## Installing

You can install the Pushprom Yii 2 client through Composer by running:

```bash
composer require messagebird/pushprom-yii2-client:1.0.0
```

Alternatively, add this to your `composer.json`:

```json
"require": {
    "messagebird/pushprom-yii2-client": "1.0.0"
}
```

And then install by running:

```bash
composer update messagebird/pushprom-yii2-client
```

## Usage

In your configuration add the Pushprom component:

```php
'pushprom' => [
    'class' => \pushprom\yii2\Component::className(),
    'job' => 'messagebird',
    'url' => 'udp://127.0.0.1:9090'
],
```

Create and update metrics in your code:

```php
$gauge = new \pushprom\Gauge(
    \Yii::$app->pushprom,
    "fish_in_the_sea",
    "The amount of fish in the sea",
    [
        "species" => "Thalassoma noronhanum"
    ]
);
$gauge->set(2000);
```

## Helpers

The repository includes helpers for common tasks.

For example, we've found that logging HTTP responses and their time is something that is repeated amongst projects. `\Yii::$app->pushprom` contains helper methods to make it easier to stat them. You can use them like this:

```php
$config = [
    'components' => [
        'response' => [
            // ...
            'on beforeSend' => function ($event) {
                \Yii::$app->pushprom->logHttpResponse($event->sender->getStatusCode());
                \Yii::$app->pushprom->logResponseTimeMs();
            }
        ]
    ]
];
```

## License

The Yii 2 client for Pushprom is licensed under [The BSD 2-Clause License](http://opensource.org/licenses/BSD-2-Clause). Copyright (c) 2016, MessageBird