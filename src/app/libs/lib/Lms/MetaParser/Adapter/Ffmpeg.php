<?php

class Lms_MetaParser_Adapter_Ffmpeg implements Lms_MetaParser_Adapter_Interface
{
    
    /**
     * Convert  info getted with demuxer to standart view 
     *
     * @param object $ffmpegInstance
     * @param string $url
     * @param int $fileSize
     * @return array
     */
    public static function analyze($ffmpegInstance, $url, $fileSize)
    {
        $movie = $ffmpegInstance->getFfmpegMovie($url);
        $info = array();
        $info['playtime_seconds']                      = $movie->getDuration();
        $info['video']['streams'][0]['total_frames']   = $movie->getFrameCount();
        $info['video']['streams'][0]['frame_rate']     = $movie->getFrameRate();
        $info['video']['streams'][0]['resolution_y']   = $movie->getFrameHeight();
        $info['video']['streams'][0]['resolution_x']   = $movie->getFrameWidth();
        $info['bitrate']                               = $movie->getBitRate();
        $info['video']['streams'][0]['bitrate']        = $movie->getVideoBitRate();
        $info['audio']['streams'][0]['bitrate']        = $movie->getAudioBitRate();
        $info['audio']['streams'][0]['sample_rate']    = $movie->getAudioSampleRate();
        $info['video']['streams'][0]['codec']          = $movie->getVideoCodec();
        $info['audio']['streams'][0]['codec']          = $movie->getAudioCodec();
        $info['audio']['streams'][0]['channels']       = $movie->getAudioChannels();
        // Эмуляция свойств //
        $info['video']['streams'][0]['bitrate_mode']       = '';
        $info['video']['streams'][0]['total_frames']       = '';
        $info['video']['streams'][0]['fourcc']             = '';
        $info['video']['streams'][0]['pixel_aspect_ratio'] = '';
        $info['video']['streams'][0]['lossless']           = '';
        $info['video']['streams'][0]['bits_per_sample']    = '';
        $info['video']['streams'][0]['compression_ratio']  = '';
       
        $info['audio']['streams'][0]['codec'] = $info['audio']['codec'];
        $info['audio']['streams'][0]['channels'] = $info['audio']['channels'];
        $info['audio']['streams'][0]['sample_rate'] = $info['audio']['sample_rate'];
        $info['audio']['streams'][0]['bitrate'] = $info['audio']['bitrate'];
        $info['audio']['streams'][0]['dataformat'] = '';
        $info['audio']['streams'][0]['wformattag'] = '';
        $info['audio']['streams'][0]['lossless'] = '';
        $info['audio']['streams'][0]['bitrate_mode'] = '';
        $info['audio']['streams'][0]['compression_ratio'] = '';
       
        $info['file_size'] = $fileSize;
        $info['mime_type'] = '';
        $minutes = floor((int)$info['playtime_seconds'] / 60);
        $seconds = (int)$info['playtime_seconds'] - $minutes * 60;
        $seconds = $seconds < 10? '0' . $seconds : $seconds;
        $info['playtime_string'] = $minutes . ':' . $seconds;
        return $info;
   
    }
}