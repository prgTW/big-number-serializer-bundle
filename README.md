# BigNumberSerializerBundle

[![GitHub license](https://img.shields.io/github/license/prgtw/big-number-serializer-bundle)](https://github.com/prgTW/big-number-serializer-bundle/blob/main/LICENSE)
[![Packagist](https://img.shields.io/packagist/v/prgtw/big-number-serializer-bundle)](https://packagist.org/packages/prgtw/big-number-serializer-bundle)

Bundle for serializing [BigNumber](https://github.com/brick/math) classes to/from `string` representation using [JmsSerializer](https://github.com/schmittjoh/serializer).

# Installation

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
	
# Example

```php
/**
 * @Serializer\ExclusionPolicy("NONE")
 */
class Temp
{
	/**
	 * @Serializer\SerializedName("integer")
	 * @Serializer\Type("Brick\Math\BigInteger")
	 */
	private BigInteger $integer;

	/**
	 * @Serializer\SerializedName("decimal")
	 * @Serializer\Type("Brick\Math\BigDecimal")
	 */
	private BigDecimal $decimal;

	/**
	 * @Serializer\SerializedName("rational")
	 * @Serializer\Type("Brick\Math\BigRational")
	 */
	private BigRational $rational;

	public function __construct(BigInteger $integer, BigDecimal $decimal, BigRational $rational)
	{
		$this->integer  = $integer;
		$this->decimal  = $decimal;
		$this->rational = $rational;
	}

	public function getInteger(): BigInteger
	{
		return $this->integer;
	}

	public function getDecimal(): BigDecimal
	{
		return $this->decimal;
	}

	public function getRational(): BigRational
	{
		return $this->rational;
	}
}

// ----------------------------------------

$temp = new Temp(
	BigInteger::of('12345'),
	BigDecimal::of('123.45'),
	BigRational::of('4/7')
);

echo $jmsSerializer->serialize($temp, 'json');
```

## Results
### Before (without bundle)
```json
{
  "integer": {"value": "12345"},
  "decimal": {"value": "12345", "scale": 2},
  "rational": {"numerator": {"value":"4"}, "denominator": {"value":"7"}}
}
```

### After (using bundle)
```json
{
  "integer": "12345",
  "decimal": "123.45",
  "rational": "4\/7"
}
```
