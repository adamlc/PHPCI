<?php
/**
 * PHPCI - Continuous Integration for PHP
 *
 * @copyright    Copyright 2013, Block 8 Limited.
 * @license      https://github.com/Block8/PHPCI/blob/master/LICENSE.md
 * @link         http://www.phptesting.org/
 */

namespace PHPCI\Plugin;

use PHPCI;
use PHPCI\Builder;
use PHPCI\Model\Build;

/**
 * PHP Lint Plugin - Provides access to PHP lint functionality.
 * @author       Dan Cryer <dan@block8.co.uk>
 * @package      PHPCI
 * @subpackage   Plugins
 */
class Lint implements PHPCI\Plugin
{
    protected $directories;
    protected $recursive = true;
    protected $ignore;
    protected $phpci;

    public function __construct(Builder $phpci, Build $build, array $options = array())
    {
        $this->phpci        = $phpci;
        $this->directories    = array('');
        $this->ignore = $phpci->ignore;

        if (!empty($options['directory'])) {
            $this->directories[] = $options['directory'];
        }

        if (!empty($options['directories'])) {
            $this->directories = $options['directories'];
        }

        if (array_key_exists('recursive', $options)) {
            $this->recursive = $options['recursive'];
        }
    }

    /**
     * Executes parallel lint
     */
    public function execute()
    {
        $this->phpci->quiet = true;
        $success = true;

        $php = $this->phpci->findBinary('php');

        foreach ($this->directories as $dir) {
            if (!$this->lintDirectory($php, $dir)) {
                $success = false;
            }
        }

        $this->phpci->quiet = false;

        return $success;
    }

    protected function lintItem($php, $item, $itemPath)
    {
        $success = true;

        if ($item->isFile() && $item->getExtension() == 'php' && !$this->lintFile($php, $itemPath)) {
            $success = false;
        } elseif ($item->isDir() && $this->recursive && !$this->lintDirectory($php, $itemPath . '/')) {
            $success = false;
        }

        return $success;
    }

    protected function lintDirectory($php, $path)
    {
        $success = true;
        $directory = new \DirectoryIterator($this->phpci->buildPath . $path);

        foreach ($directory as $item) {
            if ($item->isDot()) {
                continue;
            }

            $itemPath = $path . $item->getFilename();

            if (in_array($itemPath, $this->ignore)) {
                continue;
            }

            if (!$this->lintItem($php, $item, $itemPath)) {
                $success = false;
            }
        }

        return $success;
    }

    protected function lintFile($php, $path)
    {
        $success = true;

        if (!$this->phpci->executeCommand($php . ' -l "%s"', $this->phpci->buildPath . $path)) {
            $this->phpci->logFailure($path);
            $success = false;
        }

        return $success;
    }
}
