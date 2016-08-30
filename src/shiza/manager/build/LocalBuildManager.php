<?php

namespace shiza\manager\build;

use ride\library\system\System;

use \Exception;

/**
 * Local manager of a repository builder
 */
class LocalBuildManager extends AbstractBuildManager {

    private $system;

    public function __construct(System $system) {
        $this->system = $system;
    }

    /**
     * Invokes the script of the builder for the provided revision
     * @return null
     */
    protected function invokeScript() {
        $fileSystem = $this->system->getFileSystem();

        $directory = $fileSystem->getTemporaryFile();
        $directory->delete();
        $directory->create();
        $directoryAbsolute = $directory->getAbsolutePath();

        $this->appendOutput("# Created working directory " . $directoryAbsolute);

        $cwd = getcwd();
        $exception = null;

        try {
            chdir($directoryAbsolute);

            $variables = $this->getCommandVariables($directoryAbsolute);

            $commands = array();
            if ($this->builder->getWillCheckout()) {
                $commands = array_merge($commands, $this->vcsManager->getCheckoutCommands());
            }
            $commands = array_merge($commands, explode("\n", $this->builder->getScript()));

            foreach ($commands as $command) {
                $command = $this->parseCommandVariables($command, $variables);

                $this->appendOutput($command);

                if (substr($command, 0, 3) == 'cd ') {
                    chdir(substr($command, 3));

                    continue;
                }

                // if (strpos($command, ' 2>') === false) {
                    // $command .= ' 2>&1';
                // }

                $output = $this->system->executeInShell(array('export PATH=/usr/local/bin:/usr/bin:$PATH', $command), $code);
                if ($code != 0) {
                    throw new Exception('Command returned code ' . $code . ': ' . $command);
                }

                $output = array_slice($output, 4);

                $this->appendCommandOutput($output);
            }
        } catch (Exception $exception) {
            $this->setException($exception);
        }

        chdir($cwd);
        $directory->delete();

        $this->appendOutput("# Deleted working directory " . $directory->getAbsolutePath());
    }

}
