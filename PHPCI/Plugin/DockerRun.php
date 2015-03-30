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
* Docker Run Plugin - Lets you run commands through docker
* @author       Lewis Wright <lewis@vivait.co.uk>
* @package      PHPCI
* @subpackage   Plugins
*/
class DockerRun implements PHPCI\Plugin
{
    protected $phpci;
    protected $build;

    protected $commands;
    protected $resetModified = false;
    protected $volumes = [];
    protected $tag;
    protected $run;
    protected $remove = true;

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

        if (isset($options['volumes']) && is_array($options['volumes'])) {
            $this->volumes = $options['volumes'];
        }

        if (isset($options['network'])) {
            $this->network = $options['network'];
        }

        if (isset($options['remove'])) {
            $this->remove = (bool)$options['remove'];
        }

        if (isset($options['tag'])) {
            $this->tag = $options['tag'];
        }
        else {
            $this->tag = 'project-'. $build->getProjectId() .':'. $build->getId();
        }

        if (isset($options['run'])) {
            $this->run = (array)$options['run'];
        }
        else {
            throw new \Exception(sprintf('No run command provided for docker run plugin'));
        }

    }

    /**
    * Executes Docker
    */
    public function execute()
    {
        $volumeString = $networkString = '';

        $dockerLocation = $this->phpci->findBinary('docker');

        if (!$dockerLocation) {
            $this->phpci->logFailure(Lang::get('could_not_find', 'docker'));
            return false;
        }

        if ($this->network) {
            $networkString = '--net '. escapeshellarg($this->network);
        }

        foreach ($this->volumes as $from => $to) {
            $volumeString .= ' -v '. escapeshellarg($from) .':'. escapeshellarg($to);
        }

        $cmd = $dockerLocation . ' run %s %s %s %s %s';

        $rmString = $this->remove ? '--rm' : '';

        $this->phpci->log('Running docker container');
        $this->phpci->log(sprintf($cmd, $rmString, $volumeString, $networkString, $this->tag, $this->run));


        return $this->phpci->executeCommand($cmd, $rmString, $volumeString, $networkString, $this->tag, $this->run);
    }
}
