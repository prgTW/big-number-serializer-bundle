<?php

declare(strict_types=1);

namespace prgTW\BigNumberSerializerBundle\Tests\Stub;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigRational;
use JMS\Serializer\Annotation as Serializer;

class NoPrecisionStub
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
