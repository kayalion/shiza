<?php

namespace shiza\queue;

use shiza\exception\VarnishCompileException;

use shiza\manager\varnish\VarnishManager;

use shiza\orm\entry\ProjectEntry;

use \Exception;

/**
 * Queue job to delete a varnish instance
 */
class VarnishDeleteQueueJob extends AbstractVarnishQueueJob {

    const TITLE = 'Delete Varnish instance';

    protected function performTask(ProjectEntry $project, VarnishManager $manager) {
        $this->updateQueueJob('Deleting Varnish instance');

        $server = $project->getVarnishServer();

        try {
            $projects = $this->orm->getProjectModel()->find(array('filter' => array('varnishServer' => $server->getId())));

            $port = $project->getVarnishPort();

            $manager->deleteVarnish($project, $projects);

            $project->setSkipQueue();
            $this->orm->getProjectModel()->save($project);

            $this->logSuccess($this->title, 'Deleted varnish instance ' . $server->getHost() . ':' . $port);
        } catch (Exception $exception) {
            if ($exception instanceof VarnishCompileException) {
                $this->logError($this->title, $exception->getMessage());
            } else {
                $this->logError($this->title, 'Could not delete varnish instance ' . $server->getHost() . ':' . $port, $exception);
            }

            throw $exception;
        }
    }


}
