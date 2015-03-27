<?php
/**
 * PHPCI - Continuous Integration for PHP
 *
 * @copyright    Copyright 2014, Block 8 Limited.
 * @license      https://github.com/Block8/PHPCI/blob/master/LICENSE.md
 * @link         https://www.phptesting.org/
 */

namespace PHPCI\Plugin;

use PHPCI;
use PHPCI\Builder;
use PHPCI\Model\Build;
use PHPCI\Helper\Lang;

/**
* Docker Plugin - Provides access to Docker functionality.
* @author       Lewis Wright <lewis@vivait.co.uk>
* @package      PHPCI
* @subpackage   Plugins
*/
class Docker implements PHPCI\Plugin
{
    protected $phpci;
    protected $build;

    protected $commands;
    protected $resetModified = false;

    /**
     * Set up the plugin, configure options, etc.
     *
     * $options['directory'] Output Directory. Default: %BUILDPATH%
     * $options['playbook'] Path of playbook file
     * $options['inventory'] Path of inventory file. Defaults to ansible default
     *
     * @param Builder $phpci
     * @param Build $build
     * @param array $options
     * @throws \Exception
     */
    public function __construct(Builder $phpci, Build $build, array $options = array())
    {
        $this->phpci = $phpci;
        $this->build = $build;

        if (array_key_exists('reset_modified', $options)) {
            $this->resetModified = (bool)$options['reset_modified'];
        }
    }

    /**
    * Executes Docker
    */
    public function execute()
    {
        $build = $this->build;
        $path = $this->phpci->buildPath;

        $commit = strtolower($build->getId());

        $dockerLocation = $this->phpci->findBinary('docker');

        if (!$dockerLocation) {
            $this->phpci->logFailure(Lang::get('could_not_find', 'docker'));
            return false;
        }

        if ($this->resetModified) {
            $this->phpci->log('Reseting modified time');
            $this->phpci->executeCommand('find %s | fgrep -v ./.git/ | xargs touch -t 200001010000.00', $path);
        }

        $cmd = $dockerLocation . ' build --rm -t build-%s %s';

        $this->phpci->log('Running docker build');
        $this->phpci->log(sprintf($cmd, $commit, $path));

        return $this->phpci->executeCommand($cmd, $commit, $path);
    }
}
