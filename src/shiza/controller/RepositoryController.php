<?php

namespace shiza\controller;

use ride\library\i18n\translator\Translator;
use ride\library\orm\OrmManager;
use ride\library\validation\exception\ValidationException;

use ride\service\MimeService;

use shiza\orm\entry\RepositoryBuilderEntry;
use shiza\orm\entry\RepositoryEntry;

use shiza\service\VcsService;

use \Exception;

class RepositoryController extends AbstractRepositoryController {

    public function indexAction(OrmManager $orm) {
        $repositoryModel = $orm->getRepositoryModel();

        $query = $repositoryModel->createQuery();
        $query->addOrderBy('{name} ASC');

        $repositories = $query->query();

        $this->setTemplateView('admin/repositories/index', array(
            'repositories' => $repositories,
        ));
    }

    public function formAction(OrmManager $orm, $repository = null) {
        $repositoryModel = $orm->getRepositoryModel();

        $repository = $this->getEntry($repositoryModel, $repository, 'slug');
        if (!$repository) {
            $repository = $repositoryModel->createEntry();
        }

        $orm->getRepositoryRecentModel()->pushRepository($repository, $this->getUser());

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($repository);
        $form->addRow('vcsManager', 'option', array(
            'label' => $translator->translate('label.manager'),
            'description' => $translator->translate('label.manager.vcs.description'),
            'readonly' => $repository->getId() ? true : false,
            'options' => $this->getVcsManagerOptions($translator),
            'widget' => 'select',
        ));
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'description' => $translator->translate('label.repository.name.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('url', 'string', array(
            'label' => $translator->translate('label.url'),
            'description' => $translator->translate('label.repository.url.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $repository = $form->getData();

                $isNew = $repository->getId() ? false : true;

                $repositoryModel->save($repository);

                $this->addSuccess('success.data.saved', array('data' => $repository->getName()));

                if ($isNew || !$referer) {
                    $referer = $this->getUrl('repositories.detail', array('repository' => $repository->getId()));
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/repositories/form', array(
            'form' => $form->getView(),
            'repository' => $repository,
            'referer' => $referer,
        ));
    }

    public function deleteAction(OrmManager $orm, $repository) {
        $repository = $this->resolveRepository($orm, $repository);
        if (!$repository) {
            return;
        }

        $referer = $this->getReferer();

        if ($this->request->isPost()) {
            $orm->getRepositoryModel()->delete($repository);

            $this->addSuccess('success.data.deleted', array('data' => $repository->getName()));

            $referer = $this->getUrl('repositories');

            $this->response->setRedirect($referer);

            return;
        }

        $this->setTemplateView('admin/delete', array(
            'title' => 'title.repositories',
            'subtitle' => $repository->getName(),
            // 'description' => 'label.delete.repositoriy.description',
            'data' => (string) $repository,
            'referer' => $referer,
        ));
    }

    public function detailAction($repository) {
        $url = $this->getUrl('repositories.browse', array('repository' => $repository));

        $this->response->setRedirect($url);
    }

    /**
     * Action to show an overview of the files in a branch
     * @param OrmManager $orm
     * @param string $repository Id of the repository
     * @param string $branch Name of the branch
     * @return null
     */
    public function browseAction(OrmManager $orm, VcsService $vcsService, MimeService $mimeService, $repository) {
        $repository = $this->resolveRepository($orm, $repository);
        if (!$repository) {
            return;
        }

        if ($repository->isNew()) {
            $this->addWarning('warning.repository.status.' . $repository->getStatus());

            $this->setTemplateView('admin/repositories/status', array(
                'repository' => $repository,
            ));

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

                $this->response->setRedirect($this->getUrl('repositories.browse', array('repository' => $repository->getSlug())) . '/' . $branch);
            }

            return;
        }

        $directoryBranch = $repository->getBranchDirectory($directory, $branch);
        $repositoryBranch = $vcsService->getVcsRepository($repository->getVcsManager(), $repository->getUrl(), $directoryBranch);

        if ($pathNormalized) {
            $path = $directoryBranch->getChild($pathNormalized);
        } else {
            $path = $directoryBranch;
        }

        if (!$path->exists()) {
            $this->response->setNotFound();

            return;
        }

        $variables = array(
            'repository' => $repository,
            'breadcrumbs' => $this->createBreadcrumbs(explode('/', $pathNormalized), 'repositories.browse', $repository, $branch),
            'branch' => $branch,
            'branches' => $branches,
            'path' => rtrim('/' . implode('/', $pathTokens), '/'),
            'name' => $path->getName(),
        );

        if ($path->isDirectory()) {
            $template = 'admin/repositories/browse';
            $variables['files'] = $repositoryBranch->getTree($branch, $pathNormalized ? $pathNormalized . '/' : null);
        } else {
            $template = 'admin/repositories/file';
            $variables['file'] = $path;
            $variables['mime'] = $mimeService->getMediaTypeForFile($path);
        }

        $this->setTemplateView($template, $variables);
    }

