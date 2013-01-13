<?php

class Lms_Crypt_Adapter_Mcrypt
{
    private $alghoritm = MCRYPT_BLOWFISH;
    private $blockmode = Lms_Crypt::MODE_ECB;
    private $key = null;
    private $iv  = "z5c8e7gh";

    public function __construct($blockmode = Lms_Crypt::MODE_ECB, $alghoritm = MCRYPT_BLOWFISH, $key = null)
    {
        $this->blockmode = $blockmode;
        $this->alghoritm = $alghoritm;
        $this->key = $key;
    }

    public function encrypt($plain)
    {
    	if ($this->blockmode==Lms_Crypt::MODE_ECB) {
            $cipher = mcrypt_ecb ($this->alghoritm, $this->key, $plain, MCRYPT_ENCRYPT, $this->iv);
    	}
    	if ($this->blockmode==Lms_Crypt::MODE_CBC) {
            $cipher = mcrypt_cbc ($this->alghoritm, $this->key, $plain, MCRYPT_ENCRYPT, $this->iv);
    	}
        return $cipher;
    }

    public function decrypt($cipher)
    {
    	if ($this->blockmode==Lms_Crypt::MODE_ECB) {
            $plain = mcrypt_ecb ($this->alghoritm, $this->key, $cipher, MCRYPT_DECRYPT, $this->iv);
        }
    	if ($this->blockmode==Lms_Crypt::MODE_CBC) {
            $plain = mcrypt_cbc ($this->alghoritm, $this->key, $cipher, MCRYPT_DECRYPT, $this->iv);
    	}
        return $plain;
    }
}