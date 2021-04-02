# BigNumberSerializerBundle

[![Packagist](https://img.shields.io/packagist/l/prgtw/big-number-serializer-bundle.svg)](https://github.com/prgTW/big-number-serializer-bundle)
[![Packagist](https://img.shields.io/packagist/v/prgtw/big-number-serializer-bundle.svg)](https://packagist.org/packages/prgtw/big-number-serializer-bundle)

Bundle for serializing [BigNumber](https://github.com/brick/math) classes to/from string representation using [JmsSerializer](https://github.com/schmittjoh/serializer).

## Installation

1. Require the `prgtw/big-number-serializer-bundle` package in your `composer.json`
   and update your dependencies.
	
	```bash
	composer require prgtw/big-number-serializer-bundle
	```

2. Add the `BigNumberSerializerBundle` to your application's kernel:

	```php
	public function registerBundles()
	{
		$bundles = [
			// ...
			new prgTW\BigNumberSerializerBundle(),
			// ...
		];
		// ...
	}
	````

