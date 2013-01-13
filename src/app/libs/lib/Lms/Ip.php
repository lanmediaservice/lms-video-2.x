<?php
/**
 * Работа с IP адресами
 * 
 * @copyright 2006-2009 LanMediaService, Ltd.
 * @license    http://www.lms.by/license/1_0.txt
 * @author Ilya Spesivtsev
 * @author Alex Tatulchenkov
 * @version $Id: Ip.php 115 2009-09-18 13:49:22Z macondos $
 */
 

/**
 * Класс для работы с IP-адресами в различных нотациях
 *
 * Переводит IP-адреса из классой нотации в CIDR(слеш нотация) и обратно, 
 * проверяет корректность IP-адреса, маски и размера подсети
 * 
 * @copyright 2006-2009 LanMediaService, Ltd.
 * @license http://www.lms.by/license/1_0.txt
 */


class Lms_Ip
{
    /**
     * IP-адрес (в формате ip2long)
     * 
     * @var int/null 
     */
    private $_ip; 
    /**
     * Маска подсети (в формате ip2long)
     *
     * @var int/null
     */
    private $_netmask;
    
    /**
     * Конструктор
     *
     * @param string $ip
     * @param string $mask
     */
    public function __construct($ip = null, $mask = null)
    {
        if (is_null($ip)) {
            $this->_ip = ip2long(self::getIp());
        } else {
            if (self::_isCorrectIp($ip)) {
                $long = ip2long($ip);
                $this->_ip = $long;
            } else {
                throw new Lms_Ip_Exception('Incorrect IP');
            }
        }
        if (is_null($mask)) {
            $this->_netmask = null;
        } else {
            if (self::_isCorrectSubnetMask($mask)) {
                $this->_netmask = is_null($mask)? null : ip2long($mask);
            } else {
                throw new Lms_Ip_Exception('Incorrect subnet mask');
            }
        }
    } 
    
    /**
     * Фабричный метод создания IP-адреса. Возвращает Lms_Ip
     *
     * @param string $ip
     * @return Lms_Ip
     */
    public static function newIp($ip = null)
    {
        return new self($ip);
    }
    
    /**
     * Фабричный метод создания новой сети
     *
     * @param string $ip
     * @param string $mask
     * @return Lms_Ip
     */
    public static function newNet($ip = null, $mask = '255.255.255.255')
    {
        if (is_null($ip)) {
            return new self($ip, $mask);
        }
        if (false !== strpos($ip, '/')) {
            list($subnetId, $size) = explode('/', $ip);
            if (self::_isCorrectIp($subnetId)
                && self::_isCorrectSubnetSize($size)
            ) {
                self::cidrToNet($ip, $intIp, $intSubnetMask);
                return new self(long2ip($intIp), long2ip($intSubnetMask));
            } 
        } else {
            if (self::_isCorrectIp($ip) && self::_isCorrectSubnetMask($mask)) { 
                return new self($ip, $mask);    
            }
        }
        throw new Lms_Ip_Exception('Incorrect net');
    }
    
    /**
     * Устанавливает IP-адрес
     *
     * @param string $ip
     */
    public function setIp($ip)
    {
        if (self::_isCorrectIp($ip)) {
            $long = ip2long($ip);
            $this->_ip = $long;
        } else {
            throw new Lms_Ip_Exception('Incorrect IP');
        }
    } 
    
    /**
     * Устанавливает размер подсети
     *
     * @param int $size
     */
    public function setSubnetSize($size)
    {
        if (self::_isCorrectSubnetSize($size)) {
            $subnetSize = 32 - $size;
            $intSubnetMask =  self::getSubnetMaskBySize($subnetSize);
            $this->_netmask = $intSubnetMask;
        } else {
            throw new Lms_Ip_Exception('Incorrect subnet size');
        }
    }
    
    /**
     * Устанавливает маску подсети
     *
     * @param string $mask
     */
    public function setSubnetMask($mask)
    {
        if (self::_isCorrectSubnetMask($mask)) {
            $long = ip2long($mask);
            $this->_netmask = $long;
        } else {
            throw new Lms_Ip_Exception('Incorrect subnet mask');
        }
    }
    
