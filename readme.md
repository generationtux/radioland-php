# Radioland PHP

`@todo Document subscription handling.`

Pubsub utility with support for SNS and custom backend providers.

- [Quick start](#quick-start)
- [Providers](#providers)
- - [SNS](#sns)
- [Laravel](#laravel)
- [Custom providers](#custom-providers)

## Quick start

Install using [Composer](https://getcomposer.org/)
```
$ composer require generationtux/radioland
```

Initialize the client with the backend provider and publish a message (see [SNS](#sns) for config example)
```php
<?php

use Gentux\Radioland\Radio;
use Gentux\Radioland\Message;
use Gentux\Radioland\Providers\SNSProvider;

$radio = new Radio(
    new SNSProvider(['region' => 'us-east-1'])
);

$message = new Message('arn::some-topic', ['some' => 'data']);
$radio->publish($message);
```

Publish multiple messages
```php
$messages = [
    new Message('channel-1', ['some' => 'data']),
    new Message('channel-2', ['some' => 'data']),
];

$radio->publishAll($messages);
```

Collect messages and publish all later.
```php
$radio->collect(new Message(...));
// ...
$radio->collect(new Message(...));
// ...
$radio->publishCollection();
```

## Providers

Providers are the underlying app or service that handle pubsub messaging. Only [SNS](#sns) is supported currently, but you may also add [custom providers](#custom-providers).

### SNS

The SNS provider will read values from the environment by default. The following may be used to configure the client through the environment:
```
AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY
AWS_REGION
```

You may also pass configuration options directly to the provider
```php
new SNSProvider([
    'region' => 'us-east-1',
]);
```

See [AWS client configuration](http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html) for information about available config options.

In order to publish messages, you'll need the Topic ARN you wish to publish to. This will be the `channel` argument to a message, and the data for a message will be JSON encoded. For example
```php
$message = new Gentux\Radioland\Message('arn::some-topic', ['will-be' => 'json-encoded']);

// will result in a call to SNS with data that looks like
[
    'TopicArn': 'arn::some-topic',
    'Message': '{"will-be":"json-encoded"}',
]
```

## Laravel

Radioland provides a middleware that may be used with Laravel or Lumen in order to publish collected messages after the request/response lifecycle. This allows the app to collect messages during it's normal HTTP process, and then after the response has been sent, publish those messages to the provider without making the client wait.

First, configure your instance of Radioland in `App\Providers\AppServiceProvider`  in the `register` method as a singleton.

```php
<?php

use Gentux\Radioland\Radio;
use Gentux\Radioland\Providers\SNSProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Radio::class, function ($app) {
            return new Radio(new SNSProvider([...]));
        });
    }
}
```

Next, add the middleware to `App\Http\Kernel`

```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
        
        \Gentux\Radioland\Laravel\PublishCollectionAfterResponse::class, // <<------ Add middleware class
    ];
}
```

Now, messages may be collected during the request and after the response has been sent they will be published to the provider. For instance, in a controller:

```php
<?php

namespace App\Http\Controllers;

use Gentux\Radioland\Radio;
use Gentux\Radioland\Message;

class SomeController extends Controller
{
    
    public function store(Radio $radio)
    {
        $radio->collect(new Message('some-channel', ['action' => 'storing new data']));
        
        // ...
    }
}
```

## Custom providers

Custom providers should implement the `Gentux\Radioland\Providers\ProviderInterface`. Configuration may be passed as an array to the providers constructor.
