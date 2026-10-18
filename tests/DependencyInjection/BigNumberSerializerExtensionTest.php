<?php
declare(strict_types=1);

namespace prgTW\BigNumberSerializerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use prgTW\BigNumberSerializerBundle\DependencyInjection\BigNumberSerializerExtension;
use prgTW\BigNumberSerializerBundle\Serializer\BigNumberHandler;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BigNumberSerializerExtensionTest extends TestCase
{
	public function testHandlerIsRegisteredAsSubscribingHandler(): void
	{
		$container = new ContainerBuilder();
		$extension = new BigNumberSerializerExtension('big_number_serializer');
		$container->registerExtension($extension);
		$container->loadFromExtension('big_number_serializer', []);
		$container->compile();

		self::assertSame('big_number_serializer', $extension->getAlias());
		self::assertTrue($container->hasDefinition(BigNumberHandler::class));
		self::assertArrayHasKey(
			'jms_serializer.subscribing_handler',
			$container->getDefinition(BigNumberHandler::class)->getTags()
		);
	}
}