    /**
     * Переводит IP-адрес и маску подсети, представленные в виде целых
     * десятичных чисел в адрес в CIDR нотации и возвращает результат
     * преобразования.
     *
     * @param int $intIp
     * @param int $intSubnetMask
     * @return string
     */
    public static function netToCidr($intIp, $intSubnetMask)
    {
        $subnetMask = strpos(decbin($intSubnetMask), "0");
        if ($subnetMask===false) $subnetMask = 32;
        $subnetSize = 32 - $subnetMask;
        $cidr = long2ip($intIp >> $subnetSize << $subnetSize)
              . "/" . $subnetMask;
        return $cidr;
    }

    /**
     * Переводит адрес в CIDR нотации в IP-адрес и маску подсети,
     * представленные в виде целых десятичных чисел
     *
     * @param string $cidr
     * @param int $intIp
     * @param int $intSubnetMask
     */
    public static function cidrToNet($cidr, &$intIp, &$intSubnetMask)
    {
        if (strpos($cidr, "/") !== false) {
            list($strIp, $subnetMask) = explode("/", $cidr);
            $subnetMask = (int) $subnetMask;
        } else {
            $strIp = $cidr;
            $subnetMask = 32;
        }
        $subnetSize = 32 - $subnetMask;
        $intIp = ip2long($strIp) >> $subnetSize << $subnetSize;
        $intSubnetMask = self::getSubnetMaskBySize($subnetSize);
    }

    /**
     * Упрощает представление адреса в CIDR нотации
     *
     * @param string $cidr
     * @return bool
     */
    public static function simplifyCidr($cidr)
    {
        self::cidrToNet($cidr, $intIp, $intSubnetMask);
        return self::netToCidr($intIp, $intSubnetMask);
    }
    
    /**
     * Возвращает истину если IP-адрес $strIp принадлежит сети $cidr 
     *
     * @param string $strIp Строковое представление IP-адреса(точечная нотация)
     * @param string $cidr Адрес сети в CIDR нотации
     * @return bool
     */
    public static function ipInNet($strIp, $cidr)
    {
        Lms_Ip::cidrToNet($cidr, $intNetIp, $subnetMask);
        return (ip2long($strIp) & $subnetMask) ==  ($intNetIp & $subnetMask);
    }
    
    /**
     * Возвращает IP-адрес клиента
     *
     * @return string
     */
    public static function getIp()
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return '127.0.0.1';
        }
        return $_SERVER['REMOTE_ADDR'];
    }
    
    /**
     * Возвращает строковое представление IP-адреса или сети в CIDR нотации
     *
     * @return string
     */
    public function __toString()
    {
        if (is_null($this->_netmask)) {
            return long2ip($this->_ip);
        } else {
            return self::netToCidr($this->_ip, $this->_netmask);
        }
    }
    
    /**
     * Возвращает истину, если переданный параметром
     * IP-адрес является корректным
     *
     * @param string $ip
     * @return bool
     */
    private static function _isCorrectIp($ip)
    {
        if ((ip2long($ip) !== false)) {
            return true;
        }
        return false;
    }
    
    /**
     * Возвращает истину, если параметром передан корректный размер подсети
     *
     * @param string $size
     * @return bool
     */
    private static function _isCorrectSubnetSize($size)
    {
        if (((int)$size >= 0) && ((int)$size <= 32)) {
            return true;
        }
        return false;
    }
    
    /**
     * Возвращает истину, если параметром была передана корректная маска подсети
     *
     * @param string $mask
     * @return bool
     */
    private static function _isCorrectSubnetMask($mask)
    {
        if (preg_match('~^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$~', $mask)) {
            //Проверяем формат
            $bitMask = decbin(ip2long($mask));
            if ((strlen($bitMask) == 32) 
                 && preg_match('~^1+0*$~', $bitMask)) {
                 return true;    
            }
        }
        return false;
    }

    public static function getSubnetMaskBySize($subnetSize)
    {
        return ip2long('255.255.255.255') >> $subnetSize << $subnetSize;
    }
}
