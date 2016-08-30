<?php

namespace shiza\manager\deploy;

use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;
use ride\library\system\file\File;

use shiza\orm\entry\RepositoryDeployerEntry;

use \Exception;

interface DeployManager {

    /**
     * Creates the rows needed for this deployer
     * @param ride\library\form\FormBuilder $formBuilder
     * @param ride\library\i18n\translator\Translator $translator
     * @return null
     */
    public function createForm(FormBuilder $formBuilder, Translator $translator);

    /**
     * Processes the deployer to validate the connection
     * @param shiza\orm\entry\RepositoryDeployerEntry $repositoryDeployerEntry
     * @return null
     * @throws ride\library\validation\exception\ValidationException
     */
    public function processForm(RepositoryDeployerEntry $repositoryDeployerEntry);

    /**
     * Performs the deployment
     * @param shiza\orm\entry\RepositoryDeployerEntry $repositoryDeployerEntry
     * @param ride\library\system\file\File $path Local path of the fileset
     * @param array $files Array with the path of the file as key and a git
     * commit file as value
     * @return array with
     */
    public function deploy(RepositoryDeployerEntry $repositoryDeployerEntry, File $path, array $files, Exception &$exception = null);

}
