<?php

namespace shiza\manager\auth;

use ride\library\system\SshSystem;

use shiza\orm\entry\ProjectEntry;
use shiza\orm\entry\ServerEntry;

use shiza\service\ServerService;

use \Exception;

class SshAuthManager implements AuthManager {

    public function __construct(ServerService $serverService, $offset = 10000) {
        $this->serverService = $serverService;
        $this->offset = $offset;
    }

    public function hasUser(ServerEntry $server, ProjectEntry $project) {
        $system = $this->serverService->getSshSystemForServer($server);

        return $this->testHasUser($system, $project->getUsername());
    }

    public function addUser(ServerEntry $server, ProjectEntry $project) {
        $system = $this->serverService->getSshSystemForServer($server);

        $username = $project->getUsername();

        if ($this->testHasUser($system, $username)) {
            return true;
        }

        $uid = $this->offset + $project->getId();

        $command = 'addgroup -q';
        $command .= ' --gid ' . $uid;
        $command .= ' ' . $username;

        $code = null;
        $output = $system->execute($command, $code);

        if ($code != '0') {
            $output = implode("\n", $output);
            if (strpos($output, 'exists') === false) {
                throw new Exception('Could not add group ' . $username . ': ' . implode("\n", $output));
            }
        }

        $home = $this->getHomeDirectory($username);

        $command = 'useradd';
        $command .= ' --uid ' . $uid;
        $command .= ' --gid ' . $uid;
        $command .= ' --home-dir ' . $home;
        $command .= ' --shell /bin/bash';
        $command .= ' ' . $username;

        $code = null;
        $output = $system->execute($command, $code);

        if ($code != '0') {
            $this->deleteGroup($system, $username);

            throw new Exception('Could not add user ' . $username . ': ' . implode("\n", $output));
        }

        $system->execute('mkdir -p ' . $home);
        $system->execute('chown ' . $username . ':' . $username . ' ' . $home);
    }

    public function deleteUser(ServerEntry $server, ProjectEntry $project) {
        $system = $this->serverService->getSshSystemForServer($server);

        $username = $project->getUsername();

        if (!$this->testHasUser($system, $username)) {
            return true;
        }

        $command = 'deluser';
        $command .= ' --remove-home';
        $command .= ' ' . $username;

        $code = null;
        $output = $system->execute($command, $code);

        if ($code != '0') {
            throw new Exception('Could not delete user ' . $username . ': ' . implode("\n", $output));
        }

        $this->deleteGroup($system, $username);
    }

    public function authorizeSshKeys(ServerEntry $server, ProjectEntry $project) {
        $username = $project->getUsername();

        $system = $this->serverService->getSshSystemForServer($server);

        $home = $this->getHomeDirectory($username);
        $file = $system->getFileSystem()->getFile($home);
        $file = $file->getChild('.ssh/authorized_keys');

        $fileExists = $file->exists();
        if ($fileExists) {
            $authorizedKeys = $file->read();
            $lines = explode("\n", $authorizedKeys);
        } else {
            $authorizedKeys = '';
            $lines = array();
        }

        $authorizedKeys = $this->updateAuthorizedKeys($lines, $project->getSshKeysAsString());

        if ($fileExists) {
            $file->write($authorizedKeys);
        } else {
            $file->write($authorizedKeys);

            $system->execute('chmod 600 ' . $file->getLocalPath());
            $system->execute('chown ' . $username . ':' . $username . ' ' . $file->getLocalPath());
            $system->execute('chown ' . $username . ':' . $username . ' ' . $file->getParent()->getLocalPath());
        }
    }

    public function getHomeDirectory($username) {
        return '/home/' . substr($username, 0, 1) . '/' . $username;
    }

    private function updateAuthorizedKeys(array $lines, $sshKeys) {
        $startGenerated = '# Start automatic keys, don\'t edit this block manually';
        $stopGenerated = '# End automatic keys';
        $inGeneratedPart = false;
        $isAdded = false;

        $authorizedKeys = '';

        foreach ($lines as $line) {
            if (!$inGeneratedPart) {
                $authorizedKeys .= $line . "\n";

                if (strpos($line, $startGenerated) === 0) {
                    $inGeneratedPart = true;
                }

                continue;
            }

            if (strpos($line, $stopGenerated) !== 0) {
                continue;
            }

            $authorizedKeys .= $sshKeys;
            $authorizedKeys .= $line . "\n";

            $isAdded = true;
        }

        if (!$isAdded) {
            $authorizedKeys .= $startGenerated . "\n";
            $authorizedKeys .= $sshKeys;
            $authorizedKeys .= $stopGenerated . "\n";
        }

        return $authorizedKeys;
    }

    private function deleteGroup(SshSystem $system, $group) {
        $code = null;
        $output = $system->execute('deluser --group --only-if-empty ' . $group, $code);

        if ($code == 0) {
            return true;
        }

        return false;
    }

    private function testHasUser(SshSystem $system, $user) {
        $code = null;
        $output = $system->execute('id ' . $user, $code);

        if ($code == 0) {
            return true;
        }

        return false;
    }

}
