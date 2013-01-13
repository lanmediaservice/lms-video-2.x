<?php

class Lms_Playlist_Modules_Crp implements Lms_Playlist_Modules_Interface {
   
    public static function generatePlaylist($files)
    {
        header("Content-type: video/mls"); 
        header('Content-Disposition: attachment; filename="playlist.mls"'); 
        foreach ($files as $file) {
             echo "\"" . $file['path'] . "\"\r\n";
        }
    }
}

