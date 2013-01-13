<?php

interface Lms_MetaParser_Adapter_Interface
{
    static public function analyze($demuxerInstance, $url, $fileSize);
}
