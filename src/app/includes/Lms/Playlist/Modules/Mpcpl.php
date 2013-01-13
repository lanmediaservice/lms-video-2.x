<?php

class Lms_Playlist_Modules_Mpcpl implements Lms_Playlist_Modules_Interface {
   
    public static function generatePlaylist($files)
    {
        header("Content-type: video/mpcpl"); 
        header('Content-Disposition: attachment; filename="playlist.mpcpl"'); 
        echo "MPCPLAYLIST\r\n";
        foreach ($files as $i => $file) {
            if (Lms_Application::getConfig('download', 'mpcpl_convert_name_to_utf8')) {
                $file['path'] = Lms_Translate::translate('CP1251', 'UTF-8', $file['path']);
            }
            echo "$i,type,0\r\n$i,filename,{$file['path']}\r\n";
        }
    }
}

