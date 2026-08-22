<?php

namespace Tempcord\Localization;

use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;

final readonly class LocalizationInitializer implements Initializer
{
    /**
     * Referenced as a string so the class is never autoloaded when tempest/intl
     * is absent.
     */
    private const string CATALOG = 'Tempest\\Intl\\Catalog\\Catalog';

    #[Singleton]
    public function initialize(Container $container): LocalizationProvider
    {
        if (!interface_exists(self::CATALOG)) {
            return new NullLocalizations();
        }

        return new CatalogLocalizations($container->get(self::CATALOG));
    }
}
