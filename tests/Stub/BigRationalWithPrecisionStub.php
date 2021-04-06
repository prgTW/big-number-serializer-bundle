<?php

declare(strict_types=1);

namespace prgTW\BigNumberSerializerBundle\Tests\Stub;

use Brick\Math\BigRational;
use JMS\Serializer\Annotation as Serializer;

class BigRationalWithPrecisionStub
{
	/**
	 * @Serializer\SerializedName("value")
	 * @Serializer\Type("Brick\Math\BigRational<'2'>")
	 */
	private BigRational $value;

	public function __construct(BigRational $value)
	{
		$this->value = $value;
	}

	public function getValue(): BigRational
	{
		return $this->value;
	}
}
