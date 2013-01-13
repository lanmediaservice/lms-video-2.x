<?php

class Lms_MetaParser_Adapter_Mplayer implements Lms_MetaParser_Adapter_Interface
{
    
    /**
     * Convert  info getted with demuxer to standart view 
     *
     * @param object $mplayerInstance
     * @param string $url
     * @param int $fileSize
     * @return array
     */
    public static function analyze($mplayerInstance, $url, $fileSize)
    {
  
        $infoSource = $mplayerInstance->analyze($url);
        $bitrate = 0;
        //Video
        if (self::getVideoCodec($infoSource['ID_VIDEO_FORMAT'])) {
            $info['video']['streams'][0]['codec'] =  self::getVideoCodec($infoSource['ID_VIDEO_FORMAT']);
        } else { 
            $info['video']['streams'][0]['codec'] = $infoSource['ID_VIDEO_CODEC'];
        }
        $info['video']['streams'][0]['resolution_x'] = $infoSource['ID_VIDEO_WIDTH']; 
        $info['video']['streams'][0]['resolution_y'] = $infoSource['ID_VIDEO_HEIGHT']; 
        $info['video']['streams'][0]['frame_rate']   = $infoSource['ID_VIDEO_FPS'];
        $info['video']['streams'][0]['bitrate_mode'] = '';
        $info['video']['streams'][0]['total_frames'] = '';
        $info['video']['streams'][0]['fourcc'] = '';
        $info['video']['streams'][0]['pixel_aspect_ratio'] = $infoSource['ID_VIDEO_ASPECT'];
        $info['video']['streams'][0]['lossless'] = '';
        $info['video']['streams'][0]['bits_per_sample'] = '';
        $info['video']['streams'][0]['bitrate'] = $infoSource['ID_VIDEO_BITRATE'];
        $bitrate += $infoSource['ID_VIDEO_BITRATE'];
        $info['video']['streams'][0]['compression_ratio'] = '';
        $info['video']['streams'][0]['dataformat'] = $infoSource['ID_DEMUXER'];
        $info['video']['streams'][0]['name'] = isset($infoSource['ID_VID_0_NAME'])? $infoSource['ID_VID_0_NAME'] : null;
        $info['video']['streams'][0]['lang'] = isset($infoSource['ID_VID_0_LANG'])? $infoSource['ID_VID_0_LANG'] : null;
        
        		
        foreach ($infoSource as $key => $value) {
            if (preg_match("/^ID_AID_(\d+)_LANG$/", $key, $matches)) {
                $n = $matches[1];
                $info['audio']['streams'][$n]['lang'] = $value;
            }
            if (preg_match("/^ID_AID_(\d+)_NAME$/", $key, $matches)) {
                $n = $matches[1];
                $info['audio']['streams'][$n]['name'] = $value;
            }
        }

        //audio
        $numberOfAudioTracks = $infoSource['numberOfAudioTracks'];
        $info['audio']['streams'][0]['bitrate']     = $infoSource['ID_AUDIO_BITRATE'];
        $info['audio']['streams'][0]['sample_rate'] = $infoSource['ID_AUDIO_RATE'];
		$info['audio']['streams'][0]['channels']    = $infoSource['ID_AUDIO_NCH'];
		$info['audio']['streams'][0]['channelmode'] = '';
		
		if (@$infoSource['ID_AUDIO_NCH'] == '1') {
			$info['audio']['streams'][0]['channelmode'] = 'mono';
        } elseif (@$infoSource['ID_AUDIO_NCH'] == '2') {
            $info['audio']['streams'][0]['channelmode'] = 'stereo';
        }
        $info['audio']['streams'][0]['dataformat'] = '';
        $info['audio']['streams'][0]['wformattag'] = '';
        $info['audio']['streams'][0]['lossless'] = '';
        $info['audio']['streams'][0]['bitrate_mode'] = '';
        $info['audio']['streams'][0]['compression_ratio'] = '';
        $info['audio']['streams'][0]['codec'] = self::getAudioCodec($infoSource['ID_AUDIO_FORMAT']);
        //Если больше одной дорожки
        if ($numberOfAudioTracks > 1) {
            for ( $i = 1; $i < (int)$numberOfAudioTracks; $i++) {
                $infoSource = $mplayerInstance->analyze($url, $i);
                $info['audio']['streams'][$i]['bitrate']     = $infoSource['ID_AUDIO_BITRATE'];
                $bitrate += $infoSource['ID_AUDIO_BITRATE'];
                $info['audio']['streams'][$i]['sample_rate'] = $infoSource['ID_AUDIO_RATE'];
                $info['audio']['streams'][$i]['channels']    = $infoSource['ID_AUDIO_NCH'];
                $info['audio']['streams'][$i]['channelmode'] = '';
                if (@$infoSource['ID_AUDIO_NCH'] == '1') {
                    $info['audio']['streams'][$i]['channelmode'] = 'mono';
                } elseif (@$infoSource['ID_AUDIO_NCH'] == '2') {
                    $info['audio']['streams'][$i]['channelmode'] = 'stereo';
                }
                $info['audio']['streams'][$i]['dataformat'] = '';
                $info['audio']['streams'][$i]['wformattag'] = '';
                $info['audio']['streams'][$i]['lossless'] = '';
                $info['audio']['streams'][$i]['bitrate_mode'] = '';
                $info['audio']['streams'][$i]['compression_ratio'] = '';
                $info['audio']['streams'][$i]['codec'] = self::getAudioCodec($infoSource['ID_AUDIO_FORMAT']);
                
            }
        }
        //Common
        $info['playtime_seconds'] = (int) $infoSource['ID_LENGTH'];
        $info['file_size'] = $fileSize;
        $info['mime_type'] = '';
        $info['bitrate'] = $bitrate;
        $minutes = floor((int)$info['playtime_seconds'] / 60 );
        $seconds = (int)$info['playtime_seconds'] - $minutes * 60;
        $seconds = $seconds < 10? '0' . $seconds : $seconds;
        $info['playtime_string'] = $minutes . ':' . $seconds;
        
        return $info;
    }
    
