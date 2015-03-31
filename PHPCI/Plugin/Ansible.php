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
* Ansible Plugin - Provides access to Ansible functionality.
* @author       Lewis Wright <lewis@vivait.co.uk>
* @package      PHPCI
* @subpackage   Plugins
*/
class Ansible implements PHPCI\Plugin
{
    protected $phpci;
    protected $build;
    protected $inventory;
    protected $playbook;
    protected $privateKey;

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

        if (array_key_exists('inventory', $options)) {
            $this->inventory = $options['inventory'];
        }

        // Default to using the project private key
        if (!array_key_exists('use_private_key', $options) || $options['use_private_key']) {
            $this->privateKey = $this->build->getProject()->getSshPrivateKey();
        }

        if (array_key_exists('playbook', $options)) {
            $this->playbook = $options['playbook'];
        } else {
            throw new \Exception('Please define the playbook for ansible plugin!');
        }

        if (isset($options['executable'])) {
            $this->executable = $options['executable'];
        } else {
            $this->executable = $this->phpci->findBinary('ansible-playbook');
        }
    }

    /**
    * Executes Ansible playbook
    */
    public function execute()
    {
        $ansibleLocation = $this->executable;

        if (!$ansibleLocation) {
            $this->phpci->logFailure(Lang::get('could_not_find', 'ansible-playbook'));
            return false;
        }

        $cmd = $ansibleLocation . ' %s ';

        if ($this->inventory) {
            $cmd .= ' -i '. $this->inventory;
        }

        if ($this->privateKey) {
            $cmd .= ' -e "ansible_ssh_private_key_file='. escapeshellarg($this->writeSshKey($this->privateKey)) .'"';
        }

        $this->phpci->log(sprintf($cmd, $this->playbook));

        return $this->phpci->executeCommand($cmd, $this->playbook);
    }

    /**
     * Create an SSH key file on disk for this build.
     * @param string $key
     * @return string
     */
    protected function writeSshKey($key)
    {
        $keyFile = tempnam(sys_get_temp_dir(), 'phpci-'. $this->build->getId());

        // Write the contents of this project's git key to the file:
        file_put_contents($keyFile, $key);
        chmod($keyFile, 0600);

        // Return the filename:
        return $keyFile;
    }
}
