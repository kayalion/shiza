<?php

namespace shiza\orm\model;

class KeyChainModel extends AbstractAuthorizeModel {

    protected function getUsedProjects($entry) {
        $projectModel = $this->orm->getProjectModel();

        $query = $projectModel->createQuery();
        $query->addCondition('{keyChains.id} = %1%', $entry->getId());

        return $query->query();
    }

}
