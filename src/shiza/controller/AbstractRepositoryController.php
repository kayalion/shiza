<?php

namespace shiza\controller;

use ride\library\i18n\translator\Translator;
use ride\library\orm\OrmManager;

abstract class AbstractRepositoryController extends AbstractController {

    protected function resolveRepository(OrmManager $orm, $repository) {
        $repositoryModel = $orm->getRepositoryModel();

        $repository = $this->getEntry($repositoryModel, $repository, 'slug');
        if (!$repository) {
            $this->response->setNotFound();

            return false;
        }

        $orm->getRepositoryRecentModel()->pushRepository($repository, $this->getUser());

        return $repository;
    }

    protected function resolveBranch(array $branches, array $pathTokens, &$branch, &$path) {
        foreach ($branches as $branch) {
            $branchTokens = explode('/', $branch);

            foreach ($branchTokens as $index => $branchToken) {
                if (!isset($pathTokens[$index]) || $pathTokens[$index] != $branchToken) {
                    continue 2;
                }
            }

            $path = ltrim(str_replace($branch, '', implode('/', $pathTokens)), '/');

            return;
        }

        $branch = null;
        $path = null;
    }

    protected function getBuildManagerOptions(Translator $translator) {
        return $this->getManagerOptions($translator, 'shiza\\manager\\build\\BuildManager', 'manager.build.');
    }

    protected function getDeployManagerOptions(Translator $translator) {
        return $this->getManagerOptions($translator, 'shiza\\manager\\deploy\\DeployManager', 'manager.deploy.');
    }

    protected function getVcsManagerOptions(Translator $translator) {
        return $this->getManagerOptions($translator, 'shiza\\manager\\vcs\\VcsManager', 'manager.vcs.');
    }

}
