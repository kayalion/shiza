<?php

namespace shiza\manager\cron;

use shiza\orm\entry\ProjectEntry;

use shiza\service\ServerService;

use \Exception;

class SshCronManager implements CronManager {

    public function __construct(ServerService $serverService) {
        $this->serverService = $serverService;
    }

    public function updateCrontab(ProjectEntry $project) {
        $server = $project->getCronServer();
        if (!$server) {
            return;
        }

        $system = $this->serverService->getSshSystemForServer($server);

        $output = null;
        $username = $project->getUsername();
        $cron = $project->getCronTab();
        if ($cron) {
            $file = $system->getFileSystem()->getFile('crontab-' . $username . '.txt');
            $file = $file->getCopyFile();
            $file->write($cron . "\n");

            $code = null;
            $command = 'crontab -u ' . $username . ' ' . $file->getLocalPath();

            try {
                $output = $system->execute($command, $code);

                $file->delete();
            } catch (Exception $exception) {
                $file->delete();

                throw $exception;
            }

            if ($code != 0) {
                $output = array_shift($output);

                throw new Exception($output);
            }
        } else {
            $code = null;
            $command = 'crontab -u ' . $username . ' -l';

            $output = $system->execute($command, $code);

            if ($code == '0') {
                $command = 'crontab -u ' . $username . ' -r; echo $?';

                $output = $system->execute($command, $code);
            }
        }
    }

}
