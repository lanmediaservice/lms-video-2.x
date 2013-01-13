<?php
/**
 * LMS2
 *
 * @version $Id: Mplayer.php 413 2010-04-17 22:13:29Z macondos $
 * @copyright 2008
 */

/**
 * Mplayer
 *
 * Class for analyze mediafile, generating screenshots and other, on *nix-OS
 * mplayer can launch in gnu screen utility for generating
 *
 * @author Alex Tatulchenkov
 * @copyright Copyright (c) 2008
 * @version $Id: Mplayer.php 413 2010-04-17 22:13:29Z macondos $
 * @access public
 */
class Lms_ExternalBin_Mplayer extends  Lms_ExternalBin_Generic
{


    private $_locationMplayer; // path for launching mplayer
    private $_tmp; //path to temp dir

    function __construct($mplayerPath = null)
    {
        if ($mplayerPath !== null) {
            $this->setLocation($mplayerPath);
        }
    }

    /**
     * set path for launching mplayer
     *
     * @param unknown_type $path
     */
    public function setLocation($path)
    {
       $this->_locationMplayer = $path;
    }

    public function setTempPath($path)
    {
       $this->_tmp = $path;
    }


    /**
     * Mplayer::analyze()
     *
     * @return
     */
    function analyze($pathToFile, $audioTrackId = false)
    {
        $commandStr = escapeshellcmd($this->_locationMplayer);
        $commandStr .=  ' -frames 0 -vo null -ao null -msglevel identify=6 ';
        if ($audioTrackId!==false) {
            $commandStr .= " -aid $audioTrackId ";
        }
        $commandStr .= escapeshellarg($pathToFile);
        $lines = array();
        exec($commandStr, $lines);

        $info = array();
        $numberOfAudioTracks = 0;
        foreach ($lines as $line) {
            //echo $line;
            if (preg_match("/^(ID_.*?)=(.*?)$/", $line, $matches)) {
                if ($matches[1] == 'ID_AUDIO_ID') $numberOfAudioTracks++;
                $info[$matches[1]] = $matches[2];
            }
        }
        if (count($info) < 1) {
            throw new Lms_ExternalBin_Exception(
                "Cannot parse file: $pathToFile"
            );
        }
        $info['numberOfAudioTracks'] = $numberOfAudioTracks;
        return $info;
    }
    

    /**
     * Mplayer::generateScreenshots()
     *
     * @return
     */
    function getFrames($pathToFile, $playtime, $count = 8)
    {
        $currentPath = getcwd(); 
        chdir($this->_tmp);
        $files = array();
        $partTime = 0.8*$playtime/($count*2);
        for ($i=0; $i<$count*2; $i++) {
            $time = intval($partTime*$i + rand(1, $partTime-1));
            $cmd = escapeshellcmd($this->_locationMplayer);
            $cmd .=  " -nosound -vf screenshot " . escapeshellarg($pathToFile) . " -ss $time -frames 1 -vo jpeg";
            exec($cmd, $res, $ret);
            if ($ret!=0) {
                throw new Lms_Exception("Command $cmd return $ret");
            }
            $filename = $this->_tmp . '/frame-' . sprintf("%05s.jpg", $time);
            if (is_file("00000001.jpg")) {
                rename("00000001.jpg", $filename);
                $files[$filename] = filesize($filename);
            }
        }
        arsort($files);
        $frames = array();
        $i = 0;
        foreach ($files as $file => $size) {
            if ($i<$count) {
                $frames[] = $file;
            } else {
                unlink($file);
            }
            $i++;
        }
        /*
        $step = (int)($playtime/100);
        $segments = array();
        for ($i=1; $i<=100; $i++) {
            $filename = $this->_tmp . '/' . sprintf("%08s.jpg", $i);
            $segment = floor(($i-1)/$count);
            if (is_file($filename)) {
                $segments[$segment][$filename] = filesize($filename);
            }
        }
        $frames = array();
        foreach ($segments as $segment => $files) {
            arsort($files);
            reset($files);
            $frames[] = key($files);
            array_shift($files);
            foreach ($files as $file => $size) {
                unlink($file);
            }
        }
*/
        chdir($currentPath);
        
        return $frames;
    }

    /**
     * Mplayer::convert()
     *
     * @return
     */
    function convert()
    {
    }

    function start($str = false)
    {
        if (!$str) {
            $str = $this->_locationMplayer;
        }
        return ExternalProgram::start($str);
    }

}
