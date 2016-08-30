<?php

namespace shiza\orm\model;

use ride\library\orm\model\GenericModel;
use ride\library\security\model\User;

use shiza\orm\entry\ProjectEntry;

/**
 * Model for the recent projects of a user
 */
class ProjectRecentModel extends GenericModel {

    /**
     * Pushes a project to the recent list of a user
     * @param \shiza\orm\entry\ProjectEntry $project
     * @param \ride\library\security\model\User $user
     * @return null
     */
    public function pushProject(ProjectEntry $project, User $user = null) {
        if (!$user || !$project->getId()) {
            return;
        }

        $query = $this->createQuery();
        $query->addCondition('{project} = %1% AND {user} = %2%', $project->getId(), $user->getId());
        $result = $query->query();

        $this->delete($result);

        $entry = $this->createEntry();
        $entry->setProject($project);
        $entry->setUser($user->getId());

        $this->save($entry);
    }

    /**
     * Gets the recent projects for a user
     * @param \ride\library\security\model\User $user
     * @return array
     */
    public function findProjectsByUser(User $user, $limit = 5) {
        $projects = array();

        $query = $this->createQuery();
        $query->addCondition('{user} = %1%', $user->getId());
        $query->addOrderBy('self.id DESC');
        $query->setLimit($limit);

        $result = $query->query();
        foreach ($result as $entry) {
            $project = $entry->getProject();
            if (!$project) {
                $this->delete($entry);

                continue;
            }

            $projects[$project->getId()] = $project;
        }

        return $projects;
    }

}
