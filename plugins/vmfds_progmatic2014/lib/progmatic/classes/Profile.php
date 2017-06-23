<?php

namespace de\peregrinus\progmatic;

require_once('RoomProfile.php');

/**
 * Progmatic program profile
 *
 * @author Christoph Fischer <christoph.fischer@volksmission.de>
 */
class profile
{
    protected $header = '';
    protected $roomProfiles = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        // sensible defaults
        $this->header = \hex2bin('45544446000000000000000000000000010200000000000000000000000000000000040b1b0b0e000000000000000000');
        for ($i = 0; $i < 10; $i++) {
            $this->roomProfiles[$i] = new \de\peregrinus\progmatic\roomProfile();
            $this->disableRoomProfile($i);
        }
    }

    /**
     * Create new instance from existing profile file
     * 
     * @param type $fileName Source file
     * @return \de\peregrinus\progmatic\profile Profile object
     */
    public static function fromFile($fileName)
    {
        echo 'Opening '.$fileName.'... ';
        if (file_exists($fileName)) {
            echo 'found.<br />';
            $obj = new self();

            $fp = fopen($fileName, 'r');
            // read header
            $temp = fread($fp, 48);
            $temp = unpack('A48header', $temp);
            if (substr($temp['header'], 0, 4) !== 'ETDF') {
                throw new Exception('Wrong file type.');
            }
            $obj->setHeader($temp['header']);

            // read 10 room profiles
            for ($i = 0; $i < 10; $i++) {
                $temp = fread($fp, 200);
                $rp = $obj->getRoomProfile($i);
                $rp->importRawData($temp);
            }

            // read 10 room profile titles
            for ($i = 0; $i < 10; $i++) {
                $temp = unpack('A32title', fread($fp, 64));
                $title = str_replace('#', '', $temp['title']);
                $rp = $obj->getRoomProfile($i);
                $rp->setTitle($title);
            }

            fclose($fp);

            return $obj;
        } else {
            throw new Exception('File not found: '.$fileName);
        }
    }

    /**
     * Set the header data
     * @param string $data Data
     * @return \de\peregrinus\progmatic\profile Profile object
     */
    public function setHeader($data)
    {
        $this->header = $data;
        return $this;
    }

    /**
     * Get a specific roomProfile
     * @param \int $index Index
     * @return \de\peregrinus\progmatic\roomProfile Room profile
     */
    public function getRoomProfile($index)
    {
        return $this->roomProfiles[$index];
    }

    /**
     * Export raw data for binary file
     * @return string Data
     */
    public function exportRawData()
    {
        $o = pack('A48', $this->header);
        for ($i = 0; $i < 10; $i++) {
            $o .= $this->roomProfiles[$i]->exportRawData();
        }
        for ($i = 0; $i < 10; $i++) {
            $o .= $this->roomProfiles[$i]->exportRawTitle();
        }
        return $o;
    }

    /**
     * Write to a .dat file
     * @param string $fileName File name
     * @return \de\peregrinus\progmatic\profile Profile object
     */
    public function toFile($fileName)
    {
        $fp = fopen($fileName, 'w');
        fwrite($fp, $this->exportRawData());
        fclose($fp);
        return $this;
    }

    /**
     * Split the header into an array
     *
     * @return array Header data
     */
    private function getHeaderAsArray()
    {
        return str_split($this->header);
    }

    /**
     * Set header data from array
     *
     * @param array $headerArray Header data
     * @return \de\peregrinus\progmatic\profile Profile object
     */
    private function setHeaderAsArray($headerArray)
    {
        $this->header = join('', $headerArray);
        return $this;
    }

    /**
     * Set a single byte value in the header array
     * @param int $offset Offset
     * @param char $value Value
     * @return \de\peregrinus\progmatic\profile Profile object
     */
    protected function setHeaderByte($offset, $value)
    {
        $header = $this->getHeaderAsArray();
        $header[$offset] = chr($value);
        $this->setHeaderAsArray($header);
        return $this;
    }

    /**
     * Enable a specific room profile
     * @param int $index Room profile number (0-9)
     * @return \de\peregrinus\progmatic\profile Profile object
     */
    public function enableRoomProfile($index)
    {
        $this->setHeaderByte(16 + $index, $index + 1);
        return $this;
    }

    /**
     * Disable a specific room profile
     * @param int $index Room profile number (0..9)
     * @return \de\peregrinus\progmatic\profile Profile object
     */
    public function disableRoomProfile($index)
    {
        $this->setHeaderByte(16 + $index, 0);
        return $this;
    }
}
