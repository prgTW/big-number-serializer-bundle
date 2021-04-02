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
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use JMS\Serializer\XmlSerializationVisitor;

class BigNumberHandler implements SubscribingHandlerInterface
{
	const SUPPORTED_FORMATS = ['json', 'xml'];

	private $xmlCData;

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

	public function serialize(SerializationVisitorInterface $visitor, BigNumber $number, array $type, Context $context)
	{
		if ($visitor instanceof XmlSerializationVisitor && false === $this->xmlCData) {
			return $visitor->visitSimpleString((string)$number, $type);
		}

		return $visitor->visitString((string)$number, $type);
	}

	public function deserialize(DeserializationVisitorInterface $visitor, $data, array $type)
	{
		if (null === $data) {
			return null;
		}

		return BigNumber::of($data);
	}
}
