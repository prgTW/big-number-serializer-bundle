<?php

declare(strict_types=1);

namespace prgTW\BigNumberSerializerBundle;

use prgTW\BigNumberSerializerBundle\DependencyInjection\BigNumberSerializerExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BigNumberSerializerBundle extends Bundle
{
	/** @var string */
	protected $alias;

	public function __construct(string $alias = 'big_number_serializer')
	{
		$this->alias = $alias;
	}

	/** {@inheritdoc} */
	public function getContainerExtension()
	{
		return new BigNumberSerializerExtension($this->alias);
	}
}
