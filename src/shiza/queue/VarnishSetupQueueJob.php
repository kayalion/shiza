<?php

namespace shiza\queue;

use shiza\exception\VarnishCompileException;

use shiza\manager\varnish\VarnishManager;

use shiza\orm\entry\ProjectEntry;

use \Exception;

/**
 * Queue job to setup a varnish instance
 */
class VarnishSetupQueueJob extends AbstractVarnishQueueJob {

    const TITLE = 'Initialize Varnish server';

    protected function performTask(ProjectEntry $project, VarnishManager $manager) {
        $this->updateQueueJob('Setting up Varnish');

        $server = $project->getVarnishServer();

        try {
            $projects = $this->orm->getProjectModel()->find(array('filter' => array('varnishServer' => $server->getId())));

            $manager->setupVarnish($project, $projects);

            $project->setSkipQueue();
            $this->orm->getProjectModel()->save($project);

            $this->logSuccess($this->title, 'Initialized varnish instance on ' . $server->getHost() . ':' . $project->getVarnishPort());
        } catch (Exception $exception) {
            if ($exception instanceof VarnishCompileException) {
                $this->logError($this->title, $exception->getMessage());
            } else {
                $this->logError($this->title, 'Could not initialize varnish instance on ' . $server->getHost(), $exception);
            }

            throw $exception;
        }
    }


}
