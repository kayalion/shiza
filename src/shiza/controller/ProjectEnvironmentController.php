<?php

namespace shiza\controller;

use ride\library\i18n\translator\Translator;
use ride\library\orm\OrmManager;
use ride\library\validation\exception\ValidationException;

use shiza\service\ServerService;

use \Exception;

class ProjectEnvironmentController extends AbstractController {

    public function formAction(OrmManager $orm, ServerService $serverService, $project, $environment = null) {
        $serverModel = $orm->getServerModel();
        $projectModel = $orm->getProjectModel();
        $environmentModel = $orm->getProjectEnvironmentModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $environment = $this->getEntry($environmentModel, $environment, 'domain');
        if (!$environment) {
            $environment = $environmentModel->createEntry();
            $environment->setProject($project);
        } else if ($environment->getProject()->getId() != $project->getId()) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($environment);
        $form->addRow('server', 'object', array(
            'label' => $translator->translate('label.server'),
            'description' => $translator->translate('label.environment.server.description'),
            'disabled' => $environment->getId() ? true : false,
            'options' => $serverModel->find(array('filter' => array('isWeb' => 1))),
            'value' => 'id',
            'property' => 'name',
            'widget' => 'select',
        ));
        $form->addRow('domain', 'string', array(
            'label' => $translator->translate('label.domain'),
            'description' => $translator->translate('label.environment.domain.description'),
            'readonly' => $environment->getId() ? true : false,
	        'filters' => array(
                'trim' => array(),
            ),
	        'validators' => array(
                'required' => array(),
	            'regex' => array(
                    'regex' => '/^[\p{Latin}\d]+([\-\.]{1}[\p{Latin}\d]+)*\.[a-z]{2,5}$/u',
                    'required' => false,
                    'error.regex' => 'error.validation.domain',
                ),
            ),
        ));
        $form->addRow('alias', 'text', array(
            'label' => $translator->translate('label.alias'),
            'description' => $translator->translate('label.environment.alias.description'),
            'attributes' => array(
                'rows' => 5,
            ),
	        'filters' => array(
                'trim' => array(
                    'trim.lines' => true,
                    'trim.empty' => true
                ),
            ),
	        'validators' => array(
	            'regex' => array(
                    'regex' => '/^(([\p{Latin}\d]+([\-\.]{1}[\p{Latin}\d]+)*\.[a-z]{2,5}((\r)?\n)?)*)?$/u',
                    'required' => false,
                    'error.regex' => 'error.validation.domain',
                ),
            ),
        ));
        $form->addRow('isActive', 'option', array(
            'label' => $translator->translate('label.active'),
            'description' => $translator->translate('label.active.environment.description'),
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $environment = $form->getData();

                $environmentModel->save($environment);

                $this->addSuccess('success.data.saved', array('data' => $environment->getDomain()));

                if (!$referer) {
                    $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/projects/environment.form', array(
            'form' => $form->getView(),
            'environment' => $environment,
            'project' => $project,
            'referer' => $referer,
        ));
    }

    public function deleteAction(OrmManager $orm, $project, $environment) {
        $projectModel = $orm->getProjectModel();
        $environmentModel = $orm->getProjectEnvironmentModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $environment = $this->getEntry($environmentModel, $environment, 'domain');
        if (!$environment || $environment->getProject()->getId() != $project->getId()) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->getReferer();

        if ($this->request->isPost()) {
            $environment->setIsDeleted(true);

            $environmentModel->save($environment);

            $this->addSuccess('success.data.deleted', array('data' => (string) $environment));

            if (!$referer) {
                $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
            }

            $this->response->setRedirect($referer);

            return;
        }

        $this->setTemplateView('admin/projects/delete', array(
            'title' => 'title.projects',
            'project' => $project,
            'data' => (string) $environment,
            'referer' => $referer,
        ));
    }

    public function logAction(OrmManager $orm, ServerService $serverService, $project, $environment) {
        $projectModel = $orm->getProjectModel();
        $environmentModel = $orm->getProjectEnvironmentModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $environment = $this->getEntry($environmentModel, $environment, 'domain');
        if (!$environment || $environment->getProject()->getId() != $project->getId()) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->getReferer();

        $server = $environment->getServer();
        $manager = $serverService->getWebManager($server->getWebManager());
        $log = $manager->getLog($environment);

        $this->setTemplateView('admin/projects/environment.log', array(
            'log' => $log,
            'environment' => $environment,
            'project' => $project,
            'referer' => $referer,
        ));
    }

    public function phpAction(OrmManager $orm, ServerService $serverService, $project, $environment) {
        $serverModel = $orm->getServerModel();
        $projectModel = $orm->getProjectModel();
        $environmentModel = $orm->getProjectEnvironmentModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $environment = $this->getEntry($environmentModel, $environment, 'domain');
        if (!$environment || $environment->getProject()->getId() != $project->getId()) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $server = $environment->getServer();

        $form = $this->createFormBuilder($environment);
        $form->addRow('phpVersion', 'option', array(
            'label' => $translator->translate('label.php.version'),
            'description' => $translator->translate('label.php.version.description'),
            'options' => $server->getPhpVersions(),
            'widget' => 'select',
        ));
        $form->addRow('phpIni', 'text', array(
            'label' => $translator->translate('label.php.ini'),
            'description' => $translator->translate('label.php.ini.description'),
            'attributes' => array(
                'rows' => 7,
            ),
	        'filters' => array(
                'trim' => array(
                    'trim.lines' => true,
                ),
            ),
	        'validators' => array(
                'ini' => array(),
            ),
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $environment = $form->getData();

                $environmentModel->save($environment);

                $this->addSuccess('success.data.saved', array('data' => $environment->getDomain()));

                if (!$referer) {
                    $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/projects/environment.php', array(
            'form' => $form->getView(),
            'environment' => $environment,
            'project' => $project,
            'referer' => $referer,
        ));
    }

    public function sslAction(OrmManager $orm, ServerService $serverService, $project, $environment) {
        $projectModel = $orm->getProjectModel();
        $environmentModel = $orm->getProjectEnvironmentModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $environment = $this->getEntry($environmentModel, $environment, 'domain');
        if (!$environment || $environment->getProject()->getId() != $project->getId()) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->getReferer();
        if (!$referer) {
            $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
        }

        $translator = $this->getTranslator();

        $managers = array('' => $translator->translate('label.ssl.disabled'));
        $managers += $this->getSslManagerOptions($translator);

        $domain = $environment->getDomain();
        $domains = array($domain => $domain) + $environment->getAliases();

        $form = $this->createFormBuilder($environment);
        $form->addRow('sslManager', 'option', array(
            'label' => $translator->translate('label.ssl'),
            'description' => $translator->translate('label.ssl.manager.description'),
            'options' => $managers,
            'attributes' => array(
                'data-toggle-dependant' => 'option-manager',
            ),
            'widget' => 'select',
        ));
        $form->addRow('sslCommonName', 'option', array(
            'label' => $translator->translate('label.ssl.common-name'),
            'description' => $translator->translate('label.ssl.common-name.description'),
            'options' => $domains,
            'attributes' => array(
                'class' => 'option-manager option-manager-self option-manager-manual option-manager-le',
            ),
            'widget' => 'select',
        ));
        $form->addRow('sslEmail', 'string', array(
            'label' => $translator->translate('label.ssl.email'),
            'description' => $translator->translate('label.ssl.email.description'),
            'attributes' => array(
                'class' => 'option-manager option-manager-self option-manager-le',
            ),
        ));
        $form->addRow('sslOrganisation', 'string', array(
            'label' => $translator->translate('label.ssl.organisation'),
            'description' => $translator->translate('label.ssl.organisation.description'),
            'attributes' => array(
                'class' => 'option-manager option-manager-self',
            ),
        ));
        $form->addRow('sslCountry', 'string', array(
            'label' => $translator->translate('label.ssl.country'),
            'description' => $translator->translate('label.ssl.country.description'),
            'attributes' => array(
                'class' => 'option-manager option-manager-self',
            ),
        ));
        $form->addRow('sslState', 'string', array(
            'label' => $translator->translate('label.ssl.state'),
            'description' => $translator->translate('label.ssl.state.description'),
            'attributes' => array(
                'class' => 'option-manager option-manager-self',
            ),
        ));
        $form->addRow('sslLocality', 'string', array(
            'label' => $translator->translate('label.ssl.locality'),
            'description' => $translator->translate('label.ssl.locality.description'),
            'attributes' => array(
                'class' => 'option-manager option-manager-self',
            ),
        ));
        $form->addRow('sslCertificate', 'text', array(
            'label' => $translator->translate('label.ssl.certificate'),
            'description' => $translator->translate('label.ssl.certificate.description'),
            'attributes' => array(
                'class' => 'option-manager option-manager-manual',
                'rows' => 7,
            ),
        ));
        $form->addRow('sslCertificateKey', 'text', array(
            'label' => $translator->translate('label.ssl.certificate-key'),
            'description' => $translator->translate('label.ssl.certificate-key.description'),
            'attributes' => array(
                'class' => 'option-manager option-manager-manual',
                'rows' => 7,
            ),
        ));
        $form = $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $environment = $form->getData();
                if ($environment->getSslManager()) {
                    $environment->setIsSslActive(true);
                }

                $environmentModel->save($environment);

                $this->addSuccess('success.data.saved', array('data' => (string) $environment));

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/projects/environment.ssl', array(
            'title' => 'title.projects',
            'form' => $form->getView(),
            'project' => $project,
            'referer' => $referer,
        ));
    }

