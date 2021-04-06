<?php

declare(strict_types=1);

namespace prgTW\BigNumberSerializerBundle\Serializer;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\XmlSerializationVisitor;

class BigNumberHandler implements SubscribingHandlerInterface
{
	public const SUPPORTED_FORMATS = ['json', 'xml'];

	private bool $xmlCData;

	public function __construct(bool $xmlCData = true)
	{
		$this->xmlCData = $xmlCData;
	}

	public static function getSubscribingMethods()
	{
		$methods = [];

		$classes = [
			BigNumber::class,
			BigInteger::class,
			BigDecimal::class,
			BigRational::class,
		];
		foreach ($classes as $class) {
			foreach (self::SUPPORTED_FORMATS as $format) {
				$methods[] = [
					'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
					'format'    => $format,
					'type'      => $class,
					'method'    => 'serialize',
				];

				$methods[] = [
					'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
					'format'    => $format,
					'type'      => $class,
					'method'    => 'deserialize',
				];
			}
		}

		return $methods;
	}

	public function serialize(VisitorInterface $visitor, BigNumber $number, array $type, Context $context)
	{
		$scale = $this->getScale($type);
		if (null !== $scale) {
			$number = $number->toScale($scale);
		}

		switch ($type['name']) {
			case BigInteger::class:
				$number = $number->toBigInteger();
				break;

			case BigDecimal::class:
				$number = $number->toBigDecimal();
				break;

			case BigRational::class:
				$number = $number->toBigRational();
				break;
		}

		if ($visitor instanceof XmlSerializationVisitor && false === $this->xmlCData) {
			return $visitor->visitSimpleString((string)$number, $type, $context);
		}

		return $visitor->visitString((string)$number, $type, $context);
	}

	public function deserialize(VisitorInterface $visitor, $data, array $type)
	{
		if (null === $data) {
			return null;
		}

		$number = BigNumber::of($data);

		$scale = $this->getScale($type);
		if (null !== $scale) {
			$number = $number->toScale($scale);
		}

		switch ($type['name']) {
			case BigInteger::class:
				return $number->toBigInteger();
			case BigDecimal::class:
				return $number->toBigDecimal();
			case BigRational::class:
				return $number->toBigRational();
			default:
				return $number;
		}
	}

	private function getScale(array $type): ?int
	{
		return isset($type['params'][0]) ? (int)$type['params'][0] : null;
	}
}
