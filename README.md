# Loggly Extension for Yii2

This extensions provides support for [Loggly](http://loggly.com/) as log target for [Yii2](https://github.com/yiisoft/yii2) applications.
It is partially based on the yii 1.* extension [yii-loggly](https://github.com/aotd1/yii-loggly) by Alexey Ashurok.

## Requirements

 - php >= 5.4
 - php5-curl extension
 - Yii2
 
## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist spacedealer/yii2-loggly "*"
```

or add

```
"spacedealer/yii2-loggly": "*"
```

to the require section of your `composer.json` file.

## Usage

Once the extension is installed, simply modify your application configuration as follows:

```php
'log' => [
	'targets' => [
		'loggly' => [
			'class' => 'spacedealer\loggly\Target',
			'customerToken' => 'your_customer_token',
			'levels' => ['error', 'warning', 'info', 'trace'],
			'tags' => ['console', 'staging']
			'enableIp' => false,
			'enableTrail' => true,
		],
	],
],
```
## Resources

 - [GitHub](https://github.com/spacedealer/yii2-loggly)
