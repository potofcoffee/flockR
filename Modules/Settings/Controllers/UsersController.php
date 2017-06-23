<?php
/*
 * FLOCKR
 * Multi-Purpose Church Administration Suite
 * http://github.com/potofcoffee/flockr
 * http://flockr.org
 *
 * Copyright (c) 2016+ Christoph Fischer (chris@toph.de)
 *
 * Parts copyright 2003-2015 Renzo Lauper, renzo@churchtool.org
 * FlockR is a fork from the kOOL project (www.churchtool.org). kOOL is available
 * under the terms of the GNU General Public License (see below).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


namespace Peregrinus\Flockr\Settings\Controllers;


use Peregrinus\Flockr\Core\AbstractController;
use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Domain\UserPermission;
use Peregrinus\Flockr\Core\FlashMessage;
use Peregrinus\Flockr\Core\ModuleLoader;

class UsersController extends AbstractController
{

    protected $userRepository = null;

    public function __construct($module)
    {
        parent::__construct($module);
        $this->userRepository = App::getInstance()->getEntityManager()->getRepository('Peregrinus\Flockr\Core\Domain\User');
    }

    public function listAction() {
        $this->view->assign('users', $this->userRepository->findAll());
    }

    public function rightsAction() {
        $this->request->applyUriPattern(['user']);
        if (!$this->request->hasArgument('user')) $this->redirectToAction('list');

        $user = $this->userRepository->findOneById($this->request->getArgument('user'));
        if (!$user) $this->redirectToAction('list');

        $userGroups = $user->getUserGroups();
        $activeModules = ModuleLoader::getModules();

        $modules = [];
        foreach ($activeModules as $module) {
            $modules[] = [
                'module' => $module,
                'levels' => $module->getUserLevels(),
                'objects' => $module->getPermissionObjects(),
            ];
        }

        $rights = App::getInstance()->getPermissionsService()->getAllPermissions($user);

        $this->view->assign('user', $user);
        $this->view->assign('groups', $userGroups);
        $this->view->assign('modules', $modules);
        $this->view->assign('rights', $rights);

        $this->app->getLayout()->addJavaScript('footer', 'Modules/Settings/Resources/Public/Scripts/UserRights.js');

    }

    function editRightsAction() {
        $permissions = $this->request->getArgument('permissions');
        $this->request->applyUriPattern(['user']);
        if (!$this->request->hasArgument('user')) $this->redirectToAction('list');

        $user = $this->userRepository->findOneById($this->request->getArgument('user'));
        if (!$user) $this->redirectToAction('list');

        foreach ($permissions as $module => $modulePermissions) {
            foreach ($modulePermissions as $object => $level) {
                $storedPermission =
                    App::getInstance()->getPermissionsService()->getPermissionRecord($module, $object, $user);

                if ($storedPermission) {
                    $storedPermission->setLevel($level);
                } else {
                    $storedPermission = new UserPermission();
                    $storedPermission->setUser($user->getId());
                    $storedPermission->setModule($module);
                    $storedPermission->setObject($object);
                    $storedPermission->setLevel($level);
                }
                App::getInstance()->getEntityManager()->persist($storedPermission);
            }
        }
        App::getInstance()->getEntityManager()->flush();
        App::getInstance()->getLayout()->addFlashMessage(
            new FlashMessage('Die Änderungen an den Benutzerrechten wurden gespeichert.', FlashMessage::SUCCESS)
        );
        $this->forward('list');
    }

}