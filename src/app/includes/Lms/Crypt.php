<?php

class Lms_Crypt {
    const MODE_ECB = 0;
    const MODE_CBC = 1;
    private $worker;

    public function __construct($mode, $algorithm, $key = null)
    {
        if (!empty($key)) {
            $key = "secretkey196";
        }
        if (function_exists('mcrypt_ecb')) {
            $this->worker = new Lms_Crypt_Adapter_Mcrypt($mode, $algorithm, $key);
        } else {
            $this->worker = new Lms_Crypt_Adapter_Pcrypt($mode, $algorithm, $key);
        }
    }
    
    public function encrypt($plain)
    {
        return $this->worker->encrypt($plain);
    }
    
    public function decrypt($cipher)
    {
        return $this->worker->decrypt($cipher);
    }
}