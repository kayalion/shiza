<?php

namespace shiza\controller;

use ride\library\orm\OrmManager;
use ride\library\validation\exception\ValidationException;

use shiza\service\ServerService;

use \Exception;

class ProjectController extends AbstractController {

    public function indexAction(OrmManager $orm) {
        $projectModel = $orm->getProjectModel();

        $query = $projectModel->createQuery();
        $query->addCondition('{isDeleted} IS NULL');
        $query->addOrderBy('{name} ASC');

        $projects = $query->query();

        $this->setTemplateView('admin/projects/index', array(
            'projects' => $projects,
        ));
    }

    public function detailAction(OrmManager $orm, ServerService $serverService, $project) {
        $projectModel = $orm->getProjectModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $messageModel = $orm->getMessageModel();
        $messageQuery = $messageModel->createQuery();
        $messageQuery->addCondition('{project} = %1%', $project->getId());
        $messageQuery->addOrderBy('{dateAdded} DESC');
        $messageQuery->setLimit(15);

        $messages = $messageQuery->query();

        $password = $project->getPassword();
        if ($password) {
            $password = $serverService->decrypt($password);
        }

        $varnishSecret = $project->getVarnishSecret();
        if ($varnishSecret) {
            $varnishSecret = $serverService->decrypt($varnishSecret);
        }

        $this->setTemplateView('admin/projects/detail', array(
            'project' => $project,
            'password' => $password,
            'varnishSecret' => $varnishSecret,
            'messages' => $messages,
        ));
    }

