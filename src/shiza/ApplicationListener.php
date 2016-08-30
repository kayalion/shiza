<?php

namespace shiza;

use ride\library\event\Event;
use ride\library\orm\OrmManager;
use ride\library\security\SecurityManager;

use ride\web\base\menu\MenuItem;
use ride\web\base\menu\Menu;
use ride\web\WebApplication;

class ApplicationListener {

    public function __construct(OrmManager $orm, SecurityManager $securityManager) {
        $this->orm = $orm;
        $this->securityManager = $securityManager;
    }

    /**
     * Action to add the CMS menus to the taskbar
     * @param \ride\library\event\Event $event Triggered event
     * @return null
     */
    public function prepareTaskbar(Event $event) {
        $menuItem = new MenuItem();
        $menuItem->setTranslation('button.overview');
        $menuItem->setRoute('repositories');

        $repositoriesMenu = new Menu();
        $repositoriesMenu->setId('repositories.menu');
        $repositoriesMenu->setTranslation('title.repositories');
        $repositoriesMenu->addMenuItem($menuItem);

        $menuItem = new MenuItem();
        $menuItem->setTranslation('button.overview');
        $menuItem->setRoute('projects');

        $projectsMenu = new Menu();
        $projectsMenu->setId('projects.menu');
        $projectsMenu->setTranslation('title.projects');
        $projectsMenu->addMenuItem($menuItem);

        $user = $this->securityManager->getUser();
        if ($user) {
            $projectRecentModel = $this->orm->getProjectRecentModel();
            $projects = $projectRecentModel->findProjectsByUser($user);
            if ($projects) {
                $projectsMenu->addSeparator();

                foreach ($projects as $project) {
                    $menuItem = new MenuItem();
                    $menuItem->setLabel($project->getName());
                    $menuItem->setRoute('projects.detail', array(
                        'project' => $project->getCode(),
                    ));

                    $projectsMenu->addMenuItem($menuItem);
                }
            }

            $repositoryRecentModel = $this->orm->getRepositoryRecentModel();
            $repositories = $repositoryRecentModel->findRepositoriesByUser($user);
            if ($repositories) {
                $repositoriesMenu->addSeparator();

                foreach ($repositories as $repository) {
                    $menuItem = new MenuItem();
                    $menuItem->setLabel($repository->getName());
                    $menuItem->setRoute('repositories.detail', array(
                        'repository' => $repository->getSlug(),
                    ));

                    $repositoriesMenu->addMenuItem($menuItem);
                }
            }
        }

        $taskbar = $event->getArgument('taskbar');

        $applicationMenu = $taskbar->getApplicationsMenu();
        $applicationMenu->addMenu($projectsMenu);
        $applicationMenu->addMenu($repositoriesMenu);
    }

}
