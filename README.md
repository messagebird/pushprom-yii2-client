# Pushprom yii2 client

A thin layer that helps developers to use the [Pushprom PHP Client](https://github.com/messagebird/pushprom-php-client) on yii2 apps.


# Installing

Add this to your composer.json:

```json
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/messagebird/pushprom-yii2-client"
        }
    ],
    "require": {
        "messagebird/pushprom-yii2-client": "dev-master"
    }

```

and then install

```bash
composer update messagebird/pushprom-yii2-client
```

# Using it

on config/main.php add the pushprom component:

```
        'pushprom' => [
            'class' => \pushprom\yii2\Component::className(),
            'job' => 'messagebird',
            'url' => 'udp://127.0.0.1:9090'
        ],
```

and around your code create and update metrics:


```php
$gauge = new \pushprom\Gauge(\Yii::$app->pushprom, "fish_in_the_sea", "The amount of fish in the sea", ["species" => "Thalassoma noronhanum"]);
$gauge->set(2000);
```

## Some helpers

Logging http responses and their time is something that repeats itself on projects.

We created a couple of helper functions on \Yii::$app->pushprom to make easier to stat them. You can use them on the yii config file like this:

```
$config = [
...
        'response' => [
            'charset' => 'UTF-8',
            'on beforeSend' => function ($event) {
                \Yii::$app->pushprom->logHttpResponse($event->sender->getStatusCode());
                \Yii::$app->pushprom->logResponseTimeMs();
            },
        ],
```