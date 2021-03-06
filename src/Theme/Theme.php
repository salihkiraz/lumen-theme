<?php
namespace Lukeed\Theme;

use Lukeed\Theme\Exceptions\ThemeNotFoundException;
use Illuminate\Contracts\Container\Container;
use Lukeed\Theme\Exceptions\ThemeInfoAttributeException;
use Lukeed\Theme\Contracts\ThemeInterface;

/**
 * Theme
 *
 * @author Karlo Mikuš
 * @author Luke Edwards
 * @version 1.0.3
 * @package Lukeed\Theme
 */
class Theme implements ThemeInterface
{
    /**
     * Scanned themes
     * @var array|ThemeInfo[]
     */
    private $themes;

    /**
     * View finder
     * @var \Illuminate\View\Factory
     */
    private $view;

    /**
     * Base theme folder
     * @var string
     */
    private $basePath;

    /**
     * Current active theme
     * @var string|null
     */
    private $activeTheme = null;

    /**
     * Setup default view finder and view paths.
     *
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        // Default themes path
        $this->basePath = config('theme.path');

        // Config view finder
        $paths = $app['config']['view.paths'];
        $this->view = $app->make('view');
        $this->view->setFinder(new ThemeViewFinder($app['files'], (array) $paths));

        // Scan themes
        $this->scanThemes();
    }

    /**
     * Set current active theme
     *
     * @param string $theme Theme directory
     * @throws ThemeNotFoundException
     */
    public function set($theme)
    {
        if (!$this->has($theme)) {
            throw new ThemeNotFoundException($theme);
        }

        $this->loadTheme($theme);
    }

    /**
     * Get all found themes
     *
     * @return array|ThemeInfo[]
     */
    public function all()
    {
        return $this->themes;
    }

    /**
     * @depracted Use all() method
     */
    public function getThemes()
    {
        return $this->all();
    }

    /**
     * Returns theme information.
     *
     * @param string Theme directory
     * @return null|ThemeInfo
     */
    public function get($theme = null)
    {
        if (is_null($theme)) {
            return $this->themes[$this->activeTheme];
        }

        return $this->themes[$theme];
    }

    /**
     * Check if theme exists
     *
     * @param $theme
     * @return bool
     */
    public function has($theme)
    {
        return array_key_exists($theme, $this->themes);
    }

    /**
     * @depracted Use has() method
     */
    public function themeExists($theme)
    {
        return $this->has($theme);
    }

    /**
     * Set base themes folder path
     *
     * @param $path
     */
    public function setDefaultThemePath($path)
    {
        $this->basePath = $path;
        $this->scanThemes();
    }

    /**
     * Load a theme
     *
     * @param string $theme
     * @throws \Exception
     */
    private function loadTheme($theme)
    {
        if (!isset($theme)) {
            return;
        }

        $th = $this->findThemeByDirectory($theme);

        if (isset($th)) {
            $viewFinder = $this->view->getFinder();

            $viewFinder->prependPath($th->getPath());
            if (!is_null($th->getParent())) {
                $this->loadTheme($th->getParent());
            }

            $this->activeTheme = $theme;
        }
    }

    /**
     * Find a theme from all scanned themes
     *
     * @param string $directory
     * @return null|ThemeInfo
     */
    private function findThemeByDirectory($directory)
    {
        if (isset($this->themes[$directory])) {
            return $this->themes[$directory];
        }

        return null;
    }

    /**
     * Scan for all available themes
     *
     * @throws ThemeInfoAttributeException
     */
    private function scanThemes()
    {
        $themeDirectories = glob($this->basePath . '/*', GLOB_ONLYDIR);

        $themes = [];
        foreach ($themeDirectories as $themePath) {
            $json = $themePath . '/theme.json';

            if (file_exists($json)) {
                $contents = file_get_contents($json);
                if (!$contents === false) {
                    $th = $this->parseThemeInfo(json_decode($contents, true));
                    $themes[$th->getDirectory()] = $th;
                }
            }
        }

        $this->themes = $themes;

        // if no active theme & if found 1+ theme, set first as default
        if (count($themes) && !$this->activeTheme) {
            $this->set(array_keys($themes)[0]);
        }
    }

    /**
     * Find theme views path
     *
     * @param $directory
     * @return string
     */
    private function findPath($directory)
    {
        $path = [];
        $path[] = $this->basePath;
        $path[] = $directory;
        $path[] = 'views';

        return implode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Parse theme json file
     *
     * @param array $info
     * @return ThemeInfoInterface
     * @throws ThemeInfoAttributeException
     */
    private function parseThemeInfo(array $info)
    {
        $themeInfo = new ThemeInfo();

        $required = ['name', 'author', 'directory'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $info)) {
                throw new ThemeInfoAttributeException($key);
            }
        }

        $themeInfo->setName($info['name']);
        $themeInfo->setAuthor($info['author']);
        $themeInfo->setDirectory(strtolower($info['directory']));

        if (isset($info['description'])) {
            $themeInfo->setDescription($info['description']);
        }
        if (isset($info['version'])) {
            $themeInfo->setVersion($info['version']);
        }
        if (isset($info['parent'])) {
            $themeInfo->setParent($info['parent']);
        }

        $themeInfo->setPath($this->findPath($info['directory']));

        return $themeInfo;
    }
}
