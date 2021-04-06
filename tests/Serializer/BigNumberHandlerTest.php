<?php
declare(strict_types=1);

namespace prgTW\BigNumberSerializerBundle\Tests\Serializer;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;
use prgTW\BigNumberSerializerBundle\Serializer\BigNumberHandler;

class BigNumberHandlerTest extends TestCase
{
	private static array $expectedBigInteger = [
		'json' => '"12345"',
		'xml'  => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result><![CDATA[12345\]\]></result>\n",
	];
	private static array $expectedBigDecimal = [
		'json' => '"123.45"',
		'xml'  => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result><![CDATA[123.45\]\]></result>\n",
	];
	private static array $expectedBigRational = [
		'json' => '"3\/4"',
		'xml'  => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result><![CDATA[3/4\]\]></result>\n",
	];

	public function testSerializedAsObjectWithoutHandler(): void
	{
		$serializer = SerializerBuilder::create()->build();

		$value = $serializer->serialize(BigNumber::of(123.45), 'json');

		self::assertNotSame('"123.45"', $value);
	}

	/**
	 * @dataProvider provideBigIntegers
	 */
	public function testBigInteger(string $format, BigInteger $number): void
	{
		$serializer = $this->buildSerializer();

		$serialized = $serializer->serialize($number, $format);
		self::assertSame(self::$expectedBigInteger[$format], $serialized);

		/** @var BigNumber $deserialized */
		$deserialized = $serializer->deserialize($serialized, BigNumber::class, $format);
		self::assertInstanceOf(BigInteger::class, $deserialized);
		self::assertEquals($number, $deserialized);
		self::assertTrue($deserialized->isEqualTo($number));
	}

	public function provideBigIntegers(): iterable
	{
		foreach (BigNumberHandler::SUPPORTED_FORMATS as $format) {
			yield "$format-0" => [$format, BigNumber::of(12345)];
			yield "$format-1" => [$format, BigNumber::of('12345')];
			yield "$format-2" => [$format, BigInteger::of(12345)];
			yield "$format-3" => [$format, BigInteger::of('12345')];
		}
	}

	/**
	 * @dataProvider provideBigDecimals
	 */
	public function testBigDecimal(string $format, BigDecimal $number): void
	{
		$serializer = $this->buildSerializer();

		$serialized = $serializer->serialize($number, $format);
		self::assertSame(self::$expectedBigDecimal[$format], $serialized);

		/** @var BigNumber $deserialized */
		$deserialized = $serializer->deserialize($serialized, BigNumber::class, $format);
		self::assertInstanceOf(BigDecimal::class, $deserialized);
		self::assertEquals($number, $deserialized);
		self::assertTrue($deserialized->isEqualTo($number));
	}

	public function provideBigDecimals(): iterable
	{
		foreach (BigNumberHandler::SUPPORTED_FORMATS as $format) {
			yield "$format-0" => [$format, BigNumber::of(123.45)];
			yield "$format-1" => [$format, BigNumber::of('123.45')];
			yield "$format-2" => [$format, BigDecimal::of(123.45)];
			yield "$format-3" => [$format, BigDecimal::of('123.45')];
			yield "$format-4" => [$format, BigDecimal::ofUnscaledValue(12345, 2)];
			yield "$format-5" => [$format, BigDecimal::ofUnscaledValue('12345', 2)];
		}
	}

	/**
	 * @dataProvider provideBigRationals
	 */
	public function testBigRational(string $format, BigRational $number): void
	{
		$serializer = $this->buildSerializer();

		$serialized = $serializer->serialize($number, $format);
		self::assertSame(self::$expectedBigRational[$format], $serialized);

		/** @var BigNumber $deserialized */
		$deserialized = $serializer->deserialize($serialized, BigNumber::class, $format);
		self::assertInstanceOf(BigRational::class, $deserialized);
		self::assertEquals($number, $deserialized);
		self::assertTrue($deserialized->isEqualTo($number));
	}

	public function provideBigRationals(): iterable
	{
		foreach (BigNumberHandler::SUPPORTED_FORMATS as $format) {
			yield "$format-0" => [$format, BigNumber::of('3/4')];
			yield "$format-1" => [$format, BigRational::of('3/4')];
		}
	}

	private function buildSerializer(): Serializer
	{
		$serializer = SerializerBuilder::create()->configureHandlers(
			function (HandlerRegistry $registry) {
				$registry->registerSubscribingHandler(new BigNumberHandler());
			}
		)->build();

		return $serializer;
	}
}
