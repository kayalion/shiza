<?php

namespace shiza\helper;

use ride\library\system\file\File;
use ride\library\system\SshSystem;

class BackupHelper {

    public function rotateBackupHourly(SshSystem $system, File $directory, $maxIndex, $prefix, $suffix = null) {
        $previousBackup = $this->rotateBackup($system, $directory, $maxIndex, $prefix, $suffix, 1);

        $backupName = $prefix . '0' . $suffix;
        $backup = $directory->getChild($backupName);

        if (!$backup->exists()) {
            return $backup;
        }

        $code = null;
        $output = $system->execute('cp -al ' . $backup->getLocalPath() . ' ' . $previousBackup->getLocalPath(), $code);

        if ($code != 0) {
            throw new Exception('Could not copy ' . $backupName . ' to ' . $previousBackup->getName() . ': ' . implode("\n", $output));
        }

        return $backup;
    }

    public function rotateBackupDaily(SshSystem $system, File $directory, File $backup, $maxIndex, $prefix, $suffix = null) {
        $previousBackup = $this->rotateBackup($system, $directory, $maxIndex, $prefix, $suffix, 0);

        if (!$backup->exists()) {
            return $backup;
        }

        $code = null;
        $output = $system->execute('cp -al ' . $backup->getLocalPath() . ' ' . $previousBackup->getLocalPath(), $code);

        if ($code != 0) {
            throw new Exception('Could not copy ' . $backup->getName() . ' to ' . $previousBackup->getName() . ': ' . implode("\n", $output));
        }

        return $backup;

    }

    public function rotateBackup(SshSystem $system, File $directory, $maxIndex, $prefix, $suffix = null, $stopAt = 0) {
        $code = null;

        // remove last backup
        $index = $maxIndex - 1;

        $backupName = $prefix . $index . $suffix;

        $backup = $directory->getChild($prefix . $index . $suffix);
        if ($backup->exists()) {
            $output = $system->execute('rm -rf ' . $backup->getLocalPath(), $code);

            if ($code != 0) {
                throw new Exception('Could not remove last backup: ' . implode("\n", $output));
            }
        }

        // rotate middle backups
        if ($index <= 1) {
            return;
        }

        do {
            $index--;

            $previousBackup = $backup;
            $previousBackupName = $backupName;

            $backupName = $prefix . $index . $suffix;

            $backup = $directory->getChild($backupName);
            if ($backup->exists()) {
                $output = $system->execute('mv ' . $backup->getLocalPath() . ' ' . $previousBackup->getLocalPath(), $code);

                if ($code != 0) {
                    throw new Exception('Could not move ' . $backupName . ' to ' . $previousBackupName . ': ' . implode("\n", $output));
                }
            }
        } while ($index > $stopAt || $index != 0);

        return $backup;
    }

}