    public function getSslManagerOptions(Translator $translator) {
        return $this->getManagerOptions($translator, 'shiza\\manager\\ssl\\SslManager', 'manager.ssl.');
    }

    public function backupAction(OrmManager $orm, ServerService $serverService, $project, $environment) {
        $projectModel = $orm->getProjectModel();
        $environmentModel = $orm->getProjectEnvironmentModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $environment = $this->getEntry($environmentModel, $environment, 'domain');
        if (!$environment || $environment->getProject()->getId() != $project->getId()) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->getReferer();
        if (!$referer) {
            $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
        }

        $translator = $this->getTranslator();

        $createForm = $this->createFormBuilder();
        $createForm->setId('create');
        $createForm->setAction('create');
        $createForm->addRow('environment', 'label', array(
            'label' => $translator->translate('label.environment'),
            'default' => $environment->getDomain(),
        ));
        $createForm->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'description' => $translator->translate('label.backup.name.description'),
        ));
        $createForm = $createForm->build();

        if ($createForm->isSubmitted()) {
            $data = $createForm->getData();

            $environmentModel->createBackup($environment, $data['name']);

            $this->addSuccess('success.backup.created', array('data' => (string) $environment));

            $this->response->setRedirect($referer);

            return;
        }

        $server = $environment->getServer();

        $manager = $serverService->getWebManager($server->getWebManager());

        $backups = $manager->getBackups($environment);
        foreach ($backups as $backupId => $backup) {
            $url = $this->getUrl('environments.backups.download', array(
                'project' => $project->getCode(),
                'environment' => $environment->getId(),
                'backup' => $backupId,
            ));
            $backups[$backupId] = '<a href="' . $url . '">' . $backup . '</a>';
        }

        $environments = $environmentModel->find(array('filter' => array(
            'project' => $project->getId(),
            'server' => $environment->getServer()->getId(),
        )));

        $restoreForm = $this->createFormBuilder();
        $restoreForm->setId('restore');
        $restoreForm->setAction('restore');
        $restoreForm->addRow('backup', 'option', array(
            'label' => $translator->translate('label.backup'),
            'description' => $translator->translate('label.backup.select.description'),
            'options' => $backups,
            'validators' => array(
                'required' => array(),
            ),
        ));
        $restoreForm->addRow('destination', 'object', array(
            'label' => $translator->translate('label.destination'),
            'description' => $translator->translate('label.backup.destination.description'),
            'options' => $environments,
            'default' => $environment,
            'value' => 'id',
            'property' => 'domain',
            'validators' => array(
                'required' => array(),
            ),
            'widget' => 'select',
        ));
        $restoreForm = $restoreForm->build();

        if ($restoreForm->isSubmitted()) {
            try {
                $restoreForm->validate();

                $data = $restoreForm->getData();

                $environmentModel->restoreBackup($environment, $data['backup'], $data['destination']);

                $this->addSuccess('success.backup.restored', array('data' => (string) $environment));

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/projects/backup', array(
            'title' => 'title.projects',
            'restoreForm' => $restoreForm->getView(),
            'createForm' => $createForm->getView(),
            'project' => $project,
            'backups' => $backups,
            'referer' => $referer,
        ));
    }

}
