<?php

class Lms_Playlist_Modules_Bsl implements Lms_Playlist_Modules_Interface {
   
    public static function generatePlaylist($files)
    {
        header("Content-type: video/bsl"); 
        header('Content-Disposition: attachment; filename="playlist.bsl"'); 
        foreach ($files as $file) {
            echo $file['path'] . "\r\n";
        }
    }
}