    public function messagesAction(OrmManager $orm, VcsService $vcsService, $repository) {
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

                $this->response->setRedirect($this->getUrl('repositories.messages', array('repository' => $repository->getSlug())) . '/' . $branch);
            }

            return;
        }

        $messageModel = $orm->getMessageModel();
        $messageQuery = $messageModel->createQuery();
        $messageQuery->addCondition('{repository} = %1%', $repository->getId());
        $messageQuery->addOrderBy('{dateAdded} DESC');
        $messageQuery->setLimit(15);

        $messages = $messageQuery->query();

        $this->setTemplateView('admin/repositories/messages', array(
            'repository' => $repository,
            'messages' => $messages,
            'branch' => $branch,
            'branches' => $branches,
        ));
    }


    /**
     * Action to show an overview of the deployers of a branch
     * @param OrmManager $orm
     * @param string $repository Id of the repository
     * @param string $branch Name of the branch
     * @return null
     */
    public function deploymentAction(OrmManager $orm, VcsService $vcsService, $repository) {
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

                $this->response->setRedirect($this->getUrl('repositories.deployment', array('repository' => $repository->getSlug())) . '/' . $branch);
            }

            return;
        }

        $this->setTemplateView('admin/repositories/deployment', array(
            'repository' => $repository,
            'deployers' => $repository->getDeployersForBranch($branch),
            'branch' => $branch,
            'branches' => $branches,
        ));
    }

    /**
     * Action to show an overview of the builders of a branch
     * @param OrmManager $orm
     * @param string $repository Id of the repository
     * @param string $branch Name of the branch
     * @return null
     */
    public function integrationAction(OrmManager $orm, VcsService $vcsService, $repository) {
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

                $this->response->setRedirect($this->getUrl('repositories.integration', array('repository' => $repository->getSlug())) . '/' . $branch);
            }

            return;
        }

        $this->setTemplateView('admin/repositories/integration', array(
            'repository' => $repository,
            'builders' => $repository->getBuildersForBranch($branch),
            'branch' => $branch,
            'branches' => $branches,
        ));
    }

    public function integrationAddAction(OrmManager $orm, VcsService $vcsService, $repository) {
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

                $this->response->setRedirect($this->getUrl('repositories.integration.add', array('repository' => $repository->getSlug())) . '/' . $branch);
            }

            return;
        }

        $this->integrationFormAction($orm, $repository, $branch);
    }

    public function integrationEditAction(OrmManager $orm, VcsService $vcsService, $repository, $builder) {
        $repository = $this->resolveRepository($orm, $repository);
        if (!$repository) {
            return;
        }

        $builderModel = $orm->getRepositoryBuilderModel();

        $builder = $this->getEntry($builderModel, $builder, 'slug');
        if (!$builder || $builder->getRepository()->getId() != $repository->getId()) {
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

                $url = $this->getUrl('repositories.integration.edit', array(
                    'repository' => $repository->getSlug(),
                    'builder' => $builder->getId(),
                ) . '/' . $branch);

                $this->response->setRedirect($url);
            }

            return;
        } elseif ($builder->getBranch() != $branch) {
            $this->response->setNotFound();

            return;
        }

        $this->integrationFormAction($orm, $repository, $branch, $builder);
    }

    private function integrationFormAction(OrmManager $orm, RepositoryEntry $repository, $branch, RepositoryBuilderEntry $builder = null) {
        $builderModel = $orm->getRepositoryBuilderModel();

        if ($builder == null) {
            $builder = $builderModel->createEntry();
            $builder->setRepository($repository);
            $builder->setBranch($branch);
        }

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($builder);
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'description' => $translator->translate('label.builder.name.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('buildManager', 'option', array(
            'label' => $translator->translate('label.builder.manager'),
            'description' => $translator->translate('label.builder.manager.description'),
            'options' => $this->getBuildManagerOptions($translator),
            'validators' => array(
                'required' => array(),
            ),
            'widget' => 'select',
        ));
        $form->addRow('isAutomatic', 'option', array(
            'label' => $translator->translate('label.automatic'),
            'description' => $translator->translate('label.builder.automatic.description'),
        ));
        $form->addRow('willCheckout', 'option', array(
            'label' => $translator->translate('label.builder.checkout'),
            'description' => $translator->translate('label.builder.checkout.description'),
        ));
        $form->addRow('script', 'text', array(
            'label' => $translator->translate('label.builder.script'),
            'description' => $translator->translate('label.builder.script.description'),
            'attributes' => array(
                'class' => 'console',
                'rows' => 7,
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $builder = $form->getData();

                $isNew = $builder->getId() ? false : true;

                $builderModel->save($builder);

                $this->addSuccess('success.data.saved', array('data' => $builder->getName()));

                if ($isNew || !$referer) {
                    $referer = $this->getUrl('repositories.integration', array('repository' => $repository->getId())) . '/' . $branch;
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/repositories/builder.form', array(
            'form' => $form->getView(),
            'repository' => $repository,
            'builder' => $builder,
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
    public function integrationBuildAction(OrmManager $orm, VcsService $vcsService, $repository) {
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

                $this->response->setRedirect($this->getUrl('repositories.integration.build', array('repository' => $repository->getSlug())) . '/' . $branch);
            }

            return;
        }

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder();
        $form->addRow('builders', 'object', array(
            'label' => $translator->translate('label.builders'),
            'description' => $translator->translate('label.builders.build.description'),
            'options' => $repository->getBuildersForBranch($branch),
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

                $builderModel = $orm->getRepositoryBuilderModel();
                $data = $form->getData();

                foreach ($data['builders'] as $builder) {
                    $builderModel->build($builder);
                }

                $this->addSuccess('success.builders.queued');

                if (!$referer) {
                    $referer = $this->getUrl('repositories.integration', array('repository' => $repository->getId())) . '/' . $branch;
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/repositories/integration.form', array(
            'form' => $form->getView(),
            'repository' => $repository,
            'branch' => $branch,
            'branches' => $branches,
            'referer' => $referer,
        ));
    }

    /**
     * Creates breadcrumbs out of the path tokens
     * @param array $tokens Path tokens
     * @param string $url Id of the URL
     * @param dbud\model\data\RepositoryData $repository Repository data
     * @param string $branch Name of the branch
     * @return zibo\library\html\Breadcrumbs
     */
    protected function createBreadcrumbs(array $tokens, $url, $repository, $branch) {
        $url = $this->getUrl($url, array('repository' => $repository->getSlug())) . '/' . $branch;

        $breadcrumbs = array();
        $breadcrumbs[$url] = $repository->getName();

        $breadcrumbPath = '';
        foreach ($tokens as $pathToken) {
            if (!$pathToken) {
                continue;
            }

            $breadcrumbPath .= '/' . $pathToken;

            $breadcrumbs[$url . $breadcrumbPath] = $pathToken;
        }

        return $breadcrumbs;
    }

}
