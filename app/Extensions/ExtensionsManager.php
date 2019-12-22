<?php

namespace Azuriom\Extensions;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class ExtensionsManager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    private $app;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;

    /**
     * The composer instance.
     *
     * @var \Illuminate\Support\Composer $composer
     */
    private $composer;

    /**
     * The loaded and enabled plugins
     *
     * @var \Illuminate\Support\Collection
     */
    private $loadedPlugins;

    /**
     * The plugins route descriptions.
     *
     * @var \Illuminate\Support\Collection
     */
    private $routeDescriptions;

    /**
     * The admin panel navigation.
     *
     * @var \Illuminate\Support\Collection
     */
    private $adminNavItems;

    /**
     * Create a new ExtensionsManager instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     */
    public function __construct(Application $app, Filesystem $files, Composer $composer)
    {
        $this->app = $app;
        $this->files = $files;
        $this->composer = $composer;
        $this->routeDescriptions = collect();
        $this->adminNavItems = collect();
    }

    /**
     * Get the current active theme.
     *
     * @return string|null
     */
    public function getCurrentTheme()
    {
        return setting('theme');
    }

    /**
     * Get the loaded plugins.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLoadedPlugins()
    {
        return $this->loadedPlugins;
    }

    public function isPluginLoaded($plugin)
    {
        return $this->loadedPlugins->contains($plugin);
    }

    /**
     * Get the plugins descriptions
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPluginsDescriptions()
    {
        return $this->findPlugins()->mapWithKeys(function ($path) {
            $info = $this->getPluginDescription($path);

            return $info ? [$path => $info] : [];
        });
    }

    /**
     * Get the themes descriptions
     *
     * @return \Illuminate\Support\Collection
     */
    public function getThemesDescriptions()
    {
        return $this->findThemes()->mapWithKeys(function ($path) {
            $info = $this->getThemeDescription($path);

            return $info ? [$path => $info] : [];
        });
    }

    /**
     * Discover the plugins names from the plugins directory
     *
     * @return \Illuminate\Support\Collection
     */
    public function findPlugins()
    {
        if (! $this->files->exists(plugin_path())) {
            return collect();
        }

        return collect($this->files->directories(plugin_path()))->map(function ($path) {
            return $this->files->name($path);
        });
    }

    /**
     * Discover the themes names from the themes folder
     *
     * @return \Illuminate\Support\Collection
     */
    public function findThemes()
    {
        return collect($this->files->directories(theme_path()))->map(function ($path) {
            return $this->files->name($path);
        });
    }

    /**
     * Get the content of the plugin.json of the plugin
     *
     * @param  string  $plugin
     * @return mixed|null
     */
    public function getPluginDescription(string $plugin)
    {
        $json = $this->getJson(plugin_path($plugin.'/plugin.json'));

        if ($json !== null && ! isset($json->enabled)) {
            $json->enabled = false;
        }

        return $json;
    }

    /**
     * Get the content of the theme.json of the theme
     *
     * @param  string  $theme
     * @return mixed|null
     */
    public function getThemeDescription(string $theme)
    {
        return $this->getJson(theme_path($theme.'/theme.json'));
    }

    /**
     * Get the content of the config.json of the theme is present
     *
     * @param  string  $theme
     * @return array|null
     */
    public function getThemeConfig(string $theme)
    {
        return $this->getJson(theme_path($theme.'/config.json'), true);
    }

    /**
     * Read the content of a json
     *
     * @param  string  $path
     * @param  bool  $asoc
     * @return mixed|null
     */
    protected function getJson(string $path, bool $asoc = false)
    {
        if (! $this->files->isFile($path)) {
            return null;
        }

        try {
            return json_decode($this->files->get($path), $asoc);
        } catch (FileNotFoundException $e) {
            return null;
        }
    }

    public function loadPlugins()
    {
        if ($this->files->isFile($this->getCachedPluginsPath())) {
            $plugins = collect($this->files->getRequire($this->getCachedPluginsPath()));
        } else {
            $plugins = $this->cachePlugins();
        }

        $this->loadedPlugins = $plugins->values();

        return $plugins;
    }

    public function cachePlugins()
    {
        $plugins = $this->findPlugins()->mapWithKeys(function ($path) {
            $info = $this->getPluginDescription($path);

            return $info && $info->enabled ? [$path => $info] : [];
        });

        $this->files->put($this->getCachedPluginsPath(), '<?php return '.var_export($plugins->toArray(), true).';');

        return $plugins;
    }

    public function setPluginEnabled(string $plugin, bool $enabled)
    {
        $description = $this->getPluginDescription($plugin);

        if ($description === null) {
            return false;
        }

        $description->enabled = $enabled;

        $json = json_encode($description, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->files->put(plugin_path($plugin.'/plugin.json'), $json);
        $this->cachePlugins();

        return true;
    }

    /**
     * Get the path to the cached plugins.php file.
     *
     * @return string
     */
    public function getCachedPluginsPath()
    {
        return $this->app->bootstrapPath('/cache/plugins.php');
    }

    public function dumpAutoload()
    {
        if ($this->app->isProduction()) {
            $this->composer->dumpOptimized();
        } else {
            $this->composer->dumpAutoloads();
        }
    }

    public function addRouteDescription(array $items)
    {
        foreach ($items as $key => $value) {
            $this->routeDescriptions->put($key, $value);
        }
    }

    public function addAdminNavItem(array $items)
    {
        foreach ($items as $key => $value) {
            $this->adminNavItems->put($key, $value);
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getRouteDescriptions()
    {
        return $this->routeDescriptions;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getAdminNavItems()
    {
        return $this->adminNavItems;
    }
}