    public function formAction(OrmManager $orm, $project = null) {
        $projectModel = $orm->getProjectModel();
        $sshKeyModel = $orm->getSshKeyModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $project = $projectModel->createEntry();
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($project);
        $form->addRow('code', 'string', array(
            'label' => $translator->translate('label.code'),
            'readonly' => $project->getEnvironments() ? true : false,
            'description' => $translator->translate('label.project.code.description'),
        ));
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'description' => $translator->translate('label.project.name.description'),
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $project = $form->getData();

                $isNew = $project->getId() ? false : true;

                $projectModel->save($project);

                $this->addSuccess('success.data.saved', array('data' => $project->getName()));

                if ($isNew || !$referer) {
                    $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/projects/form', array(
            'form' => $form->getView(),
            'project' => $project,
            'referer' => $referer,
        ));
    }

    public function deleteAction(OrmManager $orm, $project) {
        $projectModel = $orm->getProjectModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $referer = $this->getReferer();

        if ($this->request->isPost()) {
            $project->setIsDeleted(true);

            $projectModel->save($project);

            $this->addSuccess('success.data.deleted', array('data' => (string) $project));

            $referer = $this->getUrl('projects');

            $this->response->setRedirect($referer);

            return;
        }

        $this->setTemplateView('admin/projects/delete', array(
            'title' => 'title.projects',
            'project' => $project,
            'description' => 'label.delete.project.description',
            'data' => (string) $project,
            'referer' => $referer,
        ));
    }

    public function notesAction(OrmManager $orm, ServerService $serverService, $project) {
        $serverModel = $orm->getServerModel();
        $projectModel = $orm->getProjectModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($project);
        $form->addRow('notes', 'text', array(
            'label' => $translator->translate('label.notes'),
            'description' => $translator->translate('label.project.notes.description'),
            'attributes' => array(
                'rows' => 10,
            ),
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $project = $form->getData();

                $projectModel->save($project);

                $this->addSuccess('success.data.saved', array('data' => $project->getCode()));

                if (!$referer) {
                    $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/projects/notes.form', array(
            'form' => $form->getView(),
            'project' => $project,
            'referer' => $referer,
        ));
    }

    public function sshKeysAction(OrmManager $orm, $project) {
        $projectModel = $orm->getProjectModel();
        $sshKeyModel = $orm->getSshKeyModel();
        $keyChainModel = $orm->getKeyChainModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($project);
        $form->addRow('keyChains', 'object', array(
            'label' => $translator->translate('label.key-chains'),
            'description' => $translator->translate('label.project.key-chains.description'),
            'options' => $keyChainModel->find(),
            'value' => 'id',
            'property' => 'name',
            'multiple' => true,
        ));
        $form->addRow('sshKeys', 'object', array(
            'label' => $translator->translate('label.ssh-keys'),
            'description' => $translator->translate('label.project.ssh-keys.description'),
            'options' => $sshKeyModel->find(),
            'value' => 'id',
            'property' => 'label',
            'multiple' => true,
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $project = $form->getData();

                $projectModel->save($project);

                $this->addSuccess('success.data.saved', array('data' => $project->getName()));

                if (!$referer) {
                    $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/projects/ssh-keys.form', array(
            'form' => $form->getView(),
            'project' => $project,
            'referer' => $referer,
        ));
    }

    public function sshKeysDeleteAction(OrmManager $orm, $project, $sshKey) {
        $projectModel = $orm->getProjectModel();
        $sshKeyModel = $orm->getSshKeyModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $sshKey = $this->getEntry($sshKeyModel, $sshKey);
        if (!$sshKey) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->getReferer();
        if (!$referer) {
            $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
        }

        $sshKeys = $project->getSshKeys();
        if (!isset($sshKeys[$sshKey->getId()])) {
            $this->addWarning('warning.project.ssh-key.delete');

            $this->response->setRedirect($referer);

            return;
        }

        if ($this->request->isPost()) {
            $project->removeFromSshKeys($sshKey);

            $projectModel->save($project);

            $this->addSuccess('success.data.deleted', array('data' => (string) $project));

            $this->response->setRedirect($referer);

            return;
        }

        $this->setTemplateView('admin/projects/delete', array(
            'title' => 'title.projects',
            'project' => $project,
            'data' => $sshKey->getLabel(),
            'referer' => $referer,
        ));
    }

    public function cronAction(OrmManager $orm, ServerService $serverService, $project) {
        $serverModel = $orm->getServerModel();
        $projectModel = $orm->getProjectModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($project);
        $form->addRow('cronServer', 'object', array(
            'label' => $translator->translate('label.server'),
            'description' => $translator->translate('label.cron.server.description'),
            'disabled' => $project->getCronTab() ? true : false,
            'options' => $serverModel->find(array('filter' => array('isCron' => 1))),
            'value' => 'id',
            'property' => 'name',
            'widget' => 'select',
        ));
        $form->addRow('cronTab', 'text', array(
            'label' => $translator->translate('label.cron'),
            'description' => $translator->translate('label.cron.tab.description'),
            'attributes' => array(
                'rows' => 5,
            ),
	        'filters' => array(
                'trim' => array(
                    'trim.lines' => true,
                    'trim.empty' => true
                ),
            ),
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $project = $form->getData();

                $projectModel->save($project);

                $this->addSuccess('success.data.saved', array('data' => $project->getCode()));

                if (!$referer) {
                    $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $blockTime = 15;
        $cronTab = $projectModel->getCronTab();

        $cronHelper = $serverService->getCronHelper();
        $occupation = $cronHelper->getOccupation($cronTab);
        $occupation = $cronHelper->blockOccupation($occupation, $blockTime);

        $maxJobs = 0;
        foreach ($occupation as $hour => $minutes) {
            foreach ($minutes as $minute => $numJobs) {
                $maxJobs = max($maxJobs, $numJobs);
            }
        }

        $this->setTemplateView('admin/projects/cron.form', array(
            'form' => $form->getView(),
            'project' => $project,
            'cronTab' => $cronTab,
            'occupation' => $occupation,
            'maxJobs' => $maxJobs,
            'blockTime' => $blockTime,
            'referer' => $referer,
        ));
    }

    public function varnishAction(OrmManager $orm, ServerService $serverService, $project) {
        $serverModel = $orm->getServerModel();
        $projectModel = $orm->getProjectModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $maxMemory = null;

        $varnishServer = $project->getVarnishServer();
        if ($varnishServer) {
            $maxMemory = $varnishServer->getVarnishMemory() - $projectModel->getUsedVarnishMemory($varnishServer);
        }

        $form = $this->createFormBuilder($project);
        $form->addRow('varnishServer', 'object', array(
            'label' => $translator->translate('label.server'),
            'description' => $translator->translate('label.varnish.server.description'),
            'disabled' => $project->getVarnishServer() ? true : false,
            'options' => $serverModel->find(array('filter' => array('isVarnish' => 1))),
            'value' => 'id',
            'property' => 'name',
            'widget' => 'select',
        ));
        $form->addRow('varnishMemory', 'option', array(
            'label' => $translator->translate('label.memory'),
            'description' => $translator->translate('label.server.varnish.memory.description'),
            'default' => 128,
            'options' => $serverService->getMemoryOptions($translator, $maxMemory),
            'widget' => 'select',
        ));
        $form->addRow('varnishVcl', 'text', array(
            'label' => $translator->translate('label.vcl'),
            'description' => $translator->translate('label.vcl.description'),
            'attributes' => array(
                'rows' => 12,
            ),
	        'filters' => array(
                'trim' => array(),
            ),
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $project = $form->getData();

                $projectModel->save($project);

                $this->addSuccess('success.data.saved', array('data' => $project->getCode()));

                if (!$referer) {
                    $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/projects/varnish.form', array(
            'form' => $form->getView(),
            'project' => $project,
            'referer' => $referer,
        ));
    }


    public function varnishRestartAction(OrmManager $orm, $project) {
        $projectModel = $orm->getProjectModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project || !$project->getVarnishServer()) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $server = $project->getVarnishServer();
        $data = $server->getHost() . ':' . $project->getVarnishPort();

        $referer = $this->getReferer();
        if (!$referer) {
            $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
        }

        if ($this->request->isPost()) {
            $projectModel->restartVarnish($project);

            $this->addSuccess('success.varnish.restarted', array('data' => $data));

            $this->response->setRedirect($referer);

            return;
        }

        $translator = $this->getTranslator();

        $this->setTemplateView('admin/projects/delete', array(
            'title' => 'title.projects',
            'question' => $translator->translate('label.varnish.restart.description', array('server' => $data)),
            'button' => 'button.varnish.restart',
            'project' => $project,
            'data' => $data,
            'referer' => $referer,
        ));
    }

    public function varnishDeleteAction(OrmManager $orm, $project) {
        $projectModel = $orm->getProjectModel();

        $project = $this->getEntry($projectModel, $project, 'code');
        if (!$project || !$project->getVarnishServer()) {
            $this->response->setNotFound();

            return;
        }

        $orm->getProjectRecentModel()->pushProject($project, $this->getUser());

        $server = $project->getVarnishServer();
        $data = $server->getHost() . ':' . $project->getVarnishPort();

        $referer = $this->getReferer();
        if (!$referer) {
            $referer = $this->getUrl('projects.detail', array('project' => $project->getCode()));
        }

        if ($this->request->isPost()) {
            $project->setVarnishMemory(0);

            $projectModel->save($project);

            $this->addSuccess('success.varnish.deleted', array('data' => $data));

            $this->response->setRedirect($referer);

            return;
        }

        $translator = $this->getTranslator();

        $this->setTemplateView('admin/projects/delete', array(
            'title' => 'title.projects',
            'question' => $translator->translate('label.varnish.delete.description', array('server' => $data)),
            'button' => 'button.varnish.delete',
            'project' => $project,
            'data' => $data,
            'referer' => $referer,
        ));
    }

}