    private static function getAudioCodec($format)
    {
        $relations = array(
            'MP4A' =>    'Advanced Audio Coding',
            '0x0000' =>    'Microsoft Unknown Wave Format',
            '0x0001' =>    'Pulse Code Modulation (PCM)',
            '0x0002' =>    'Microsoft ADPCM',
            '0x0003' => 'IEEE Float',
            '0x0004' =>    'Compaq Computer VSELP',
            '0x0005' => 'IBM CVSD',
            '0x0006' =>    'Microsoft A-Law',
            '0x0007' =>    'Microsoft mu-Law',
            '0x0008' =>    'Microsoft DTS',
            '0x0010' =>    'OKI ADPCM',
            '0x0011' =>    'Intel DVI/IMA ADPCM',
            '0x0012' =>    'Videologic MediaSpace ADPCM',
            '0x0013' =>    'Sierra Semiconductor ADPCM',
            '0x0014' =>    'Antex Electronics G.723 ADPCM',
            '0x0015' =>    'DSP Solutions DigiSTD',
            '0x0016' =>    'DSP Solutions DigiFIX',
            '0x0017' =>    'Dialogic OKI ADPCM',
            '0x0018' =>    'MediaVision ADPCM',
            '0x0019' =>    'Hewlett-Packard CU',
            '0x0020' =>    'Yamaha ADPCM',
            '0x0021' =>    'Speech Compression Sonarc',
            '0x0022' =>    'DSP Group TrueSpeech',
            '0x0023' =>    'Echo Speech EchoSC1',
            '0x0024' =>    'Audiofile AF36',
            '0x0025' =>    'Audio Processing Technology APTX',
            '0x0026' =>    'AudioFile AF10',
            '0x0027' =>    'Prosody 1612',
            '0x0028' =>    'LRC',
            '0x0030' =>    'Dolby AC2',
            '0x0031' =>    'Microsoft GSM 6.10',
            '0x0032' =>    'MSNAudio',
            '0x0033' =>    'Antex Electronics ADPCME',
            '0x0034' =>    'Control Resources VQLPC',
            '0x0035' =>    'DSP Solutions DigiREAL',
            '0x0036' =>    'DSP Solutions DigiADPCM',
            '0x0037' =>    'Control Resources CR10',
            '0x0038' =>    'Natural MicroSystems VBXADPCM',
            '0x0039' =>    'Crystal Semiconductor IMA ADPCM',
            '0x003A' =>    'EchoSC3',
            '0x003B' =>    'Rockwell ADPCM',
            '0x003C' =>    'Rockwell Digit LK',
            '0x003D' =>    'Xebec',
            '0x0040' =>    'Antex Electronics G.721 ADPCM',
            '0x0041' =>    'G.728 CELP',
            '0x0042' =>    'MSG723',
            '0x0050' =>    'MPEG Layer-2 or Layer-1',
            '0x0052' =>    'RT24',
            '0x0053' =>    'PAC',
            '0x0055' =>    'MPEG Layer-3',
            '0x0059' =>    'Lucent G.723',
            '0x0060' =>    'Cirrus',
            '0x0061' =>    'ESPCM',
            '0x0062' =>    'Voxware',
            '0x0063' =>    'Canopus Atrac',
            '0x0064' =>    'G.726 ADPCM',
            '0x0065' =>    'G.722 ADPCM',
            '0x0066' =>    'DSAT',
            '0x0067' =>    'DSAT Display',
            '0x0069' =>    'Voxware Byte Aligned',
            '0x0070' =>    'Voxware AC8',
            '0x0071' =>    'Voxware AC10',
            '0x0072' =>    'Voxware AC16',
            '0x0073' =>    'Voxware AC20',
            '0x0074' =>    'Voxware MetaVoice',
            '0x0075' =>    'Voxware MetaSound',
            '0x0076' =>    'Voxware RT29HW',
            '0x0077' =>    'Voxware VR12',
            '0x0078' =>    'Voxware VR18',
            '0x0079' =>    'Voxware TQ40',
            '0x0080' =>    'Softsound',
            '0x0081' =>    'Voxware TQ60',
            '0x0082' =>    'MSRT24',
            '0x0083' =>    'G.729A',
            '0x0084' =>    'MVI MV12',
            '0x0085' =>    'DF G.726',
            '0x0086' =>    'DF GSM610',
            '0x0088' =>    'ISIAudio',
            '0x0089' =>    'Onlive',
            '0x0091' =>    'SBC24',
            '0x0092' =>    'Dolby AC3 SPDIF',
            '0x0093' =>    'MediaSonic G.723',
            '0x0094' =>    'Aculab PLC    Prosody 8kbps',
            '0x0097' =>    'ZyXEL ADPCM',
            '0x0098' =>    'Philips LPCBB',
            '0x0099' =>    'Packed',
            '0x00FF' =>    'AAC',
            '0x0100' =>    'Rhetorex ADPCM',
            '0x0101' =>    'IBM mu-law',
            '0x0102' =>    'IBM A-law',
            '0x0103' =>    'IBM AVC Adaptive Differential Pulse Code Modulation (ADPCM)',
            '0x0111' =>    'Vivo G.723',
            '0x0112' =>    'Vivo Siren',
            '0x0123' =>    'Digital G.723',
            '0x0125' =>    'Sanyo LD ADPCM',
            '0x0130' =>    'Sipro Lab Telecom ACELP NET',
            '0x0131' =>    'Sipro Lab Telecom ACELP 4800',
            '0x0132' =>    'Sipro Lab Telecom ACELP 8V3',
            '0x0133' => 'Sipro Lab Telecom G.729',
            '0x0134' =>    'Sipro Lab Telecom G.729A',
            '0x0135' =>    'Sipro Lab Telecom Kelvin',
            '0x0140' =>    'Windows Media Video V8',
            '0x0150' =>    'Qualcomm PureVoice',
            '0x0151' => 'Qualcomm HalfRate',
            '0x0155' =>    'Ring Zero Systems TUB GSM',
            '0x0160' =>    'Microsoft Audio 1',
            '0x0161' =>    'Windows Media Audio V7 / V8 / V9',
            '0x0162' =>    'Windows Media Audio Professional V9',
            '0x0163' => 'Windows Media Audio Lossless V9',
            '0x0200' =>    'Creative Labs ADPCM',
            '0x0202' =>    'Creative Labs Fastspeech8',
            '0x0203' =>    'Creative Labs Fastspeech10',
            '0x0210' =>    'UHER Informatic GmbH ADPCM',
            '0x0220' =>    'Quarterdeck',
            '0x0230' =>    'I-link Worldwide VC',
            '0x0240' =>    'Aureal RAW Sport',
            '0x0250' =>    'Interactive Products HSX',
            '0x0251' =>    'Interactive Products RPELP',
            '0x0260' =>    'Consistent Software CS2',
            '0x0270' =>    'Sony SCX',
            '0x0300' =>    'Fujitsu FM Towns Snd',
            '0x0400' =>    'BTV Digital',
            '0x0401' =>    'Intel Music Coder',
            '0x0450' =>    'QDesign Music',
            '0x0680' =>    'VME VMPCM',
            '0x0681' =>    'AT&T Labs TPC',
            '0x08AE' =>    'ClearJump LiteWave',
            '0x1000' =>    'Olivetti GSM',
            '0x1001' =>    'Olivetti ADPCM',
            '0x1002' =>    'Olivetti CELP',
            '0x1003' =>    'Olivetti SBC',
            '0x1004' =>    'Olivetti OPR',
            '0x1100' =>    'Lernout & Hauspie Codec (0x1100)',
            '0x1101' =>    'Lernout & Hauspie CELP Codec (0x1101)',
            '0x1102' =>    'Lernout & Hauspie SBC Codec (0x1102)',
            '0x1103' =>    'Lernout & Hauspie SBC Codec (0x1103)',
            '0x1104' =>    'Lernout & Hauspie SBC Codec (0x1104)',
            '0x1400' =>    'Norris',
            '0x1401' =>    'AT&T ISIAudio',
            '0x1500' =>    'Soundspace Music Compression',
            '0x181C' =>    'VoxWare RT24 Speech',
            '0x1FC4' =>    'NCT Soft ALF2CD (www.nctsoft.com)',
            '0x2000' =>    'Dolby AC3',
            '0x2001' =>    'Dolby DTS',
            '0x2002' =>    'WAVE_FORMAT_14_4',
            '0x2003' =>    'WAVE_FORMAT_28_8',
            '0x2004' =>    'WAVE_FORMAT_COOK',
            '0x2005' =>    'WAVE_FORMAT_DNET',
            '0x566F' =>    'Vorbis',
            '0x674F' =>    'Ogg Vorbis 1',
            '0x6750' =>    'Ogg Vorbis 2',
            '0x6751' =>    'Ogg Vorbis 3',
            '0x676F' =>    'Ogg Vorbis 1+',
            '0x6770' =>    'Ogg Vorbis 2+',
            '0x6771' =>    'Ogg Vorbis 3+',
            '0x7A21' =>    'GSM-AMR (CBR, no SID)',
            '0x7A22' =>    'GSM-AMR (VBR, including SID)',
            '0xFFFE' =>    'WAVE_FORMAT_EXTENSIBLE',
            '0xFFFF' =>    'WAVE_FORMAT_DEVELOPMENT'
        );
        if (isset($relations[$format])) {
            return $relations[$format];
        }
        if (is_numeric($format)) {
            $key = '0x' . str_pad(strtoupper(dechex($format)), 4, '0', STR_PAD_LEFT);
            if (isset($relations[$key])) {
                return $relations[$key];
            }
        }
        return $format;
    }
        
