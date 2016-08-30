<?php

namespace shiza\controller;

use ride\library\orm\OrmManager;
use ride\library\validation\exception\ValidationException;

use shiza\service\ServerService;

use \Exception;

class ProjectDatabaseController extends AbstractController {

    public function formAction(OrmManager $orm, ServerService $serverService, $project, $database = null) {
        $serverModel = $orm->getServerModel();
        $projectModel = $orm->getProjectModel();
        $databaseModel = $orm->getProjectDatabaseModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $database = $this->getEntry($databaseModel, $database, 'name');
        if (!$database) {
            $database = $databaseModel->createEntry();
            $database->setProject($project);
        } else if ($database->getProject()->getId() != $project->getId()) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($database);
        $form->addRow('server', 'object', array(
            'label' => $translator->translate('label.server'),
            'description' => $translator->translate('label.database.server.description'),
            'options' => $serverModel->find(array('filter' => array('isDatabase' => 1))),
            'value' => 'id',
            'property' => 'name',
            'widget' => 'select',
        ));
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.database'),
            'description' => $translator->translate('label.database.name.description'),
	        'filters' => array(
                'trim' => array(
                    'trim.lines' => true,
                    'trim.empty' => true
                ),
            ),
	        'validators' => array(
	            'regex' => array(
                    'regex' => '/^[a-z0-9_]+$/',
                    'required' => false,
                    'error.regex' => 'error.validation.database',
                ),
            ),
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $database = $form->getData();

                $databaseModel->save($database);

                $this->addSuccess('success.data.saved', array('data' => $database->getName()));

                if (!$referer) {
                    $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/projects/database.form', array(
            'form' => $form->getView(),
            'database' => $database,
            'project' => $project,
            'referer' => $referer,
        ));
    }

    public function deleteAction(OrmManager $orm, $project, $database) {
        $projectModel = $orm->getProjectModel();
        $databaseModel = $orm->getProjectDatabaseModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $database = $this->getEntry($databaseModel, $database, 'name');
        if (!$database || $database->getProject()->getId() != $project->getId()) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->getReferer();

        if ($this->request->isPost()) {
            $database->setIsDeleted(true);

            $databaseModel->save($database);

            $this->addSuccess('success.data.deleted', array('data' => (string) $database));

            if (!$referer) {
                $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
            }

            $this->response->setRedirect($referer);

            return;
        }

        $this->setTemplateView('admin/projects/delete', array(
            'title' => 'title.projects',
            'project' => $project,
            'data' => (string) $database,
            'referer' => $referer,
        ));
    }

    public function backupAction(OrmManager $orm, ServerService $serverService, $project, $database) {
        $projectModel = $orm->getProjectModel();
        $databaseModel = $orm->getProjectDatabaseModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $database = $this->getEntry($databaseModel, $database, 'name');
        if (!$database || $database->getProject()->getId() != $project->getId()) {
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
        $createForm->addRow('database', 'label', array(
            'label' => $translator->translate('label.database'),
            'default' => $database->getDatabaseName(),
        ));
        $createForm->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'description' => $translator->translate('label.backup.name.description'),
        ));
        $createForm = $createForm->build();

        if ($createForm->isSubmitted()) {
            $data = $createForm->getData();

            $databaseModel->createBackup($database, $data['name']);

            $this->addSuccess('success.backup.created', array('data' => (string) $database));

            $this->response->setRedirect($referer);

            return;
        }

        $server = $database->getServer();

        $manager = $serverService->getDatabaseManager($server->getDatabaseManager());

        $backups = $manager->getBackups($database);
        foreach ($backups as $backupId => $backup) {
            $url = $this->getUrl('databases.backups.download', array(
                'project' => $project->getCode(),
                'database' => $database->getId(),
                'backup' => $backupId,
            ));
            $backups[$backupId] = '<a href="' . $url . '">' . $backup . '</a>';
        }

        $databases = $databaseModel->find(array('filter' => array(
            'project' => $project->getId(),
            'server' => $database->getServer()->getId(),
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
            'options' => $databases,
            'default' => $database,
            'value' => 'id',
            'property' => 'databaseName',
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

                $databaseModel->restoreBackup($database, $data['backup'], $data['destination']);

                $this->addSuccess('success.backup.restored', array('data' => (string) $database));

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

    public function backupDownloadAction(OrmManager $orm, ServerService $serverService, $project, $database, $backup) {
        $projectModel = $orm->getProjectModel();
        $databaseModel = $orm->getProjectDatabaseModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $database = $this->getEntry($databaseModel, $database, 'name');
        if (!$database || $database->getProject()->getId() != $project->getId()) {
            $this->response->setNotFound();

            return;
        }

        $server = $database->getServer();
        $manager = $serverService->getDatabaseManager($server->getDatabaseManager());

        $file = $serverService->getLocalSystem()->getFileSystem()->getTemporaryFile();

        $manager->downloadBackup($database, $backup, $file);

        $this->setDownloadView($file, $backup, true);
    }

}
