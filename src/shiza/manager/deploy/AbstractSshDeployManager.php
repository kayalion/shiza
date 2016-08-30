<?php

namespace shiza\manager\deploy;

use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;
use ride\library\system\exception\AuthenticationSshSystemException;
use ride\library\system\exception\SshSystemException;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\ValidationError;

use shiza\orm\entry\RepositoryDeployerEntry;

use shiza\service\ServerService;

use \Exception;

/**
 * Abstract implementation for a SSH deployment type
 */
abstract class AbstractSshDeployManager extends AbstractDeployManager {

    public function __construct(ServerService $serverService) {
        $this->serverService = $serverService;
    }

    /**
     * Creates the rows needed for extra commands
     * @param ride\library\form\FormBuilder $formBuilder
     * @param ride\library\i18n\translator\Translator $translator
     * @return null
     */
    protected function createScriptRows(FormBuilder $formBuilder, Translator $translator) {
        $formBuilder->addRow('script', 'text', array(
            'label' => $translator->translate('label.script'),
            'description' => $translator->translate('label.deployer.script.description'),
            'filters' => array(
                'trim' => array(
                    'trim.lines' => true,
                    'trim.empty' => true,
                ),
            ),
            'attributes' => array(
                'class' => 'console',
                'rows' => 10,
            ),
        ));
    }

    /**
     * Processes the server to validate the connection
     * @param shiza\orm\entry\RepositoryDeployerEntry $repositoryDeployerEntry
     * @return null
     * @throws ride\library\validation\exception\ValidationException
     */
    protected function connect(RepositoryDeployerEntry $repositoryDeployerEntry) {
        $sshSystem = $this->serverService->getSshSystemForDeployer($repositoryDeployerEntry);

        try {
            $sshSystem->connect();
        } catch (AuthenticationSshSystemException $exception) {
            if ($repositoryDeployerEntry->getUseKey()) {
                $error = new ValidationError('error.remote.host.authenticate.key', 'Could not authenticate with SSH key');

                $exception = new ValidationException();
                $exception->addError('remoteUsername', $error);

                throw $exception;
            } else {
                $error = new ValidationError('error.remote.host.authenticate.password', 'Could not authenticate with password');

                $exception = new ValidationException();
                $exception->addError('remoteUsername', $error);
                $exception->addError('remotePassword', $error);

                throw $exception;
            }
        } catch (SshSystemException $exception) {
            $error = new ValidationError('error.remote.host.connect', 'Could not connect to %host%', array('host' => $repositoryDeployerEntry->getRemoteHost() . ':' . $repositoryDeployerEntry->getRemotePort()));

            $exception = new ValidationException();
            $exception->addError('remoteHost', $error);
            $exception->addError('remotePort', $error);

            throw $exception;
        }

        return $sshSystem;
    }

}