    private static function getVideoCodec($format)
    {
        $relations = array(
            'swot' =>    'http://developer.apple.com/qa/snd/snd07.html',
            '____' =>    'No Codec (____)',
            '_BIT' =>    'BI_BITFIELDS (Raw RGB)',
            '_JPG' =>    'JPEG compressed',
            '_PNG' =>    'PNG compressed W3C/ISO/IEC (RFC-2083)',
            '_RAW' =>    'Full Frames (Uncompressed)',
            '_RGB' =>    'Raw RGB Bitmap',
            '_RL4' =>    'RLE 4bpp RGB',
            '_RL8' =>    'RLE 8bpp RGB',
            '3IV1' =>    '3ivx MPEG-4 v1',
            '3IV2' =>    '3ivx MPEG-4 v2',
            '3IVX' =>    '3ivx MPEG-4',
            'AASC' =>    'Autodesk Animator',
            'ABYR' =>    'Kensington ?ABYR?',
            'AEMI' =>    'Array Microsystems VideoONE MPEG1-I Capture',
            'AFLC' =>    'Autodesk Animator FLC',
            'AFLI' =>     'Autodesk Animator FLI',
            'AMPG' =>    'Array Microsystems VideoONE MPEG',
            'ANIM' =>    'Intel RDX (ANIM)',
            'AP41' =>    'AngelPotion Definitive',
            'ASV1' =>    'Asus Video v1',
            'ASV2' =>    'Asus Video v2',
            'ASVX' =>    'Asus Video 2.0 (audio)',
            'AUR2' =>    'AuraVision Aura 2 Codec - YUV 4:2:2',
            'AURA' =>    'AuraVision Aura 1 Codec - YUV 4:1:1',
            'AVDJ' =>    'Independent JPEG Group\'s codec (AVDJ)',
            'AVRN' =>    'Independent JPEG Group\'s codec (AVRN)',
            'AYUV' =>    '4:4:4 YUV (AYUV)',
            'AZPR' =>    'Quicktime Apple Video (AZPR)',
            'BGR'  =>     'Raw RGB32',
            'BLZ0' =>    'Blizzard DivX MPEG-4',
            'BTVC' =>    'Conexant Composite Video',
            'BINK' =>    'RAD Game Tools Bink Video',
            'BT20' =>    'Conexant Prosumer Video',
            'BTCV' =>    'Conexant Composite Video Codec',
            'BW10' =>    'Data Translation Broadway MPEG Capture',
            'CC12' =>    'Intel YUV12',
            'CDVC' =>    'Canopus DV',
            'CFCC' =>    'Digital Processing Systems DPS Perception',
            'CGDI' =>    'Microsoft Office 97 Camcorder Video',
            'CHAM' =>    'Winnov Caviara Champagne',
            'CJPG' =>    'Creative WebCam JPEG',
            'CLJR' =>    'Cirrus Logic YUV 4:1:1',
            'CMYK' =>    'Common Data Format in Printing (Colorgraph)',
            'CPLA' =>    'Weitek 4:2:0 YUV Planar',
            'CRAM' =>    'Microsoft Video 1 (CRAM)',
            'cvid' =>    'Radius Cinepak',
            'CVID' =>    'Radius Cinepak',
            'CWLT' =>    'Microsoft Color WLT DIB',
            'CYUV' =>    'Creative Labs YUV',
            'CYUY' =>    'ATI YUV',
            'D261' =>    'H.261',
            'D263' =>    'H.263',
            'DIB'  =>     'Device Independent Bitmap',
            'DIV1' =>    'FFmpeg OpenDivX',
            'DIV2' =>    'Microsoft MPEG-4 v1/v2',
            'DIV3' =>    'DivX ;-) MPEG-4 v3.x Low-Motion',
            'DIV4' =>    'DivX ;-) MPEG-4 v3.x Fast-Motion',
            'DIV5' =>   'DivX MPEG-4 v5.x',
            'DIV6' =>    'DivX ;-) (MS MPEG-4 v3.x)',
            'DIVX' =>    'DivX MPEG-4 v4 (OpenDivX / Project Mayo)',
            'divx' =>    'DivX MPEG-4',
            'DMB1' =>    'Matrox Rainbow Runner hardware MJPEG',
            'DMB2' =>    'Paradigm MJPEG',
            'DSVD' =>    '?DSVD?',
            'DUCK' =>    'Duck TrueMotion 1.0',
            'DPS0' =>    'DPS/Leitch Reality Motion JPEG',
            'DPSC' =>    'DPS/Leitch PAR Motion JPEG',
            'DV25' =>    'Matrox DVCPRO codec',
            'DV50' =>    'Matrox DVCPRO50 codec',
            'DVC'  =>     'IEC 61834 and SMPTE 314M (DVC/DV Video)',
            'DVCP' =>    'IEC 61834 and SMPTE 314M (DVC/DV Video)',
            'DVHD' =>    'IEC Standard DV 1125 lines @ 30fps / 1250 lines @ 25fps',
            'DVMA' =>    'Darim Vision DVMPEG (dummy for MPEG compressor) (www.darvision.com)',
            'DVSL' =>    'IEC Standard DV compressed in SD (SDL)',
            'DVAN' =>    '?DVAN?',
            'DVE2' =>   'InSoft DVE-2 Videoconferencing',
            'dvsd' =>    'IEC 61834 and SMPTE 314M DVC/DV Video',
            'DVSD' =>    'IEC 61834 and SMPTE 314M DVC/DV Video',
            'DVX1' =>    'Lucent DVX1000SP Video Decoder',
            'DVX2' =>    'Lucent DVX2000S Video Decoder',
            'DVX3' =>    'Lucent DVX3000S Video Decoder',
            'DX50' =>    'DivX v5',
            'DXT1' =>    'Microsoft DirectX Compressed Texture (DXT1)',
            'DXT2' =>    'Microsoft DirectX Compressed Texture (DXT2)',
            'DXT3' =>    'Microsoft DirectX Compressed Texture (DXT3)',
            'DXT4' =>    'Microsoft DirectX Compressed Texture (DXT4)',
            'DXT5' =>    'Microsoft DirectX Compressed Texture (DXT5)',
            'DXTC' =>    'Microsoft DirectX Compressed Texture (DXTC)',
            'DXTn' =>    'Microsoft DirectX Compressed Texture (DXTn)',
            'EM2V' =>    'Etymonix MPEG-2 I-frame (www.etymonix.com)',
            'EKQ0' =>    'Elsa ?EKQ0?',
            'ELK0' =>    'Elsa ?ELK0?',
            'ESCP' =>    'Eidos Escape',
            'ETV1' =>    'eTreppid Video ETV1',
            'ETV2' =>    'eTreppid Video ETV2',
            'ETVC' =>    'eTreppid Video ETVC',
            'FLIC' =>    'Autodesk FLI/FLC Animation',
            'FRWT' =>    'Darim Vision Forward Motion JPEG (www.darvision.com)',
            'FRWU' =>    'Darim Vision Forward Uncompressed (www.darvision.com)',
            'FLJP' =>    'D-Vision Field Encoded Motion JPEG',
            'FRWA' =>    'SoftLab-Nsk Forward Motion JPEG w/ alpha channel',
            'FRWD' =>    'SoftLab-Nsk Forward Motion JPEG',
            'FVF1' =>    'Iterated Systems Fractal Video Frame',
            'GLZW' =>    'Motion LZW (gabest@freemail.hu)',
            'GPEG' =>    'Motion JPEG (gabest@freemail.hu)',
            'GWLT' =>    'Microsoft Greyscale WLT DIB',
            'H260' =>    'Intel ITU H.260 Videoconferencing',
            'H261' =>    'Intel ITU H.261 Videoconferencing',
            'H262' =>    'Intel ITU H.262 Videoconferencing',
            'H263' =>    'Intel ITU H.263 Videoconferencing',
            'H264' =>    'Intel ITU H.264 Videoconferencing',
            'H265' =>    'Intel ITU H.265 Videoconferencing',
            'H266' =>    'Intel ITU H.266 Videoconferencing',
            'H267' =>    'Intel ITU H.267 Videoconferencing',
            'H268' =>    'Intel ITU H.268 Videoconferencing',
            'H269' =>    'Intel ITU H.269 Videoconferencing',
            'HFYU' =>    'Huffman Lossless Codec',
            'HMCR' =>    'Rendition Motion Compensation Format (HMCR)',
            'HMRR' =>    'Rendition Motion Compensation Format (HMRR)',
            'I263' =>    'FFmpeg I263 decoder',
            'IF09' =>    'Indeo YVU9 ("YVU9 with additional delta-frame info after the U plane")',
            'IUYV' =>    'Interlaced version of UYVY (www.leadtools.com)',
            'IY41' =>    'Interlaced version of Y41P (www.leadtools.com)',
            'IYU1' =>    '12 bit format used in mode 2 of the IEEE 1394 Digital Camera 1.04 spec    IEEE standard',
            'IYU2' =>    '24 bit format used in mode 2 of the IEEE 1394 Digital Camera 1.04 spec    IEEE standard',
            'IYUV' =>    'Planar YUV format (8-bpp Y plane, followed by 8-bpp 2�2 U and V planes)',
            'i263' =>    'Intel ITU H.263 Videoconferencing (i263)',
            'I420' =>    'Intel Indeo 4',
            'IAN'  =>     'Intel Indeo 4 (RDX)',
            'ICLB' =>    'InSoft CellB Videoconferencing',
            'IGOR' =>    'Power DVD',
            'IJPG' =>    'Intergraph JPEG',
            'ILVC' =>    'Intel Layered Video',
            'ILVR' =>    'ITU-T H.263+',
            'IPDV' =>    'I-O Data Device Giga AVI DV Codec',
            'IR21' =>    'Intel Indeo 2.1',
            'IRAW' =>    'Intel YUV Uncompressed',
            'IV30' =>    'Intel Indeo 3.0',
            'IV31' =>    'Intel Indeo 3.1',
            'IV32' =>    'Ligos Indeo 3.2',
            'IV33' =>    'Ligos Indeo 3.3',
            'IV34' =>    'Ligos Indeo 3.4',
            'IV35' =>    'Ligos Indeo 3.5',
            'IV36' =>    'Ligos Indeo 3.6',
            'IV37' =>    'Ligos Indeo 3.7',
            'IV38' =>    'Ligos Indeo 3.8',
            'IV39' =>    'Ligos Indeo 3.9',
            'IV40' =>    'Ligos Indeo Interactive 4.0',
            'IV41' =>    'Ligos Indeo Interactive 4.1',
            'IV42' =>    'Ligos Indeo Interactive 4.2',
            'IV43' =>    'Ligos Indeo Interactive 4.3',
            'IV44' =>    'Ligos Indeo Interactive 4.4',
            'IV45' =>    'Ligos Indeo Interactive 4.5',
            'IV46' =>    'Ligos Indeo Interactive 4.6',
            'IV47' =>    'Ligos Indeo Interactive 4.7',
            'IV48' =>    'Ligos Indeo Interactive 4.8',
            'IV49' =>    'Ligos Indeo Interactive 4.9',
            'IV50' =>    'Ligos Indeo Interactive 5.0',
            'JBYR' =>    'Kensington ?JBYR?',
            'JPEG' =>    'Still Image JPEG DIB',
            'JPGL' =>    'Pegasus Lossless Motion JPEG',
            'KMVC' =>    'Team17 Software Karl Morton\'s Video Codec',
            'LSVM' =>    'Vianet Lighting Strike Vmail (Streaming) (www.vianet.com)',
            'LEAD' =>    'LEAD Video Codec',
            'Ljpg' =>    'LEAD MJPEG Codec',
            'MDVD' =>    'Alex MicroDVD Video (hacked MS MPEG-4) (www.tiasoft.de)',
            'MJPA' =>    'Morgan Motion JPEG (MJPA) (www.morgan-multimedia.com)',
            'MJPB' =>    'Morgan Motion JPEG (MJPB) (www.morgan-multimedia.com)',
            'MP42' =>    'Microsoft S-Mpeg 4 version 2 (MP42)',
            'MP43' =>    'Microsoft S-Mpeg 4 version 3 (MP43)',
            'MP4S' =>    'Microsoft S-Mpeg 4 version 3 (MP4S)',
            'MP4V' =>    'MPEG-4',
            'MPG1' =>    'MPEG 1/2',
            'MPG2' =>    'MPEG 1/2',
            'MPG3' =>    'FFmpeg DivX ;-) (MS MPEG-4 v3)',
            'MPG4' =>    'Microsoft MPEG-4',
            'MPGI' =>    'Sigma Designs MPEG',
            'MPNG' =>    'PNG images decoder',
            'MSS1' =>    'Microsoft Windows Screen Video',
            'MSZH' =>   'LCL (Lossless Codec Library) (www.geocities.co.jp/Playtown-Denei/2837/LRC.htm)',
            'M261' =>    'Microsoft H.261',
            'M263' =>    'Microsoft H.263',
            'M4S2' =>    'Microsoft Fully Compliant MPEG-4 v2 simple profile (M4S2)',
            'm4s2' =>    'Microsoft Fully Compliant MPEG-4 v2 simple profile (m4s2)',
            'MC12' =>    'ATI Motion Compensation Format (MC12)',
            'MCAM' =>    'ATI Motion Compensation Format (MCAM)',
            'MJ2C' =>   'Morgan Multimedia Motion JPEG2000',
            'mJPG' =>    'IBM Motion JPEG w/ Huffman Tables',
            'MJPG' =>    'Microsoft Motion JPEG DIB',
            'MP42' =>    'Microsoft MPEG-4 (low-motion)',
            'MP43' =>    'Microsoft MPEG-4 (fast-motion)',
            'MP4S' =>    'Microsoft MPEG-4 (MP4S)',
            'mp4s' =>    'Microsoft MPEG-4 (mp4s)',
            'MPEG' =>    'Chromatic Research MPEG-1 Video I-Frame',
            'MPG4' =>    'Microsoft MPEG-4 Video High Speed Compressor',
            'MPGI' =>    'Sigma Designs MPEG',
            'MRCA' =>    'FAST Multimedia Martin Regen Codec',
            'MRLE' =>    'Microsoft Run Length Encoding',
            'MSVC' =>    'Microsoft Video 1',
            'MTX1' =>    'Matrox ?MTX1?',
            'MTX2' =>    'Matrox ?MTX2?',
            'MTX3' =>    'Matrox ?MTX3?',
            'MTX4' =>    'Matrox ?MTX4?',
            'MTX5' =>    'Matrox ?MTX5?',
            'MTX6' =>    'Matrox ?MTX6?',
            'MTX7' =>    'Matrox ?MTX7?',
            'MTX8' =>    'Matrox ?MTX8?',
            'MTX9' =>    'Matrox ?MTX9?',
            'MV12' =>    'Motion Pixels Codec (old)',
            'MWV1' =>    'Aware Motion Wavelets',
            'nAVI' =>    'SMR Codec (hack of Microsoft MPEG-4) (IRC #shadowrealm)',
            'NT00' =>    'NewTek LightWave HDTV YUV w/ Alpha (www.newtek.com)',
            'NUV1' =>    'NuppelVideo',
            'NTN1' =>    'Nogatech Video Compression 1',
            'NVS0' =>    'nVidia GeForce Texture (NVS0)',
            'NVS1' =>    'nVidia GeForce Texture (NVS1)',
            'NVS2' =>    'nVidia GeForce Texture (NVS2)',
            'NVS3' =>    'nVidia GeForce Texture (NVS3)',
            'NVS4' =>    'nVidia GeForce Texture (NVS4)',
            'NVS5' =>    'nVidia GeForce Texture (NVS5)',
            'NVT0' =>    'nVidia GeForce Texture (NVT0)',
            'NVT1' =>    'nVidia GeForce Texture (NVT1)',
            'NVT2' =>    'nVidia GeForce Texture (NVT2)',
            'NVT3' =>    'nVidia GeForce Texture (NVT3)',
            'NVT4' =>    'nVidia GeForce Texture (NVT4)',
            'NVT5' =>    'nVidia GeForce Texture (NVT5)',
            'PIXL' =>    'MiroXL, Pinnacle PCTV',
            'PDVC' =>    'I-O Data Device Digital Video Capture DV codec',
            'PGVV' =>    'Radius Video Vision',
            'PHMO' =>    'IBM Photomotion',
            'PIM1' =>    'MPEG Realtime (Pinnacle Cards)',
            'PIM2' =>    'Pegasus Imaging ?PIM2?',
            'PIMJ' =>    'Pegasus Imaging Lossless JPEG',
            'PVEZ' =>    'Horizons Technology PowerEZ',
            'PVMM' =>    'PacketVideo Corporation MPEG-4',
            'PVW2' =>    'Pegasus Imaging Wavelet Compression',
            'Q1.0' =>    'Q-Team\'s QPEG 1.0 (www.q-team.de)',
            'Q1.1' =>    'Q-Team\'s QPEG 1.1 (www.q-team.de)',
            'QPEG' =>    'Q-Team QPEG 1.0',
            'qpeq' =>    'Q-Team QPEG 1.1',
            'RGB'  =>     'Raw BGR32',
            'RGBA' =>    'Raw RGB w/ Alpha',
            'RMP4' =>    'REALmagic MPEG-4 (unauthorized XVID copy) (www.sigmadesigns.com)',
            'ROQV' =>    'Id RoQ File Video Decoder',
            'RPZA' =>    'Quicktime Apple Video (RPZA)',
            'RUD0' =>    'Rududu video codec (http://rududu.ifrance.com/rududu/)',
            'RV10' =>    'RealVideo 1.0 (aka RealVideo 5.0)',
            'RV13' =>    'RealVideo 1.0 (RV13)',
            'RV20' =>    'RealVideo G2',
            'RV30' =>    'RealVideo 8',
            'RV40' =>    'RealVideo 9',
            'RGBT' =>    'Raw RGB w/ Transparency',
            'RLE'  =>     'Microsoft Run Length Encoder',
            'RLE4' =>   'Run Length Encoded (4bpp, 16-color)',
            'RLE8' =>    'Run Length Encoded (8bpp, 256-color)',
            'RT21' =>    'Intel Indeo RealTime Video 2.1',
            'rv20' =>    'RealVideo G2',
            'rv30' =>    'RealVideo 8',
            'RVX'  =>     'Intel RDX (RVX )',
            'SMC'  =>     'Apple Graphics (SMC )',
            'SP54' =>    'Logitech Sunplus Sp54 Codec for Mustek GSmart Mini 2',
            'SPIG' =>    'Radius Spigot',
            'SVQ3' =>    'Sorenson Video 3 (Apple Quicktime 5)',
            's422' =>    'Tekram VideoCap C210 YUV 4:2:2',
            'SDCC' =>    'Sun Communication Digital Camera Codec',
            'SFMC' =>    'CrystalNet Surface Fitting Method',
            'SMSC' =>    'Radius SMSC',
            'SMSD' =>    'Radius SMSD',
            'smsv' =>    'WorldConnect Wavelet Video',
            'SPIG' =>    'Radius Spigot',
            'SPLC' =>    'Splash Studios ACM Audio Codec (www.splashstudios.net)',
            'SQZ2' =>    'Microsoft VXTreme Video Codec V2',
            'STVA' =>    'ST Microelectronics CMOS Imager Data (Bayer)',
            'STVB' =>    'ST Microelectronics CMOS Imager Data (Nudged Bayer)',
            'STVC' =>    'ST Microelectronics CMOS Imager Data (Bunched)',
            'STVX' =>    'ST Microelectronics CMOS Imager Data (Extended CODEC Data Format)',
            'STVY' =>    'ST Microelectronics CMOS Imager Data (Extended CODEC Data Format with Correction Data)',
            'SV10' =>    'Sorenson Video R1',
            'SVQ1' =>    'Sorenson Video',
            'T420' =>    'Toshiba YUV 4:2:0',
            'TM2A' =>    'Duck TrueMotion Archiver 2.0 (www.duck.com)',
            'TVJP' =>    'Pinnacle/Truevision Targa 2000 board (TVJP)',
            'TVMJ' =>    'Pinnacle/Truevision Targa 2000 board (TVMJ)',
            'TY0N' =>    'Tecomac Low-Bit Rate Codec (www.tecomac.com)',
            'TY2C' =>    'Trident Decompression Driver',
            'TLMS' =>    'TeraLogic Motion Intraframe Codec (TLMS)',
            'TLST' =>    'TeraLogic Motion Intraframe Codec (TLST)',
            'TM20' =>    'Duck TrueMotion 2.0',
            'TM2X' =>    'Duck TrueMotion 2X',
            'TMIC' =>    'TeraLogic Motion Intraframe Codec (TMIC)',
            'TMOT' =>    'Horizons Technology TrueMotion S',
            'tmot' =>    'Horizons TrueMotion Video Compression',
            'TR20' =>    'Duck TrueMotion RealTime 2.0',
            'TSCC' =>    'TechSmith Screen Capture Codec',
            'TV10' =>    'Tecomac Low-Bit Rate Codec',
            'TY2N' =>    'Trident ?TY2N?',
            'U263' =>    'UB Video H.263/H.263+/H.263++ Decoder',
            'UMP4' =>    'UB Video MPEG 4 (www.ubvideo.com)',
            'UYNV' =>    'Nvidia UYVY packed 4:2:2',
            'UYVP' =>    'Evans & Sutherland YCbCr 4:2:2 extended precision',
            'UCOD' =>    'eMajix.com ClearVideo',
            'ULTI' =>    'IBM Ultimotion',
            'UYVY' =>    'UYVY packed 4:2:2',
            'V261' =>    'Lucent VX2000S',
            'VIFP' =>    'VFAPI Reader Codec (www.yks.ne.jp/~hori/)',
            'VIV1' =>    'FFmpeg H263+ decoder',
            'VIV2' =>    'Vivo H.263',
            'VQC2' =>    'Vector-quantised codec 2 (research) http://eprints.ecs.soton.ac.uk/archive/00001310/01/VTC97-js.pdf)',
            'VTLP' =>    'Alaris VideoGramPiX',
            'VYU9' =>    'ATI YUV (VYU9)',
            'VYUY' =>    'ATI YUV (VYUY)',
            'V261' =>    'Lucent VX2000S',
            'V422' =>    'Vitec Multimedia 24-bit YUV 4:2:2 Format',
            'V655' =>    'Vitec Multimedia 16-bit YUV 4:2:2 Format',
            'VCR1' =>    'ATI Video Codec 1',
            'VCR2' =>    'ATI Video Codec 2',
            'VCR3' =>    'ATI VCR 3.0',
            'VCR4' =>    'ATI VCR 4.0',
            'VCR5' =>    'ATI VCR 5.0',
            'VCR6' =>    'ATI VCR 6.0',
            'VCR7' =>    'ATI VCR 7.0',
            'VCR8' =>    'ATI VCR 8.0',
            'VCR9' =>    'ATI VCR 9.0',
            'VDCT' =>    'Vitec Multimedia Video Maker Pro DIB',
            'VDOM' =>    'VDOnet VDOWave',
            'VDOW' =>    'VDOnet VDOLive (H.263)',
            'VDTZ' =>    'Darim Vison VideoTizer YUV',
            'VGPX' =>    'Alaris VideoGramPiX',
            'VIDS' =>    'Vitec Multimedia YUV 4:2:2 CCIR 601 for V422',
            'VIVO' =>    'Vivo H.263 v2.00',
            'vivo' =>    'Vivo H.263',
            'VIXL' =>    'Miro/Pinnacle Video XL',
            'VLV1' =>    'VideoLogic/PURE Digital Videologic Capture',
            'VP6F' =>    'TrueMotion VP6',
            'VP30' =>    'On2 VP3.0',
            'VP31' =>    'On2 VP3.1',
            'VX1K' =>    'Lucent VX1000S Video Codec',
            'VX2K' =>    'Lucent VX2000S Video Codec',
            'VXSP' =>    'Lucent VX1000SP Video Codec',
            'WBVC' =>    'Winbond W9960',
            'WHAM' =>    'Microsoft Video 1 (WHAM)',
            'WINX' =>    'Winnov Software Compression',
            'WJPG' =>    'AverMedia Winbond JPEG',
            'WMV1' =>    'Windows Media Video V7',
            'WMV2' =>    'Windows Media Video V8',
            'WMV3' =>    'Windows Media Video V9',
            'WNV1' =>    'Winnov Hardware Compression',
            'XYZP' =>    'Extended PAL format XYZ palette (www.riff.org)',
            'x263' =>    'Xirlink H.263',
            'XLV0' =>    'NetXL Video Decoder',
            'XMPG' =>    'Xing MPEG (I-Frame only)',
            'XVID' =>    'XviD MPEG-4 (www.xvid.org)',
            'XXAN' =>    '?XXAN?',
            'YU92' =>    'Intel YUV (YU92)',
            'YUNV' =>    'Nvidia Uncompressed YUV 4:2:2',
            'YUVP' =>    'Extended PAL format YUV palette (www.riff.org)',
            'Y211' =>    'YUV 2:1:1 Packed',
            'Y411' =>    'YUV 4:1:1 Packed',
            'Y41B' =>    'Weitek YUV 4:1:1 Planar',
            'Y41P' =>    'Brooktree PC1 YUV 4:1:1 Packed',
            'Y41T' =>    'Brooktree PC1 YUV 4:1:1 with transparency',
            'Y42B' =>    'Weitek YUV 4:2:2 Planar',
            'Y42T' =>    'Brooktree UYUV 4:2:2 with transparency',
            'Y422' =>    'ADS Technologies Copy of UYVY used in Pyro WebCam firewire camera',
            'Y800' =>    'Simple, single Y plane for monochrome images',
            'Y8'   =>   'Grayscale video',
            'YC12' =>    'Intel YUV 12 codec',
            'YUV8' =>    'Winnov Caviar YUV8',
            'YUV9' =>    'Intel YUV9',
            'YUY2' =>    'Uncompressed YUV 4:2:2',
            'YUYV' =>    'Canopus YUV',
            'YV12' =>    'YVU12 Planar',
            'YVU9' =>    'Intel YVU9 Planar (8-bpp Y plane, followed by 8-bpp 4x4 U and V planes)',
            'YVYU' =>    'YVYU 4:2:2 Packed',
            'ZLIB' =>    'Lossless Codec Library zlib compression (www.geocities.co.jp/Playtown-Denei/2837/LRC.htm)',
            'ZPEG' =>    'Metheus Video Zipper'
        );     
        $key = strtoupper($format);
        return $relations[$key];  
    }
    
}
?>