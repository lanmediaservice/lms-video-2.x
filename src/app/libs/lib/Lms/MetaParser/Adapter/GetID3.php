<?php

class Lms_MetaParser_Adapter_GetID3 implements Lms_MetaParser_Adapter_Interface
{
    /**
     * Convert  info getted with demuxer to standart view 
     *
     * @param object $getID3Instance
     * @param string $url
     * @param int $fileSize
     * @return array
     */
    public static function analyze($getID3Instance, $url, $fileSize)
    {
        
        $getID3Instance->Analyze($url);
        
        if (isset($getID3Instance->info['video']['streams'])) {
            $info['video']['streams'] =  array_values(($getID3Instance->info['video']['streams']));
            for ($i=0; $i < count($info['video']['streams']); $i++) {
                $info['video']['streams'][$i]['bitrate'] = $getID3Instance->info['video']['bitrate'];
                $info['video']['streams'][$i]['dataformat'] = $getID3Instance->info['video']['dataformat'];
                $info['video']['streams'][$i]['lossless']   = $getID3Instance->info['video']['lossless'];
                $info['video']['streams'][$i]['pixel_aspect_ratio'] = $getID3Instance->info['video']['pixel_aspect_ratio'];
            } 
        } else {
            $info['video']['streams'][0] =  $getID3Instance->info['video'];
        }
        $info['audio']['streams'] =  array_values($getID3Instance->info['audio']['streams']);
        $info['file_size'] = $fileSize; 
        $info['mime_type'] = $getID3Instance->info['mime_type']; 
        $info['playtime_seconds'] = $getID3Instance->info['playtime_seconds'];
        $info['bitrate'] = $getID3Instance->info['bitrate'];
        $info['playtime_string'] = $getID3Instance->info['playtime_string'];
        return $info;
        
    }
}
?>