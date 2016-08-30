<?php

namespace shiza\orm\entry;

use ride\application\orm\entry\ProjectDatabaseEntry as OrmProjectDatabaseEntry;

class ProjectDatabaseEntry extends OrmProjectDatabaseEntry {

    public function __toString() {
        return $this->getDatabaseName();
    }

    public function getDatabaseName() {
        return $this->getProject()->getUsername() . '_' . $this->getName();
    }

}
