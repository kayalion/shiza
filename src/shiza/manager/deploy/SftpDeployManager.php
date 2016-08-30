<?php

namespace shiza\manager\deploy;

use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;
use ride\library\system\exception\SshException;
use ride\library\system\file\File;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\ValidationError;

use shiza\orm\entry\RepositoryDeployerEntry;

use \Exception;

/**
 * Interface for a deployment type
 */
class SftpDeployManager extends AbstractSshDeployManager {

    /**
     * Creates the rows needed for this protocol
     * @param zibo\library\form\FormBuilder $formBuilder
     * @param zibo\library\i18n\translation\Translator $translator
     * @return null
     */
    public function createForm(FormBuilder $formBuilder, Translator $translator) {
        $this->createRepositoryRows($formBuilder, $translator, true);
        $this->createServerRows($formBuilder, $translator, 22, true, true, false, false);

        $formBuilder->addRow('exclude', 'text', array(
            'label' => $translator->translate('label.deployer.exclude'),
            'description' => $translator->translate('label.deployer.exclude.description'),
            'filters' => array(
                'trim' => array(
                    'trim.lines' => true,
                    'trim.empty' => true,
                ),
            ),
        ));

        $this->createScriptRows($formBuilder, $translator);
    }

    /**
     * Processes the server to validate the connection
     * @param shiza\orm\entry\RepositoryDeployerEntry $repositoryDeployerEntry
     * @return null
     * @throws ride\library\validation\exception\ValidationException
     */
    public function processForm(RepositoryDeployerEntry $repositoryDeployer) {
        $sshSystem = $this->connect($repositoryDeployer);

        $repositoryDeployer->setFingerprint($sshSystem->getFingerprint());

        $file = $sshSystem->getFileSystem()->getFile($repositoryDeployer->getRemotePath());
        if (!$file->exists()) {
            $error = new ValidationError('error.remote.path.exists', 'Remote path does not exist');

            $exception = new ValidationException();
            $exception->addError('remotePath', $error);

            throw $exception;
        }

        $sshSystem->disconnect();
    }

    /**
     * Performs the deployment
     * @param shiza\orm\entry\RepositoryDeployerEntry $repositoryDeployer
     * @param ride\library\system\file\File $path Local path of the fileset
     * @param array $files Array with the path of the file as key and a git
     * commit file as value
     * @param \Exception $exception
     * @return array
     */
    public function deploy(RepositoryDeployerEntry $repositoryDeployer, File $path, array $files, Exception &$exception = null) {
        $log = array();

        if (!$files) {
            return $log;
        }

        $sshSystem = $this->connect($repositoryDeployer);
        $remoteFileSystem = $sshSystem->getFileSystem();

        $remotePath = $remoteFileSystem->getFile($repositoryDeployer->getRemotePath());

        foreach ($files as $file => $action) {
            $remoteFile = $remotePath->getChild($file);

            if ($action == 'D') {
                try {
                    $remoteFile->delete();

                    $log['-' . $file] = true;
                } catch (Exception $e) {
                    $exception = $e;

                    $log['-' . $file] = 'Could not delete ' . $remoteFile->getLocalPath();

                    break;
                }

                continue;
            }

            try {
                $localFile = $path->getChild($file);

                $localFile->copy($remoteFile);

                $remoteFile->setPermissions($localFile->getPermissions());

                $log['+' . $file] = true;
            } catch (Exception $e) {
                $exception = $e;

                $log['+' . $file] = 'Could not upload ' . $file;

                break;
            }
        }

        if (!$exception) {
            $commands = $repositoryDeployer->parseCommands();
            foreach ($commands as $command) {
                try {
                    // if (substr($command, 0, 3) == 'cd ') {
                        // chdir(substr($command, 3));

                        // continue;
                    // }

                    $code = null;
                    $log[$command] = $sshSystem->execute($command, $code);

                    if ($code != 0) {
                        throw new Exception('Command returned ' . $code . ': ' . implode("\n", $log[$command]));
                    }
                } catch (Exception $e) {
                    $exception = $e;

                    $log[$command] = $e->getMessage();

                    break;
                }
            }
        }

        $sshSystem->disconnect();

        return $log;
    }

}
