<?php

class Lms_Playlist_Modules_Mp implements Lms_Playlist_Modules_Interface {
   
    public static function generatePlaylist($files)
    {
        header("Content-type: video/asx"); 
        header('Content-Disposition: attachment; filename="playlist.asx"'); 
        echo "<Asx Version = \"3.0\" >\r\n<Param Name = \"Name\" />\r\n\r\n";
        foreach ($files as $file) {
            echo "<Entry>\r\n\t<Title>{$file['name']}</Title>\r\n\t<Ref href = \"{$file['path']}\"/>\r\n</Entry>\r\n";
        }
        echo "</Asx>";
    }
}

