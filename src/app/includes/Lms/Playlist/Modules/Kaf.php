<?php

class Lms_Playlist_Modules_Kaf implements Lms_Playlist_Modules_Interface {
   
    public static function generatePlaylist($files)
    {
        header("Content-type: video/kaffeine"); 
        header('Content-Disposition: attachment; filename="playlist.kaffeine"'); 
        echo "<!DOCTYPE XMLPlaylist>\n";
        echo "<playlist client=\"kaffeine\" >\n";
        foreach ($files as $file) {
            $path = $file['path'];
            $path = str_replace("\\", "/", $path);
            if ($path[0]=='/') {
                $path = "smb:" . $path;
            }
            echo "\t<entry title=\"" . $file['name'] . "\" url=\"$path\" />\n";
        }
        echo "</playlist>";
    }
}

