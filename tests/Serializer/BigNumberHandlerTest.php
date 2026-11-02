<?php
declare(strict_types=1);

namespace prgTW\BigNumberSerializerBundle\Tests\Serializer;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\RoundingNecessaryException;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;
use prgTW\BigNumberSerializerBundle\Serializer\BigNumberHandler;
use prgTW\BigNumberSerializerBundle\Tests\Stub\BigDecimalWithPrecisionStub;
use prgTW\BigNumberSerializerBundle\Tests\Stub\BigRationalWithPrecisionStub;
use prgTW\BigNumberSerializerBundle\Tests\Stub\NoPrecisionStub;

class BigNumberHandlerTest extends TestCase
{
	private static array $expectedBigInteger = [
		'json' => '"12345"',
		'xml'  => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result><![CDATA[12345]]></result>\n",
	];
	private static array $expectedBigDecimal = [
		'json' => '"123.45"',
		'xml'  => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result><![CDATA[123.45]]></result>\n",
	];
	private static array $expectedBigRational = [
		'json' => '"3\/4"',
		'xml'  => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result><![CDATA[3/4]]></result>\n",
	];

	public function testSerializedAsObjectWithoutHandler(): void
	{
		$serializer = SerializerBuilder::create()->build();

		$value = $serializer->serialize(self::ofFloat(123.45, '123.45'), 'json');

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
			if (self::floatsAccepted()) {
				yield "$format-0" => [$format, BigNumber::of(123.45)];
			}
			yield "$format-1" => [$format, BigNumber::of('123.45')];
			if (self::floatsAccepted()) {
				yield "$format-2" => [$format, BigDecimal::of(123.45)];
			}
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

	public function testNoPrecisionSerialization(): void
	{
		$stub = new NoPrecisionStub(
			BigInteger::of('1234/1'),
			self::ofFloat(1234.56789, '1234.56789')->toBigDecimal(),
			BigRational::of('1234/5678')
		);

		$serializer = $this->buildSerializer();
		$serialized = $serializer->serialize($stub, 'json');
		$rational = self::rationalsAreSimplified() ? '617\\/2839' : '1234\\/5678';
		self::assertSame('{"integer":"1234","decimal":"1234.56789","rational":"' . $rational . '"}', $serialized);
	}

	public function testNoPrecisionDeserialization(): void
	{
		$data = '{"integer":"1234","decimal":"1234.56789","rational":"1234\/5678"}';

		$serializer = $this->buildSerializer();
		/** @var NoPrecisionStub $stub */
		$stub = $serializer->deserialize($data, NoPrecisionStub::class, 'json');

		self::assertSame(5, $stub->getDecimal()->getScale());
		self::assertTrue(BigRational::of('1234/5678')->isEqualTo($stub->getRational()));
		if (self::rationalsAreSimplified()) {
			self::assertSame(617, $stub->getRational()->getNumerator()->toInt());
			self::assertSame(2839, $stub->getRational()->getDenominator()->toInt());
		} else {
			self::assertSame(1234, $stub->getRational()->getNumerator()->toInt());
			self::assertSame(5678, $stub->getRational()->getDenominator()->toInt());
		}
	}

	public function testBigDecimalWithPrecisionSerialization(): void
	{
		$stub = new BigDecimalWithPrecisionStub(BigDecimal::of(1));

		$serializer = $this->buildSerializer();
		$data       = $serializer->serialize($stub, 'json');
		self::assertSame('{"value":"1.00"}', $data);
	}

	public function testBigRationalWithPrecisionSerialization(): void
	{
		$stub = new BigRationalWithPrecisionStub(BigRational::of('25/100'));

		$serializer = $this->buildSerializer();
		$data       = $serializer->serialize($stub, 'json');
		self::assertSame(self::rationalsAreSimplified() ? '{"value":"1\/4"}' : '{"value":"25\/100"}', $data);
	}

	/**
	 * @dataProvider provideFractionsForDeserializationData
	 */
	public function testBigDecimalWithPrecisionDeserialization(string $format, string $data): void
	{
		$serializer = $this->buildSerializer();
		/** @var BigDecimalWithPrecisionStub $stub */
		$stub = $serializer->deserialize($data, BigDecimalWithPrecisionStub::class, $format);

		self::assertInstanceOf(BigDecimalWithPrecisionStub::class, $stub);
		self::assertSame(2, $stub->getValue()->getScale());
		self::assertSame('0.25', (string)$stub->getValue());
	}

	/**
	 * @dataProvider provideFractionsForDeserializationData
	 */
	public function testBigRationalWithPrecisionDeserialization(string $format, string $data): void
	{
		$serializer = $this->buildSerializer();

		/** @var BigRationalWithPrecisionStub $stub */
		$stub = $serializer->deserialize($data, BigRationalWithPrecisionStub::class, $format);

		self::assertTrue(BigRational::of('1/4')->isEqualTo($stub->getValue()));
	}

	public function provideFractionsForDeserializationData(): iterable
	{
		yield 'json-0' => ['json', '{"value":"1/4"}'];
		yield 'json-1' => ['json', '{"value":"4/16"}'];
		yield 'json-2' => ['json', '{"value":"0.25"}'];

		yield 'xml-simple-0' => ['xml', '<result><value>1/4</value></result>'];
		yield 'xml-simple-1' => ['xml', '<result><value>4/16</value></result>'];
		yield 'xml-simple-2' => ['xml', '<result><value>0.25</value></result>'];

		yield 'xml-cdata-0' => ['xml', '<result><value><![CDATA[1/4]]></value></result>'];
		yield 'xml-cdata-1' => ['xml', '<result><value><![CDATA[4/16]]></value></result>'];
		yield 'xml-cdata-2' => ['xml', '<result><value><![CDATA[0.25]]></value></result>'];
	}

	public function testBigDecimalWithPrecisionRoundingNecessaryDuringSerialization(): void
	{
		$this->expectException(RoundingNecessaryException::class);

		$serializer = $this->buildSerializer();
		$stub       = new BigDecimalWithPrecisionStub(self::ofFloat(0.375, '0.375')->toBigDecimal());

		$serializer->serialize($stub, 'json');
	}

	public function testBigRationalWithPrecisionRoundingNecessaryDuringSerialization(): void
	{
		$this->expectException(RoundingNecessaryException::class);

		$serializer = $this->buildSerializer();
		$stub       = new BigRationalWithPrecisionStub(BigRational::of('3/8'));

		$serializer->serialize($stub, 'json');
	}

	/**
	 * @dataProvider provideFractionsForRoundingNecessaryDeserializationData
	 */
	public function testBigDecimalWithPrecisionRoundingNecessaryDuringDeserialization(
		string $format,
		string $data
	): void {
		$this->expectException(RoundingNecessaryException::class);

		$serializer = $this->buildSerializer();
		$serializer->deserialize($data, BigDecimalWithPrecisionStub::class, $format);
	}

	/**
	 * @dataProvider provideFractionsForRoundingNecessaryDeserializationData
	 */
	public function testBigRationalWithPrecisionRoundingNecessaryDuringDeserialization(
		string $format,
		string $data
	): void {
		$this->expectException(RoundingNecessaryException::class);

		$serializer = $this->buildSerializer();
		$serializer->deserialize($data, BigRationalWithPrecisionStub::class, $format);
	}

	public function provideFractionsForRoundingNecessaryDeserializationData(): iterable
	{
		yield 'json-0' => ['json', '{"value":"3/8"}'];
		yield 'json-1' => ['json', '{"value":"9/24"}'];
		yield 'json-2' => ['json', '{"value":"0.375"}'];

		yield 'xml-simple-0' => ['xml', '<result><value>3/8</value></result>'];
		yield 'xml-simple-1' => ['xml', '<result><value>9/24</value></result>'];
		yield 'xml-simple-2' => ['xml', '<result><value>0.375</value></result>'];

		yield 'xml-cdata-0' => ['xml', '<result><value><![CDATA[3/8]]></value></result>'];
		yield 'xml-cdata-1' => ['xml', '<result><value><![CDATA[9/24]]></value></result>'];
		yield 'xml-cdata-2' => ['xml', '<result><value><![CDATA[0.375]]></value></result>'];
	}

	/**
	 * @dataProvider provideJsonNumbersForDeserialization
	 */
	public function testBigDecimalWithPrecisionDeserializationFromJsonNumber(string $data, string $expected): void
	{
		$serializer = $this->buildSerializer();

		/** @var BigDecimalWithPrecisionStub $stub */
		$stub = $serializer->deserialize($data, BigDecimalWithPrecisionStub::class, 'json');

		self::assertSame($expected, (string)$stub->getValue());
	}

	public function provideJsonNumbersForDeserialization(): iterable
	{
		yield 'int'               => ['{"value":1}', '1.00'];
		yield 'float'             => ['{"value":0.25}', '0.25'];
		yield 'negative float'    => ['{"value":-123.45}', '-123.45'];
		// Read with 14 significant digits, as brick/math 0.9 did: the exact binary value has 55 decimals
		yield 'inexact float'     => ['{"value":0.1}', '0.10'];
	}

	public function testDeserializationFromJsonNumberKeepsTheNumericLocale(): void
	{
		$locale = \setlocale(LC_NUMERIC, '0');

		$this->buildSerializer()->deserialize('{"value":0.25}', BigDecimalWithPrecisionStub::class, 'json');

		self::assertSame($locale, \setlocale(LC_NUMERIC, '0'));
	}

	public function testBigDecimalWithPrecisionRoundingNecessaryDuringDeserializationFromJsonNumber(): void
	{
		$this->expectException(RoundingNecessaryException::class);

		$serializer = $this->buildSerializer();
		$serializer->deserialize('{"value":0.375}', BigDecimalWithPrecisionStub::class, 'json');
	}

	/** brick/math 0.15+ rejects floats; older versions keep exercising the float inputs. */
	private static function floatsAccepted(): bool
	{
		try {
			BigNumber::of(1.5);

			return true;
		} catch (\TypeError $e) {
			return false;
		}
	}

	private static function ofFloat(float $float, string $string): BigNumber
	{
		return self::floatsAccepted() ? BigNumber::of($float) : BigNumber::of($string);
	}

	/** Newer brick/math versions keep rationals in lowest terms. */
	private static function rationalsAreSimplified(): bool
	{
		return '1/2' === (string)BigRational::of('2/4');
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
