<?php

namespace shiza\manager\deploy;

use ride\library\form\FormBuilder;
use ride\library\i18n\translator\Translator;

/**
 * Abstract implementation for a deployment type
 */
abstract class AbstractDeployManager implements DeployManager {

    /**
     * Creates the rows needed for the repository
     * @param ride\library\form\FormBuilder $formBuilder
     * @param ride\library\i18n\translation\Translator $translator
     * @param boolean addRepositoryPath
     * @return null
     */
    protected function createRepositoryRows(FormBuilder $formBuilder, Translator $translator, $addRepositoryPath) {
        $formBuilder->addRow('revision', 'string', array(
            'label' => $translator->translate('label.revision'),
            'description' => $translator->translate('label.deployer.revision.description'),
            'filters' => array(
                'trim' => array(),
            ),
        ));

        if ($addRepositoryPath) {
            $formBuilder->addRow('repositoryPath', 'string', array(
                'label' => $translator->translate('label.path'),
                'description' => $translator->translate('label.deployer.path.repository.description'),
                'filters' => array(
                    'trim' => array(),
                ),
                'validators' => array(
                    'required' => array(),
                ),
            ));
        }
    }

    protected function createServerRows(FormBuilder $formBuilder, Translator $translator, $defaultPort, $addRemotePath, $addUseKey, $addUsePassive, $addUseSsl) {
        $formBuilder->addRow('remoteHost', 'string', array(
            'label' => $translator->translate('label.host'),
            'description' => $translator->translate('label.deployer.host.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $formBuilder->addRow('remotePort', 'string', array(
            'label' => $translator->translate('label.port'),
            'description' => $translator->translate('label.deployer.port.description'),
            'default' => $defaultPort,
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'minmax' => array('required' => false, 'minimum' => 1, 'maximum' => 65535),
            ),
        ));

        if ($addRemotePath) {
            $formBuilder->addRow('remotePath', 'string', array(
                'label' => $translator->translate('label.path'),
                'description' => $translator->translate('label.deployer.path.remote.description'),
                'filters' => array(
                    'trim' => array(),
                ),
                'validators' => array(
                    'required' => array(),
                ),
            ));
        }

        if ($addUseKey) {
            $formBuilder->addRow('useKey', 'option', array(
                'label' => $translator->translate('label.deployer.use.key'),
                'description' => $translator->translate('label.deployer.use.key.description'),
                'attributes' => array(
                    'data-toggle-dependant' => 'option-usekey',
                ),
            ));
        }

        $formBuilder->addRow('remoteUsername', 'string', array(
            'label' => $translator->translate('label.username'),
            'description' => $translator->translate('label.deployer.username.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $formBuilder->addRow('newPassword', 'password', array(
            'label' => $translator->translate('label.password'),
            'description' => $translator->translate('label.deployer.password.description'),
            'attributes' => array(
                'autocomplete' => 'off',
                'class' => 'option-usekey option-usekey-null',
            ),
        ));

        if ($addUsePassive) {
            $formBuilder->addRow('usePassive', 'option', array(
                'label' => $translator->translate('label.use'),
                'description' => $translator->translate('label.deployer.use.passive.description'),
            ));
        }

        if ($addUseSsl) {
            $formBuilder->addRow('useSsl', 'option', array(
                'label' => $translator->translate('label.use'),
                'description' => $translator->translate('label.deployer.use.ssl.description'),
            ));
        }
    }

}
