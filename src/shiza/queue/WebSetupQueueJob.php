<?php

namespace shiza\queue;

use shiza\manager\web\WebManager;

use shiza\orm\entry\ProjectEnvironmentEntry;

use \Exception;

/**
 * Queue job to create a backup of a web environment
 */
class WebSetupQueueJob extends AbstractWebQueueJob {

    const TITLE = 'Sync web environment settings';

    protected function performTask(ProjectEnvironmentEntry $environment, WebManager $manager) {
        $this->updateQueueJob('Initializing web environment settings');

        $server = $environment->getServer();
        $environmentModel = $this->orm->getProjectEnvironmentModel();

        try {
            $manager->updateEnvironment($environment);

            if (!$environment->isDeleted()) {
                $environment->setSkipQueue();
                $environment->setQueueJobStatus(null);
                $environmentModel->save($environment);

                $this->logSuccess($this->title, 'Initialized ' . $environment . ' on ' . $server->getHost());
            } else {
                $environmentModel->delete($environment);

                $this->logSuccess($this->title, 'Deleted ' . $environment . ' from ' . $server->getHost());
            }

            $this->serverService->getSshSystemForServer($server)->disconnect();
        } catch (\Exception $exception) {
            $environment->setSkipQueue();
            $environmentModel->save($environment);

            if (!$environment->isDeleted()) {
                $this->logError($this->title, 'Could not initialize web environment ' . $environment . ' on ' . $server->getHost(), $exception);
            } else {
                $this->logError($this->title, 'Could not delete web environment ' . $environment . ' from ' . $server->getHost(), $exception);
            }

            $this->serverService->getSshSystemForServer($server)->disconnect();

            throw $exception;
        }
    }

}
