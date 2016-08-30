<?php

namespace shiza\controller;

use ride\library\i18n\translator\Translator;
use ride\library\orm\OrmManager;
use ride\library\validation\exception\ValidationException;

use ride\service\MimeService;

use shiza\orm\entry\RepositoryDeployerEntry;
use shiza\orm\entry\RepositoryEntry;

use shiza\service\ServerService;
use shiza\service\VcsService;

use \Exception;

class RepositoryDeployerController extends AbstractRepositoryController {

    public function addAction(OrmManager $orm, VcsService $vcsService, ServerService $serverService, $repository) {
        $repository = $this->resolveRepository($orm, $repository);
        if (!$repository) {
            return;
        }

        $directory = $vcsService->getWorkingDirectory();
        $directoryHead = $repository->getBranchDirectory($directory);

        $repositoryHead = $vcsService->getVcsRepository($repository->getVcsManager(), $repository->getUrl(), $directoryHead);
        $branches = $repositoryHead->getBranches();
        $pathTokens = array_slice(func_get_args(), 4);

        $this->resolveBranch($branches, $pathTokens, $branch, $pathNormalized);
        if (!$branch) {
            if ($pathTokens) {
                $this->response->setNotFound();
            } else {
                $branch = $repositoryHead->getBranch();

                $this->response->setRedirect($this->getUrl('repositories.deployment.add', array('repository' => $repository->getSlug())) . '/' . $branch);
            }

            return;
        }

        $repositoryDeployerModel = $orm->getRepositoryDeployerModel();
        $projectEnvironmentModel = $orm->getProjectEnvironmentModel();

        $deployer = $repositoryDeployerModel->createEntry();
        $deployer->setRepository($repository);
        $deployer->setBranch($branch);

        $translator = $this->getTranslator();

        $empty = new \stdClass();
        $empty->name = '---';

        $form = $this->createFormBuilder($deployer);
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'description' => $translator->translate('label.deployer.name.description'),
        ));
        $form->addRow('deployManager', 'option', array(
            'label' => $translator->translate('label.manager.deploy'),
            'description' => $translator->translate('label.manager.deploy.description'),
            'options' => $this->getDeployManagerOptions($translator),
            'validators' => array(
                'required' => array(),
            ),
            'widget' => 'select',
        ));
        $form->addRow('environment', 'object', array(
            'label' => $translator->translate('label.deployer.environment'),
            'description' => $translator->translate('label.deployer.environment.description'),
            'options' => array('' => $empty) + $projectEnvironmentModel->find(),
            'value' => 'id',
            'property' => 'name',
            'widget' => 'select',
        ));
        $form = $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $deployer = $form->getData();

                if ($deployer->environment) {
                    $server = $deployer->environment->getServer();
                    $webManager = $serverService->getWebManager($server->getWebManager());

                    $deployer->setRemoteHost($server->getHost());
                    $deployer->setRemotePort($server->getPort());
                    $deployer->setRemoteUsername($deployer->environment->getProject()->getUsername());
                    $deployer->setRemotePath($webManager->getWebRoot($deployer->environment));
                }

                return $this->formAction($orm, $deployer);
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        } elseif ($this->request->isPost()) {
            return $this->formAction($orm, $deployer);
        }

        $this->setTemplateView('admin/repositories/deployer.add', array(
            'form' => $form->getView(),
            'repository' => $repository,
            'referer' => $this->getReferer(),
        ));
    }

    public function editAction(OrmManager $orm, VcsService $vcsService, $repository, $deployer) {
        $repository = $this->resolveRepository($orm, $repository);
        if (!$repository) {
            return;
        }

        $deployerModel = $orm->getRepositoryDeployerModel();

        $deployer = $this->getEntry($deployerModel, $deployer, 'slug');
        if (!$deployer || $deployer->getRepository()->getId() != $repository->getId()) {
            $this->response->setNotFound();

            return;
        }

        $directory = $vcsService->getWorkingDirectory();
        $directoryHead = $repository->getBranchDirectory($directory);

        $repositoryHead = $vcsService->getVcsRepository($repository->getVcsManager(), $repository->getUrl(), $directoryHead);
        $branches = $repositoryHead->getBranches();
        $pathTokens = array_slice(func_get_args(), 4);

        $this->resolveBranch($branches, $pathTokens, $branch, $pathNormalized);
        if (!$branch) {
            if ($pathTokens) {
                $this->response->setNotFound();
            } else {
                $branch = $repositoryHead->getBranch();

                $url = $this->getUrl('repositories.deployment.edit', array(
                    'repository' => $repository->getSlug(),
                    'deployer' => $deployer->getId(),
                ) . '/' . $branch);

                $this->response->setRedirect($url);
            }

            return;
        } elseif ($deployer->getBranch() != $branch) {
            $this->response->setNotFound();

            return;
        }

        $this->formAction($orm, $deployer);
    }

    private function formAction(OrmManager $orm, RepositoryDeployerEntry $deployer) {
        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($deployer);
        $form->setAction('save');
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'description' => $translator->translate('label.deployer.name.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('deployManager', 'option', array(
            'label' => $translator->translate('label.manager.deploy'),
            'description' => $translator->translate('label.manager.deploy.description'),
            'readonly' => true,
            'options' => $this->getDeployManagerOptions($translator),
            'validators' => array(
                'required' => array(),
            ),
            'widget' => 'select',
        ));
        $form->addRow('isAutomatic', 'option', array(
            'label' => $translator->translate('label.automatic'),
            'description' => $translator->translate('label.deployer.automatic.description'),
        ));

        $deployManager = $this->dependencyInjector->get('shiza\\manager\\deploy\\DeployManager', $deployer->getDeployManager());
        $deployManager->createForm($form, $translator);

        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $deployer = $form->getData();

                $deployManager->processForm($deployer);

                $isNew = $deployer->getId() ? false : true;

                $orm->getRepositoryDeployerModel()->save($deployer);

                $this->addSuccess('success.data.saved', array('data' => $deployer->getName()));

                if ($isNew || !$referer) {
                    $referer = $this->getUrl('repositories.deployment', array('repository' => $deployer->getRepository()->getId())) . '/' . $deployer->getBranch();
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/repositories/deployer.form', array(
            'form' => $form->getView(),
            'repository' => $deployer->getRepository(),
            'deployer' => $deployer,
            'referer' => $referer,
        ));
    }

    /**
     * Action to show an overview of the builders of a branch
     * @param OrmManager $orm
     * @param string $repository Id of the repository
     * @param string $branch Name of the branch
     * @return null
     */
    public function deployAction(OrmManager $orm, VcsService $vcsService, $repository) {
        $repository = $this->resolveRepository($orm, $repository);
        if (!$repository) {
            return;
        }

        $directory = $vcsService->getWorkingDirectory();
        $directoryHead = $repository->getBranchDirectory($directory);

        $repositoryHead = $vcsService->getVcsRepository($repository->getVcsManager(), $repository->getUrl(), $directoryHead);
        $branches = $repositoryHead->getBranches();
        $pathTokens = array_slice(func_get_args(), 3);

        $this->resolveBranch($branches, $pathTokens, $branch, $pathNormalized);
        if (!$branch) {
            if ($pathTokens) {
                $this->response->setNotFound();
            } else {
                $branch = $repositoryHead->getBranch();

                $this->response->setRedirect($this->getUrl('repositories.deployment.deploy', array('repository' => $repository->getSlug())) . '/' . $branch);
            }

            return;
        }

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder();
        $form->addRow('deployers', 'object', array(
            'label' => $translator->translate('label.deployers'),
            'description' => $translator->translate('label.deployers.deploy.description'),
            'options' => $repository->getDeployersForBranch($branch),
            'value' => 'id',
            'property' => 'name',
            'multiple' => true,
            'validators' => array(
                'required' => array(),
            ),
            'widget' => 'option',
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $deployerModel = $orm->getRepositoryDeployerModel();
                $data = $form->getData();

                foreach ($data['deployers'] as $deployer) {
                    $deployerModel->deploy($deployer);
                }

                $this->addSuccess('success.deployers.queued');

                if (!$referer) {
                    $referer = $this->getUrl('repositories.deployment', array('repository' => $repository->getId())) . '/' . $branch;
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/repositories/deployment.form', array(
            'form' => $form->getView(),
            'repository' => $repository,
            'branch' => $branch,
            'branches' => $branches,
            'referer' => $referer,
        ));
    }

}
