<?php

namespace shiza\queue;

use shiza\exception\VarnishCompileException;

use shiza\manager\varnish\VarnishManager;

use shiza\orm\entry\ProjectEntry;

use \Exception;

/**
 * Queue job to restart a varnish instance
 */
class VarnishRestartQueueJob extends AbstractVarnishQueueJob {

    const TITLE = 'Restart Varnish server';

    protected function performTask(ProjectEntry $project, VarnishManager $manager) {
        $this->updateQueueJob('Restarting Varnish instance');

        $server = $project->getVarnishServer();

        try {
            $manager->restartVarnish($project);

            $this->logSuccess($this->title, 'Restarted varnish instance ' . $server->getHost() . ':' . $project->getVarnishPort());
        } catch (Exception $exception) {
            if ($exception instanceof VarnishCompileException) {
                $this->logError($this->title, $exception->getMessage());
            } else {
                $this->logError($this->title, 'Could not restart varnish instance ' . $server->getHost() . ':' . $project->getVarnishPort(), $exception);
            }

            throw $exception;
        }
    }


}
