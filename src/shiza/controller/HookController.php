<?php

namespace shiza\controller;

use ride\library\orm\OrmManager;

use ride\web\base\controller\AbstractController as RideAbstractController;

class HookController extends RideAbstractController {

    public function githubAction(OrmManager $orm) {
        $event = $this->request->getHeader("X-Github-Event");
        if (!$event) {
            $this->response->setBadRequest();

            return;
        } elseif ($event != 'push') {
            return;
        }

        $data = json_decode($this->request->getBody(), true);

        if (!isset($data['repository']['name']) || !isset($data['repository']['clone_url'])) {
            $this->response->setBadRequest();

            return;
        }

        $repositoryModel = $orm->getRepositoryModel();
        $repository = $repositoryModel->getBy(array(
            'filter' => array(
                'url' => $data['repository']['clone_url'],
            ),
        ));

        if ($repository) {
            $repositoryModel->updateRepository($repository);
        } else {
            $repository = $repositoryModel->createEntry();
            $repository->setVcsManager('git');
            $repository->setName($data['repository']['name']);
            $repository->setUrl($data['repository']['clone_url']);

            $repositoryModel->save($repository);
        }
    }

}
