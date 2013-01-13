<?php

class Lms_Playlist_Modules_Pls implements Lms_Playlist_Modules_Interface {
   
    public static function generatePlaylist($files)
    {
        header("Content-type: video/pls"); 
        header('Content-Disposition: attachment; filename="playlist.pls"'); 
        echo "[playlist]";
        echo "\nnumberofentries=" . count($files);
        foreach ($files as $i => $file) {
            $path = $file['path'];
            $path = str_replace("\\", "/", $path);
            if ($path[0]=='/') {
                $path = "smb:" . $path;
            }
            echo "\nFile$i=$path";
        }
    }
}

