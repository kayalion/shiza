<?php

namespace shiza\manager\auth;

use shiza\orm\entry\ProjectEntry;
use shiza\orm\entry\ServerEntry;

interface AuthManager {

    public function hasUser(ServerEntry $server, ProjectEntry $project);

    public function addUser(ServerEntry $server, ProjectEntry $project);

    public function deleteUser(ServerEntry $server, ProjectEntry $project);

    public function authorizeSshKeys(ServerEntry $server, ProjectEntry $project);

    public function getHomeDirectory($username);

}
