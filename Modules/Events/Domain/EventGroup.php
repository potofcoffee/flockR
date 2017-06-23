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


namespace Peregrinus\Flockr\Events\Domain;


class EventGroup
{
    protected $id;
    protected $calendar;
    protected $groupTitle;
    protected $shortTitle;
    protected $room;
    protected $startTime;
    protected $endTime;
    protected $title;
    protected $description;
    protected $color;
    protected $reservations;
    protected $rota;
    protected $reservationStartTime;
    protected $reservationEndTime;
    protected $reservationsCombined;
    protected $tapes;
    protected $url;
    protected $moderation;
    protected $notify;
    protected $type;
    protected $gCalUrl;
    protected $facebookPages;
    protected $image;
    protected $iCalUrl;
    protected $update;
    protected $lastUpdate;
    protected $groupImage;
    protected $skipListTitle;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * @param mixed $calendar
     */
    public function setCalendar($calendar)
    {
        $this->calendar = $calendar;
    }

    /**
     * @return mixed
     */
    public function getGroupTitle()
    {
        return $this->groupTitle;
    }

    /**
     * @param mixed $groupTitle
     */
    public function setGroupTitle($groupTitle)
    {
        $this->groupTitle = $groupTitle;
    }

    /**
     * @return mixed
     */
    public function getShortTitle()
    {
        return $this->shortTitle;
    }

    /**
     * @param mixed $shortTitle
     */
    public function setShortTitle($shortTitle)
    {
        $this->shortTitle = $shortTitle;
    }

    /**
     * @return mixed
     */
    public function getRoom()
    {
        return $this->room;
    }

    /**
     * @param mixed $room
     */
    public function setRoom($room)
    {
        $this->room = $room;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param mixed $startTime
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param mixed $endTime
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * @return mixed
     */
    public function getReservations()
    {
        return $this->reservations;
    }

    /**
     * @param mixed $reservations
     */
    public function setReservations($reservations)
    {
        $this->reservations = $reservations;
    }

    /**
     * @return mixed
     */
    public function getRota()
    {
        return $this->rota;
    }

    /**
     * @param mixed $rota
     */
    public function setRota($rota)
    {
        $this->rota = $rota;
    }

    /**
     * @return mixed
     */
    public function getReservationStartTime()
    {
        return $this->reservationStartTime;
    }

    /**
     * @param mixed $reservationStartTime
     */
    public function setReservationStartTime($reservationStartTime)
    {
        $this->reservationStartTime = $reservationStartTime;
    }

    /**
     * @return mixed
     */
    public function getReservationEndTime()
    {
        return $this->reservationEndTime;
    }

    /**
     * @param mixed $reservationEndTime
     */
    public function setReservationEndTime($reservationEndTime)
    {
        $this->reservationEndTime = $reservationEndTime;
    }

    /**
     * @return mixed
     */
    public function getReservationsCombined()
    {
        return $this->reservationsCombined;
    }

    /**
     * @param mixed $reservationsCombined
     */
    public function setReservationsCombined($reservationsCombined)
    {
        $this->reservationsCombined = $reservationsCombined;
    }

    /**
     * @return mixed
     */
    public function getTapes()
    {
        return $this->tapes;
    }

    /**
     * @param mixed $tapes
     */
    public function setTapes($tapes)
    {
        $this->tapes = $tapes;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getModeration()
    {
        return $this->moderation;
    }

    /**
     * @param mixed $moderation
     */
    public function setModeration($moderation)
    {
        $this->moderation = $moderation;
    }

    /**
     * @return mixed
     */
    public function getNotify()
    {
        return $this->notify;
    }

    /**
     * @param mixed $notify
     */
    public function setNotify($notify)
    {
        $this->notify = $notify;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getGCalUrl()
    {
        return $this->gCalUrl;
    }

    /**
     * @param mixed $gCalUrl
     */
    public function setGCalUrl($gCalUrl)
    {
        $this->gCalUrl = $gCalUrl;
    }

    /**
     * @return mixed
     */
    public function getFacebookPages()
    {
        return $this->facebookPages;
    }

    /**
     * @param mixed $facebookPages
     */
    public function setFacebookPages($facebookPages)
    {
        $this->facebookPages = $facebookPages;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getICalUrl()
    {
        return $this->iCalUrl;
    }

    /**
     * @param mixed $iCalUrl
     */
    public function setICalUrl($iCalUrl)
    {
        $this->iCalUrl = $iCalUrl;
    }

    /**
     * @return mixed
     */
    public function getUpdate()
    {
        return $this->update;
    }

    /**
     * @param mixed $update
     */
    public function setUpdate($update)
    {
        $this->update = $update;
    }

    /**
     * @return mixed
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @param mixed $lastUpdate
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;
    }

    /**
     * @return mixed
     */
    public function getGroupImage()
    {
        return $this->groupImage;
    }

    /**
     * @param mixed $groupImage
     */
    public function setGroupImage($groupImage)
    {
        $this->groupImage = $groupImage;
    }

    /**
     * @return mixed
     */
    public function getSkipListTitle()
    {
        return $this->skipListTitle;
    }

    /**
     * @param mixed $skipListTitle
     */
    public function setSkipListTitle($skipListTitle)
    {
        $this->skipListTitle = $skipListTitle;
    }


}