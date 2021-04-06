<?php

declare(strict_types=1);

namespace prgTW\BigNumberSerializerBundle\Tests\Stub;

use Brick\Math\BigDecimal;
use JMS\Serializer\Annotation as Serializer;

class BigDecimalWithPrecisionStub
{
	/**
	 * @Serializer\SerializedName("value")
	 * @Serializer\Type("Brick\Math\BigDecimal<'2'>")
	 */
	private BigDecimal $value;

	public function __construct(BigDecimal $value)
	{
		$this->value = $value;
	}

	public function getValue(): BigDecimal
	{
		return $this->value;
	}
}
