<?php

namespace shiza\manager\build;

use shiza\manager\vcs\VcsManager;

use shiza\orm\entry\RepositoryBuilderEntry;

use \Exception;

/**
 * Abstract implementation for the manager of a repository builder
 */
abstract class AbstractBuildManager implements BuildManager {

    private $output;

    private $exception;

    protected $vcsManager;

    protected $builder;

    protected $revision;

    /**
     * Runs the script of the builder for the provided revision
     * @param \shiza\mangaer\vcs\VcsManager $vcsManager
     * @param \shiza\orm\entry\RepositoryBuilderEntry $builder
     * @param string $revision
     * @param \Exception $exception
     * @return string Output of the builder
     */
    public function runScript(VcsManager $vcsManager, RepositoryBuilderEntry $builder, $revision, Exception &$exception = null) {
        $this->vcsManager = $vcsManager;
        $this->builder = $builder;
        $this->revision = $revision;
        $this->exception = null;
        $this->output = '';

        $this->invokeScript();

        $exception = $this->exception;

        return $this->output;
    }

    abstract protected function invokeScript();

    protected function setException(Exception $exception) {
        $this->exception = $exception;
    }

    /**
     * Gets the variables to parse in script commands
     * @param string $directory
     * @return array
     */
    protected function getCommandVariables($directory) {
        return array(
            'branch' => $this->builder->getBranch(),
            'dir' => $directory,
            'repository' => $this->builder->getRepository()->getUrl(),
            'revision' => $this->revision,
        );
    }

    protected function parseCommandVariables($command, array $variables) {
        foreach ($variables as $variable => $value) {
            $command = str_replace('[[' . $variable . ']]', $value, $command);
        }

        return $command;
    }

    protected function appendOutput($output) {
        $this->output .= $output . "\n";
    }

    /**
     * Parses the output of a command to log output
     * @param array $output
     * @return string
     */
    protected function appendCommandOutput(array $output) {
        foreach ($output as $line) {
            $this->appendOutput('# | ' . $line);
        }
    }

}
