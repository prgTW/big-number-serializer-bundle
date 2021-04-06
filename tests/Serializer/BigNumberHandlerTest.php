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

	public function testNoPrecisionSerialization(): void
	{
		$stub = new NoPrecisionStub(
			BigInteger::of('1234/1'),
			BigDecimal::of(1234.56789),
			BigRational::of('1234/5678')
		);

		$serializer = $this->buildSerializer();
		$serialized = $serializer->serialize($stub, 'json');
		self::assertSame('{"integer":"1234","decimal":"1234.56789","rational":"1234\/5678"}', $serialized);
	}

	public function testNoPrecisionDeserialization(): void
	{
		$data = '{"integer":"1234","decimal":"1234.56789","rational":"1234\/5678"}';

		$serializer = $this->buildSerializer();
		/** @var NoPrecisionStub $stub */
		$stub = $serializer->deserialize($data, NoPrecisionStub::class, 'json');

		self::assertSame(5, $stub->getDecimal()->getScale());
		self::assertSame(1234, $stub->getRational()->getNumerator()->toInt());
		self::assertSame(5678, $stub->getRational()->getDenominator()->toInt());
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
		self::assertSame('{"value":"25\/100"}', $data);
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
		$stub       = new BigDecimalWithPrecisionStub(BigDecimal::of(0.375));

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
