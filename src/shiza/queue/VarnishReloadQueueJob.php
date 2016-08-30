<?php

namespace shiza\queue;

use shiza\exception\VarnishCompileException;

use shiza\manager\varnish\VarnishManager;

use shiza\orm\entry\ProjectEntry;

use \Exception;

/**
 * Queue job to reload a varnish instance
 */
class VarnishReloadQueueJob extends AbstractVarnishQueueJob {

    const TITLE = 'Reload Varnish server';

    protected function performTask(ProjectEntry $project, VarnishManager $manager) {
        $this->updateQueueJob('Reloading Varnish instance');

        $server = $project->getVarnishServer();

        try {
            $manager->reloadVarnish($project);

            $this->logSuccess($this->title, 'Reloaded varnish instance ' . $server->getHost() . ':' . $project->getVarnishPort());
        } catch (Exception $exception) {
            if ($exception instanceof VarnishCompileException) {
                $this->logError($this->title, $exception->getMessage());
            } else {
                $this->logError($this->title, 'Could not reload varnish instance ' . $server->getHost() . ':' . $project->getVarnishPort(), $exception);
            }

            throw $exception;
        }
    }


}
