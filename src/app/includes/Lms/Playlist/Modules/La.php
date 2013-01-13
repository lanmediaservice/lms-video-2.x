<?php

class Lms_Playlist_Modules_La implements Lms_Playlist_Modules_Interface {
   
    public static function generatePlaylist($files)
    {
        header("Content-type: video/lap"); 
        header('Content-Disposition: attachment; filename="playlist.lap"'); 
        foreach ($files as $file) {
            echo $file['path'] . "\r\n>N " . $file['name'] . "\r\n\r\n";
        }
    }
}

