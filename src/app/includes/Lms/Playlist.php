<?php

class Lms_Playlist {
    
    const PLAYER_LIGHT_ALLOY = 'la';
    const PLAYER_WINDOWS_MEDIA_PLAYER = 'mp';
    const PLAYER_MEDIA_PLAYER_CLASSIC = 'mpcpl';
    const PLAYER_CRYSTAL_PLAYER = 'crp';
    const PLAYER_BSPLAYER = 'bsl';
    const PLAYER_XINE = 'tox';
    const PLAYER_KAFFEINE = 'kaf';
    const PLAYER_WINAMP = 'pls';
    const PLAYER_VLC = 'xspf';
    
    public static function getModule($module)
    {
        $module = "Lms_Playlist_Modules_" . ucfirst(preg_replace('{\W}', '', strtolower($module)));
        if (!class_exists($module, true)) {
            throw new Lms_Exception("Error loading module $module");
        }
        return $module;
    }
    
    public static function generatePlaylist($files, $module)
    {
        $moduleClass = self::getModule($module);
        
        $filesArray = array();
        
        foreach ($files as $file) {
            $path = $file->getPath();
            foreach (Lms_Application::getConfig('download', 'masks') as $mask) {
                if (stripos($path, $mask['source'])!==false) {
                    $path = str_ireplace($mask['source'], $mask['smb'], $path);
                    break;
                }
            }
            if ($path[0]=='/') {
                $path = str_replace("/", "\\", $path);
            }
            
            $filesArray[] = array(
                'name' => $file->getName(),
                'path' => $path,
                'movie_id' => $file->getChilds('Movie')->getId()
            );
        }
        $moduleClass::generatePlaylist($filesArray);
    }
}

