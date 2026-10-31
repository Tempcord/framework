<?php

namespace Tempcord;

use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;
use Tempcord\Support\Localization\CatalogLocalizations;
use Tempcord\Support\Localization\LocalizationProvider;
use Tempcord\Support\Localization\NullLocalizations;

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
