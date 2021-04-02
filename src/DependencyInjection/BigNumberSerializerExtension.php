<?php

declare(strict_types=1);

namespace prgTW\BigNumberSerializerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class BigNumberSerializerExtension extends ConfigurableExtension
{
	/** @var string */
	protected $alias;

	public function __construct(string $alias)
	{
		$this->alias = $alias;
	}

	/** {@inheritdoc} */
	protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
	{
		$loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');
	}

	/** {@inheritdoc} */
	public function getAlias()
	{
		return $this->alias;
	}

	/** {@inheritdoc} */
	public function getConfiguration(array $config, ContainerBuilder $container)
	{
		return new Configuration($this->alias);
	}
}
