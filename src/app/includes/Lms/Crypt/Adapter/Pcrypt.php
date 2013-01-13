<?php

class Lms_Crypt_Adapter_Pcrypt
{
    /** Encryption Block Mode: ECB, CBC actually.
      *
      * @var    int the mode used
      * @access private
      */
    private $blockmode = Lms_Crypt::MODE_ECB;

    /** Key for Encryption
      *
      * @var    string the key used in encryption and decryption
      * @access public
      */
    private $key = null;

    /** IV - Initialization Vector String
      *
      * @var    string initialization vector for some modes (CBC)
      * @access public
      */
    private $iv  = "z5c8e7gh";

    /** Methods */

    /** Constructor of the class.
      *
      * The constructor initialize some important vars and include the algorithm
      * file.
      *
      * @access public
      * @param  int $blockmode the blockmode to use
      * @param  string $cipher the algorithm used to crypt
      * @param  string $key the ley used to crypt
      *
      * @return void
      */

    public function __construct($blockmode = Lms_Crypt::MODE_ECB, $cipher = 'BLOWFISH', $key = null)
    {
        // Include cipher_class file
        $cipher = preg_replace("/(mcrypt_|[^a-z0-9])/i", "", strtolower($cipher)) ;

        $class  = "Lms_Crypt_Adapter_Pcrypt_" . ucfirst($cipher);
        $this->cipher = new $class($key);

        // Initialize Vars
        $this->blockmode = $blockmode;
        $this->key = $key;
    }

    /** Crypt data using the selected algorithm
      *
      * This method encrypt data using the selected algorithm and mode:
      *    Algorithms: Blowfish
      *    Modes: ECB, CBC
      * For a description about algorithms and modes see:
      * Applied Cryptography by Bruce Schneier
      *
      * @access public
      * @param  string $plain  the plain text to be encrypted
      * @return string $cipher the plain text encrypted
      */
    public function encrypt($plain)
    {
        if (empty($plain))
        {
            $this->error("Empty Plain Text");
        }

        // Encrypt using the correct mode
        switch($this->blockmode) {
        case MODE_ECB:
            $cipher = $this->_ecb_encrypt($plain);
            break;

        case MODE_CBC:
            $cipher = $this->_cbc_encrypt($plain);
            break;

        default:
            $this->error("Invalid mode ".$this->blockmode);
        }

        return $cipher;
    }

    /** Decrypt using the selected algorithm
      *
      * This method decrypt data using the selected algorithm and mode.
      * TODO: Discover the algorithm and mode auto
      *
      * @access public
      * @param  string $cipher the crypted data to be decrypted
      * @return string $plain  the cipher text decrypted
      */
    public function decrypt($cipher)
    {
        if (empty($cipher))
        {
            $this->error("Invalid Cipher Text");
        }

        // Decrypt with the correct mode
        switch($this->blockmode) {
        case MODE_ECB:
            $plain = $this->_ecb_decrypt($cipher);
            break;

        case MODE_CBC:
            $plain = $this->_cbc_decrypt($cipher);
            break;

        default:
            $this->error("Invalid mode ".$this->blockmode);
        }

        return $plain;
    }

    /** Method to encrypt using ECB mode.
      *
      * In ECB mode the blocks are encrypted independently
      *
      * @access private
      * @param  string $plain  the plain text to be encrypted
      * @return string $cipher the plain text encrypted
      */
    private function _ecb_encrypt($plain)
    {
        $blocksize = $this->cipher->blocksize;
        $plainsize = strlen($plain);
        $cipher    = '';

        for($i = 0;$i < $plainsize;$i = $i + $blocksize)
        {
            $block = substr($plain,$i,$blocksize);

            if(strlen($block) < $blocksize)
            {
                // pad block with '\0'
                $block = str_pad($block,$blocksize,"\0",STR_PAD_LEFT);
            }
            $cipher .= $this->cipher->_encrypt($block);
        }

        return $cipher;
    }

    /** Method to decrypt using ECB mode.
      *
      * @access private
      * @param  string $cipher the cipher text
      * @return string $plain  the cipher text decrypted
      */
    private function _ecb_decrypt($cipher)
    {
        $blocksize  = $this->cipher->blocksize;
        $ciphersize = strlen($cipher);
        $plain      = '';

        for($i = 0;$i < $ciphersize;$i = $i + $blocksize)
        {
            $block = substr($cipher,$i,$blocksize);
            $block = $this->cipher->_decrypt($block);

            // Remove padded chars
            while(substr($block,0,1) == "\0")
            {
                $block = substr($block,1);
            }
            $plain .= $block;
        }

        return $plain;
    }

    /** This method encrypt using CBC mode.
      *
      * In CBC mode each block is xored with the last. This function use $iv as
      * first block.
      *
      * @access private
      * @param  string $plain  the plain text to be decrypted
      * @return string $cipher the plain text encrypted
      */
    private function _cbc_encrypt($plain)
    {
        $blocksize = $this->cipher->blocksize;
        $plainsize = strlen($plain);
        $cipher    = '';
        $lcipher   = $this->iv;

        // encrypt each block
        for($i = 0;$i < $plainsize;$i = $i + $blocksize)
        {
            $block = substr($plain,$i,$blocksize);
            if(strlen($block) < $blocksize)
            {
                // pad block with '\0'
                $block = str_pad($block,$blocksize,"\0",STR_PAD_LEFT);
            }
            // crypt the block xored with the last cipher block
            $lcipher = $this->cipher->_encrypt($block ^ $lcipher);
            $cipher .= $lcipher;
        }

        return $cipher;
    }

    /** This method decrypt using CBC.
      *
      * @access private
      * @param  string $cipher the cipher text
      * @return string $plain  the cipher text decrypted
      */
    private function _cbc_decrypt($cipher)
    {
        // get the block size of the cipher
        $blocksize  = $this->cipher->blocksize;
        $ciphersize = strlen($cipher);
        $plain      = '';
        $lcipher    = $this->iv;

        for($i = 0;$i < $ciphersize;$i = $i + $blocksize)
        {
            $block   = substr($cipher,$i,$blocksize);

            // xor the block with the last cipher block
            $dblock  = $lcipher ^ $this->cipher->_decrypt($block);
            $lcipher = $block;

            // Remove padded chars
            while(substr($dblock,0,1) == "\0")
            {
                $dblock = substr($dblock,1);
            }
            $plain .= $dblock;
        }

        return $plain;
    }

    /**
      * A simple function for error handling.
      *
      * TODO: Improve the error handling of the class
      *
      * @access private
      * @param  string $message erro message
      * @return boolean true
      */
    private function error($message)
    {
    	trigger_error($message,E_USER_WARNING);
        return 1;
    }
}