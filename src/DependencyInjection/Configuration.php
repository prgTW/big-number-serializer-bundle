<?php

declare(strict_types=1);

namespace prgTW\BigNumberSerializerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
	/** @var string */
	protected $alias;

	public function __construct(string $alias)
	{
		$this->alias = $alias;
	}

	/** {@inheritdoc} */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder($this->alias);

		if (method_exists($treeBuilder, 'getRootNode')) {
			$rootNode = $treeBuilder->getRootNode();
		} else {
			// BC layer for symfony/config 4.1 and older
			$rootNode = $treeBuilder->root($this->alias);
		}

		return $treeBuilder;
	}
}
