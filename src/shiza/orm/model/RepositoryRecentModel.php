<?php

namespace shiza\orm\model;

use ride\library\orm\model\GenericModel;
use ride\library\security\model\User;

use shiza\orm\entry\RepositoryEntry;

/**
 * Model for the recent repositories of a user
 */
class RepositoryRecentModel extends GenericModel {

    /**
     * Pushes a repository to the recent list of a user
     * @param \shiza\orm\entry\RepositoryEntry $repository
     * @param \ride\library\security\model\User $user
     * @return null
     */
    public function pushRepository(RepositoryEntry $repository, User $user = null) {
        if (!$user || !$repository->getId()) {
            return;
        }

        $query = $this->createQuery();
        $query->addCondition('{repository} = %1% AND {user} = %2%', $repository->getId(), $user->getId());

        $this->delete($query->query());

        $entry = $this->createEntry();
        $entry->setRepository($repository);
        $entry->setUser($user->getId());

        $this->save($entry);
    }

    /**
     * Gets the recent repositories for a user
     * @param \ride\library\security\model\User $user
     * @return array
     */
    public function findRepositoriesByUser(User $user, $limit = 5) {
        $repositories = array();

        $query = $this->createQuery();
        $query->addCondition('{user} = %1%', $user->getId());
        $query->addOrderBy('self.id DESC');
        $query->setLimit($limit);

        $result = $query->query();
        foreach ($result as $entry) {
            $repository = $entry->getRepository();
            if (!$repository) {
                $this->delete($entry);

                continue;
            }

            $repositories[$repository->getId()] = $repository;
        }

        return $repositories;
    }

}
