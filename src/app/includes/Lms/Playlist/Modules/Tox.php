<?php

class Lms_Playlist_Modules_Tox implements Lms_Playlist_Modules_Interface {
   
    public static function generatePlaylist($files)
    {
        header("Content-type: video/tox"); 
        header('Content-Disposition: attachment; filename="playlist.tox"'); 
        echo "# toxine playlist\n";
        foreach ($files as $file) {
            $path = $file['path'];
            $path = str_replace("\\", "/", $path);
            if ($path[0]=='/') {
                $path = "smb:" . $path;
            }
            echo "\nentry {\n";
            echo "\tidentifier = " . $file['name'] . ";\n";
            echo "\tmrl = $path;\n";
            echo "};\n";
        }
        echo "# END\n";
    }
}

