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


namespace Peregrinus\Flockr\Songs\Services;


use Peregrinus\Flockr\Songs\Domain\Song;

class SongSelectService extends CCLIService
{
    public function __construct()
    {
        parent::__construct('SongSelect');
        $this->login();
    }

    public function find($queryString)
    {
        $doc = \phpQuery::newDocumentHTML($this->get('https://songselect.ccli.com/search/results?SearchText=' . urlencode($queryString)));

        $songResult = [];
        foreach ($doc['.song-result'] as $res) {
            $result = pq($res);
            $ccliNo = str_replace('/Songs/', '', $result->find('p.song-result-title a')->attr('href'));
            $ccliNo = substr($ccliNo, 0, strpos($ccliNo, '/'));
            $songResult[] = $ccliNo;
        }
        return $songResult;
    }

    /**
     * Get song data by CCLI number
     * @param string $ccliNo CCLI number
     * @param bool $getLyrics getLyrics as well?
     */
    public function getbyNumber($ccliNo, $getLyrics = false)
    {
        $doc = \phpQuery::newDocumentHTML($this->get($this->getBaseUrl() . '/Songs/' . $ccliNo));
        foreach ($doc['ul.authors li a'] as $author) {
            $authors[] = $author->textContent;
        }


        // meta lists
        foreach ($doc['ul.song-meta-list'] as $list) {
            $ul = pq($list);
            $listTitle = $ul['li:first-child']->html();
            $ul->find('li:first-child')->remove();
            foreach ($ul['li'] as $item) {
                $metaList[$listTitle][] = strip_tags($item->textContent);
            }
        }

        $meta = [
            'title' => $doc['.content-title h1']->html(),
            'author' => join(', ', $authors),
            'CCLI' => $ccliNo,
            '(c)' => join(', ',
                    $metaList['Urheberrechte']) . (count($metaList['Verwalter']) ? '. Verwaltet von ' . join(', ',
                        $metaList['Verwalter']) : ''),
            'key' => $doc['.song-content-data ul li:last-child strong']->html(),
        ];


        $song = new Song();
        $song->setMeta($meta);

        $doc['.song-info .large-4 p br']->replaceWith(Song::EOL);
        $song->setPreview($doc['.song-info .large-4 p']->html());

        if ($getLyrics) {
            $song->setUnits($this->getLyricsUnits($ccliNo));
        }
        return $song;
    }

    protected function getLyricsUnits($ccliNo)
    {
        $doc = \phpQuery::newDocumentHTML($this->get($this->getBaseUrl() . '/Songs/' . $ccliNo . '/x/viewlyrics'));

        $partTitles = [];
        foreach ($doc['h3.song-viewer-part'] as $partTitle) {
            $partTitles[] = $partTitle->textContent;
        }

        // unset the last paragraph (CCLI Number)
        $doc['.copyright-info']->remove();
        $doc['.song-viewer p:last-child']->remove();

        $ctr = 0;
        foreach ($doc['.song-viewer p'] as $p) {
            $p = pq($p);
            $units[$partTitles[$ctr]] = $partTitles[$ctr] . Song::EOL . $p->html();
            $ctr++;
        }

        return $units;
    }

}