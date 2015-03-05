# Loggly Extension for Yii2

This extensions provides support for [Loggly](http://loggly.com/) as log target for [Yii2](https://github.com/yiisoft/yii2) applications.
It is partially based on the yii 1.* extension [yii-loggly](https://github.com/aotd1/yii-loggly) by Alexey Ashurok.

[![Build Status](https://travis-ci.org/spacedealer/yii2-loggly.svg?branch=master)](https://travis-ci.org/spacedealer/yii2-loggly)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/6650bfdc-8c13-4fdb-bcec-66696e084fa8/mini.png)](https://insight.sensiolabs.com/projects/6650bfdc-8c13-4fdb-bcec-66696e084fa8)
[![Dependency Status](https://www.versioneye.com/user/projects/547dc2de8674a48feb0000df/badge.svg?style=flat)](https://www.versioneye.com/user/projects/547dc2de8674a48feb0000df)


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
