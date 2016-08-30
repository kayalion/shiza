<?php

namespace shiza\orm\model;

class SshKeyModel extends AbstractAuthorizeModel {

    protected function getUsedProjects($entry) {
        $projectModel = $this->orm->getProjectModel();
        $keyChainModel = $this->orm->getKeyChainModel();

        $query = $keyChainModel->createQuery();
        $query->addCondition('{sshKeys.id} = %1%', $entry->getId());

        $keyChains = $query->query();

        $query = $projectModel->createQuery();
        if ($keyChains) {
            $query->addCondition('{sshKeys.id} = %1% OR {keyChains.id} IN %2%', $entry->getId(), array_keys($keyChains));
        } else {
            $query->addCondition('{sshKeys.id} = %1%', $entry->getId());
        }

        return $query->query();
    }

}
