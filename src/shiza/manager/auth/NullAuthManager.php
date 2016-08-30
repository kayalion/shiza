<?php

namespace shiza\manager\auth;

use shiza\orm\entry\ProjectEntry;
use shiza\orm\entry\ServerEntry;

class NullAuthManager implements AuthManager {

    public function hasUser(ServerEntry $server, ProjectEntry $project) {
        return null;
    }

    public function addUser(ServerEntry $server, ProjectEntry $project) {
        return null;
    }

    public function deleteUser(ServerEntry $server, ProjectEntry $project) {
        return null;
    }

    public function authorizeSshKeys(ServerEntry $server, ProjectEntry $project) {
        return null;
    }

    public function getHomeDirectory($username) {
        return null;
    }

}
