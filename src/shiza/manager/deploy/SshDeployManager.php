<?php

namespace shiza\manager\deploy;

use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;
use ride\library\system\exception\SshException;
use ride\library\system\file\File;

use shiza\orm\entry\RepositoryDeployerEntry;

use \Exception;

/**
 * Interface for a deployment type
 */
class SshDeployManager extends AbstractSshDeployManager {

    /**
     * Creates the rows needed for this protocol
     * @param zibo\library\form\FormBuilder $formBuilder
     * @param zibo\library\i18n\translation\Translator $translator
     * @return null
     */
    public function createForm(FormBuilder $formBuilder, Translator $translator) {
        $this->createRepositoryRows($formBuilder, $translator, false);
        $this->createServerRows($formBuilder, $translator, 22, false, true, false, false);
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

        $sshSystem->disconnect();
    }

    /**
     * Performs the deployment
     * @param shiza\orm\entry\RepositoryDeployerEntry $repositoryDeployer
     * @param ride\library\system\file\File $path Local path of the fileset
     * @param array $files Array with the path of the file as key and a git
     * commit file as value
     * @return array
     */
    public function deploy(RepositoryDeployerEntry $repositoryDeployer, File $path, array $files, Exception &$exception = null) {
        $log = array();

        $commands = $repositoryDeployer->parseCommands();
        $command = implode(" && ", $commands);

        $sshSystem = $this->connect($repositoryDeployer);

        try {
            $log[$command] = $sshSystem->execute($command);
        } catch (SshException $e) {
            $exception = $e;

            $log[$command] = $e->getMessage();
        }

        $sshSystem->disconnect();

        return $log;
    }

}
