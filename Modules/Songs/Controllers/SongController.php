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


namespace Peregrinus\Flockr\Songs\Controllers;

use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Core\FlashMessage;
use Peregrinus\Flockr\Core\Helpers\FileSystemHelper;
use Peregrinus\Flockr\Songs\Repository\SongRepository;
use Peregrinus\Flockr\Songs\Services\SongSelectService;

class SongController extends \Peregrinus\Flockr\Core\AbstractController
{

    private $planRepository;
    private $songRepository;

    public function initializeController()
    {
        parent::initializeController();
        $this->planRepository = new \Peregrinus\Flockr\Songs\Repository\PlanRepository(
            $this->config['folders']['plans'],
            $this->config['folders']['songs']
        );
        $this->songRepository = new \Peregrinus\Flockr\Songs\Repository\SongRepository(
            $this->config['folders']['songs']
        );
    }

    public function listAction()
    {
        $this->view->assign('songs', $this->songRepository->findAll());
    }

    public function statisticsAction()
    {
        $this->view->assign('plans', $this->planRepository->findAll());
    }

    public function ccliImportAction()
    {

    }

    public function ccliImportSelectSongAction() {
        $this->app->getLayout()->addJavaScript('footer', 'Modules/Songs/Resources/Public/Scripts/ccliImportSelectSong.js');

        $q = $this->request->getArgument('q');
        if ($this->request->hasArgument('song')) {
            $songFile = $this->request->getArgument('song');
            $song = $this->songRepository->get($songFile);
        }

        $songSelectService = new SongSelectService();
        $result = $songSelectService->find($q);

        if (!count($result)) {
            App::getInstance()->getLayout()->addFlashMessage(
                new FlashMessage('Es wurden keine Ergebnisse gefunden.', FlashMessage::WARNING)
            );
            $this->forward($this->request->getArgument('back'));
        }

        $this->view->assign('q', $q);
        $this->view->assign('next', $this->request->getArgument('next'));
        $this->view->assign('result', $result);
        $this->view->assign('song', $song);
    }

    public function ccliImportDoImportAction() {
        $no = $this->request->getArgument('song');
        $songSelectService = new SongSelectService();
        $song = $songSelectService->getbyNumber($no, true);
        $title = $song->getMeta()['title'];
        $song->saveToSongFile(FileSystemHelper::getAbsolutePath($this->config['folders']['songs']).$title.'.sng');

        App::getInstance()->getLayout()->addFlashMessage(
            new FlashMessage('"'.$title.'" wurde erfolgreich importiert und als "'.$title.'.sng" gespeichert.', FlashMessage::SUCCESS)
        );

        $this->forward('ccliImport');
    }

    public function ccliSongDataAction() {
        $no = $this->request->getArgument('song');
        $songSelectService = new SongSelectService();
        $song = $songSelectService->getbyNumber($no, false);
        $this->view->assign('song', $song);
    }

    public function ccliImportMetaAction() {
        $songSelectService = new SongSelectService();
        $ccliSong = $songSelectService->getbyNumber($this->request->getArgument('song'), true);
        $mySong = $this->songRepository->get($this->request->getArgument('file'));
        $title = $mySong->getMeta()['title'];

        $ccliMeta = $ccliSong->getMeta();
        $myMeta = $mySong->getMeta();
        foreach ($ccliMeta as $key=>$value) {
            $myMeta[$key] = $value;
        }
        $mySong->setMeta($myMeta);
        $mySong->saveToSongFile();
        App::getInstance()->getLayout()->addFlashMessage(
            new FlashMessage('"'.$title.'" wurde erfolgreich mit den Daten von CCLI abgeglichen und als "'.$mySong->getFile().'" gespeichert.', FlashMessage::SUCCESS)
        );
        $this->forward('list');
    }

}