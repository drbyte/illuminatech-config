<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2019 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Config\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminatech\Config\Console\ConfigCacheCommand;
use Illuminatech\Config\PersistentRepository;
use Illuminatech\Config\StorageContract;

/**
 * AbstractPersistentConfigServiceProvider is scaffold for the application-wide persistent configuration setup.
 *
 * You may extend this class, overriding abstract methods, to create persistent configuration for the particular application.
 *
 * For example:
 *
 * ```php
 * namespace App\Providers;
 *
 * use Illuminatech\Config\Providers\AbstractPersistentConfigServiceProvider;
 * use Illuminatech\Config\StorageContract;
 * use Illuminatech\Config\StorageDb;
 *
 * class PersistentConfigServiceProvider extends AbstractPersistentConfigServiceProvider
 * {
 *     protected function storage(): StorageContract
 *     {
 *         return (new StorageDb($this->app->make('db.connection')));
 *     }
 *
 *     protected function items(): array
 *     {
 *         return [
 *             'mail.contact.address' => [
 *                 'label' => __('Email address receiving contact messages'),
 *                 'rules' => ['sometimes', 'required', 'email'],
 *             ],
 *             // ...
 *         ];
 *     }
 * }
 * ```
 *
 * Do not forget to register your particular persistent config service provider in "providers" section at "config/app.php".
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.1.1
 */
abstract class AbstractPersistentConfigServiceProvider extends ServiceProvider
{
    /**
     * @var string key used to store persistent config values into the cache.
     */
    protected $cacheKey = 'application.persistent.config';

    /**
     * @var int|\DateInterval the TTL (e.g. lifetime) value for the persistent config cache.
     */
    protected $cacheTtl = 3600 * 24;

    /**
     * Bootstrap replacement of the regular config with the persistent one.
     *
     * @return void
     */
    public function boot() : void
    {
        $this->app->extend('config', function ($originConfig) {
            return (new PersistentRepository($originConfig, $this->storage()))
                ->setCache($this->app->make('cache.store'))
                ->setCacheKey($this->cacheKey)
                ->setCacheTtl($this->cacheTtl)
                ->setItems($this->items());
        });
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->app->extend('command.config.cache', function () {
            return new ConfigCacheCommand($this->app->make('files'));
        });
    }

    /**
     * Defines the storage for the persistent config.
     *
     * @return \Illuminatech\Config\StorageContract
     */
    abstract protected function storage(): StorageContract;

    /**
     * Defines configuration items, which values should be placed in persistent storage.
     *
     * @return \Illuminatech\Config\Item[]|array persistent config items.
     */
    abstract protected function items(): array;
}
