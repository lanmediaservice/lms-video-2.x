<?php
/**
 * LMS Library
 * 
 * @version $Id: ImageProcessor.php 395 2010-03-21 21:19:28Z macondos $
 * @copyright 2007
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @package ImageProcessor
 */

class Lms_ImageProcessor
{
    /* properties */
    private $_imageStream;
    private $_sFileLocation;
    private $_sImageUrl;

    private $_filename;
    private $_width;
    private $_height;
    private $_orientation;
    private $_type;
    private $_mimetype;
    private $_interlace;
    private $_jpegQuality;

    public function __construct($jpegQuality = 80, $imageInterlace = true)
    {
        $this->aProperties = array();
        $this->_jpegQuality = $jpegQuality;
        $this->_interlace = $imageInterlace;
    }

    function loadImage()
    {
        /* load a image from file */
        switch ($this->_type) {
        case 1:
            $this->_imageStream = @imagecreatefromgif($this->_sFileLocation);
            break;
        case 2:
            $this->_imageStream = @imagecreatefromjpeg($this->_sFileLocation);
            break;
        case 3:
            $this->_imageStream = @imagecreatefrompng($this->_sFileLocation);
            imagealphablending($this->_imageStream, true);
            break;
        default:
            throw new Lms_ImageProcessor_Exception('invalid imagetype');
        }

        if (!$this->_imageStream) {
            throw new Lms_ImageProcessor_Exception('image not loaded');
        }
    }

    function saveImage()
    {
        /* store a memoryimage to file */

        if (!$this->_imageStream) {
            throw new Lms_ImageProcessor_Exception('image not loaded');
        }

        switch ($this->_type) {
        case 1:
            /* store a interlaced gif image */
            if ($this->_interlace === true) {
                imageinterlace($this->_imageStream, 1);
            }

            imagegif($this->_imageStream, $this->_sFileLocation);
            break;
        case 2:
            /* store a progressive jpeg image (with default quality value)*/
            if ($this->_interlace === true) {
                imageinterlace($this->_imageStream, 1);
            }

            imagejpeg(
                $this->_imageStream, $this->_sFileLocation, $this->_jpegQuality
            );
            break;
        case 3:
            /* store a png image */
            imagepng($this->_imageStream, $this->_sFileLocation);
            break;
        default:
            throw new Lms_ImageProcessor_Exception('invalid imagetype');

            if (!file_exists($this->_sFileLocation)) {
                throw new Lms_ImageProcessor_Exception('file not stored');
            }
        }
    }

    function showImage()
    {
        /* show a memoryimage to screen */
        if (!$this->_imageStream) {
            throw new Lms_ImageProcessor_Exception('image not loaded');
        }

        switch ($this->_type) {
        case 1:
            imagegif($this->_imageStream);
            break;
        case 2:
            imagejpeg($this->_imageStream);
            break;
        case 3:
            imagepng($this->_imageStream);
            break;
        default:
            throw new Lms_ImageProcessor_Exception('invalid imagetype');
        }
    }

    function setFilenameExtension()
    {
        switch ($this->_type) {
            case 1:
                $this->_filename = pathinfo($this->_filename, PATHINFO_FILENAME)
                                 . '.gif';
                break;
            case 2:
                $this->_filename = pathinfo($this->_filename, PATHINFO_FILENAME)
                                 . '.jpg';
                break;
            case 3:
                $this->_filename = pathinfo($this->_filename, PATHINFO_FILENAME)
                                 . '.png';
                break;
            default:
                throw new Lms_ImageProcessor_Exception('invalid imagetype');
                break;
        }
    }

    function setImageType($iType)
    {
        /* set the imahe type and mimetype */
        switch ($iType) {
        case 1:
            $this->_type = $iType;
            $this->_mimetype = 'image/gif';
            $this->setFilenameExtension();
            break;
        case 2:
            $this->_type = $iType;
            $this->_mimetype = 'image/jpeg';
            $this->setFilenameExtension();
            break;
        case 3:
            $this->_type = $iType;
            $this->_mimetype = 'image/png';
            $this->setFilenameExtension();
            break;
        default:
            throw new Lms_ImageProcessor_Exception('invalid imagetype');
        }
    }

    function setLocations($sFileName)
    {
        /* set the photo url */
        $this->_filename = basename($sFileName);
        $this->_sFileLocation = $sFileName;
        $this->_sImageUrl = $sFileName;
    }

    function initializeImageProperties()
    {
        /* get imagesize from file and set imagesize array */
        list($this->_width, $this->_height, $iType, $this->htmlattributes)
            = getimagesize($this->_sFileLocation);

        if (($this->_width < 1) || ($this->_height < 1)) {
            throw new Lms_ImageProcessor_Exception('invalid imagesize');
        }

        $this->setImageOrientation();
        $this->setImageType($iType);
    }

    function setImageOrientation()
    {
        /* get image-orientation based on imagesize
       options: [ portrait | landscape | square ] */

        if ($this->_width < $this->_height) {
            $this->_orientation = 'portrait';
        }

        if ($this->_width > $this->_height) {
            $this->_orientation = 'landscape';
        }

        if ($this->_width == $this->_height) {
            $this->_orientation = 'square';
        }
    }

    function loadfile($sFileName)
    {
        /* load an image from file into memory */
        $this->setLocations($sFileName);

        if (file_exists($this->_sFileLocation)) {
            $this->initializeImageProperties();
            $this->loadImage();
        } else {
            throw new Lms_ImageProcessor_Exception(
                "file $this->_sFileLocation not found"
            );
        }
    }

    function savefile($sFileName = null)
    {
        /* store memory image to file */
        if ((isset($sFileName)) && ($sFileName != '')) {
            $this->setLocations($sFileName);
        }

        $this->saveImage();
    }

    function preview()
    {
        /* print memory image to screen */
        header("Content-type: {$this->_mimetype}");
        $this->showImage();
    }

    function showhtml($sAltText = null, $sClassName = null)
    {
        /* print image as htmltag */
        if (file_exists($this->_sFileLocation)) {
            /* set html alt attribute */
            if ((isset($sAltText)) && ($sAltText != '')) {
                $htmlAlt = " alt=\"" . $sAltText . "\"";
            } else {
                $htmlAlt = "";
            }

            /* set html class attribute */
            if ((isset($sClassName)) && ($sClassName != '')) {
                $htmlClass = " class=\"" . $sClassName . "\"";
            } else {
                $htmlClass = " border=\"0\"";
            }

            $sHTMLOutput = '<img src="' . $this->_sImageUrl . '"' 
                         . $htmlClass . ' width="' . $this->_width . '" '
                         . 'height="' . $this->_height . '"' . $htmlAlt . '>';
            print $sHTMLOutput;
        } else {
            throw new Lms_ImageProcessor_Exception('file not found');
        }
    }

    function resize($iNewWidth, $iNewHeight)
    {
        /* resize the memoryimage do not keep ratio */
        if (!$this->_imageStream) {
            throw new Lms_ImageProcessor_Exception('image not loaded');
        }

        if (function_exists("imagecopyresampled")) {
            $resizedImageStream = imagecreatetruecolor($iNewWidth, $iNewHeight);
            
            if ($this->_type==1 || $this->_type==3) {
                imagealphablending($resizedImageStream, false);
                imagesavealpha($resizedImageStream, true);
                $transparent = imagecolorallocatealpha($resizedImageStream, 255, 255, 255, 127);
                imagefilledrectangle($resizedImageStream, 0, 0, $iNewWidth, $iNewHeight, $transparent);
            } 
            
            imagecopyresampled(
                $resizedImageStream, $this->_imageStream,
                0, 0, 0, 0,
                $iNewWidth, $iNewHeight, $this->_width, $this->_height
            );
        } else {
            $resizedImageStream = imagecreate($iNewWidth, $iNewHeight);
            imagecopyresized(
                $resizedImageStream, $this->_imageStream,
                0, 0, 0, 0,
                $iNewWidth, $iNewHeight, $this->_width, $this->_height
            );
        }

        $this->_imageStream = $resizedImageStream;
        $this->_width = $iNewWidth;
        $this->_height = $iNewHeight;
        $this->setImageOrientation();
    }

    function resizetowidth($iNewWidth)
    {
        /* resize image to given width (keep ratio) */
        $iNewHeight = ($iNewWidth / $this->_width) * $this->_height;

        $this->resize($iNewWidth, $iNewHeight);
    }

    function resizetoheight($iNewHeight)
    {
        /* resize image to given height (keep ratio) */
        $iNewWidth = ($iNewHeight / $this->_height) * $this->_width;

        $this->resize($iNewWidth, $iNewHeight);
    }

    function resizetopercentage($iPercentage)
    {
        /* resize image to given percentage (keep ratio) */
        $iPercentageMultiplier = $iPercentage / 100;
        $iNewWidth = $this->_width * $iPercentageMultiplier;
        $iNewHeight = $this->_height * $iPercentageMultiplier;

        $this->resize($iNewWidth, $iNewHeight);
    }

    function crop($iNewWidth, $iNewHeight, $iResize = 0)
    {
        /* crop image (first resize with keep ratio) */
        if (!$this->_imageStream) {
            throw new Lms_ImageProcessor_Exception('image not loaded');
        }

        /* resize imageobject in memory if resize percentage is set */
        if ($iResize > 0) {
            $this->resizetopercentage($iResize);
        }

        /* constrain width and height values */
        if (($iNewWidth > $this->_width) || ($iNewWidth < 0)) {
            throw new Lms_ImageProcessor_Exception('width out of range');
        }
        if (($iNewHeight > $this->_height) || ($iNewHeight < 0)) {
            throw new Lms_ImageProcessor_Exception('height out of range');
        }

        /* create blank image with new sizes */
        $croppedImageStream = ImageCreateTrueColor($iNewWidth, $iNewHeight);

        /* calculate size-ratio */
        $iWidthRatio = $this->_width / $iNewWidth;
        $iHeightRatio = $this->_height / $iNewHeight;
        $iHalfNewHeight = $iNewHeight / 2;
        $iHalfNewWidth = $iNewWidth / 2;

        /* if the image orientation is landscape */
        if ($this->_orientation == 'landscape') {
            /* calculate resize width parameters */
            $iResizeWidth = $this->_width / $iHeightRatio;
            $iHalfWidth = $iResizeWidth / 2;
            $iDiffWidth = $iHalfWidth - $iHalfNewWidth;

            if (function_exists("imagecopyresampled")) {
                imagecopyresampled(
                    $croppedImageStream, $this->_imageStream,
                    -$iDiffWidth, 0, 0, 0,
                    $iResizeWidth, $iNewHeight, $this->_width, $this->_height
                );
            } else {
                imagecopyresized(
                    $croppedImageStream, $this->_imageStream,
                    -$iDiffWidth, 0, 0, 0,
                    $iResizeWidth, $iNewHeight, $this->_width, $this->_height
                );
            }
        } else if (($this->_orientation == 'portrait')
                    || ($this->_orientation == 'square')
        ) {
            /* if the image orientation is portrait or square */
            /* calculate resize height parameters */
            $iResizeHeight = $this->_height / $iWidthRatio;
            $iHalfHeight = $iResizeHeight / 2;
            $iDiffHeight = $iHalfHeight - $iHalfNewHeight;

            if (function_exists("imagecopyresampled")) {
                imagecopyresampled(
                    $croppedImageStream, $this->_imageStream,
                    0, - $iDiffHeight, 0, 0,
                    $iNewWidth, $iResizeHeight, $this->_width, $this->_height
                );
            } else {
                imagecopyresized(
                    $croppedImageStream, $this->_imageStream,
                    0, - $iDiffHeight, 0, 0,
                    $iNewWidth, $iResizeHeight, $this->_width, $this->_height
                );
            }
        } else {
            if (function_exists("imagecopyresampled")) {
                imagecopyresampled(
                    $croppedImageStream, $this->_imageStream,
                    0, 0, 0, 0,
                    $iNewWidth, $iNewHeight, $this->_width, $this->_height
                );
            } else {
                imagecopyresized(
                    $croppedImageStream, $this->_imageStream,
                    0, 0, 0, 0,
                    $iNewWidth, $iNewHeight, $this->_width, $this->_height
                );
            }
        }

        $this->_imageStream = $croppedImageStream;
        $this->_width = $iNewWidth;
        $this->_height = $iNewHeight;
        $this->setImageOrientation();
    }

    function writeText(
        $sText, $iFontSize = 10, $sTextColor = '0,0,0',
        $sFontFilename = 'arial', $iXPos = 5, $iYPos = 15, $iTextAngle = 0
    )
    {
        /* write text on image */
        if (!$this->_imageStream) {
            throw new Lms_ImageProcessor_Exception('image not loaded');
        }

        if (($iXPos > $this->_width) || ($iXPos < 0)) {
            throw new Lms_ImageProcessor_Exception('x-pos out of range');
        }

        if (($iYPos > $this->_height) || ($iYPos < 0)) {
            throw new Lms_ImageProcessor_Exception('y-pos out of range');
        }

        $sFont = $sFontFilename;
        $aTextColor = explode(',', $sTextColor, 3);
        $imageColor = imagecolorallocate(
            $this->_imageStream,
            $aTextColor[0], $aTextColor[1], $aTextColor[2]
        );
        $iLineWidth = imagettfbbox($iFontSize, $iTextAngle, $sFont, $sText);
        imagettftext(
            $this->_imageStream, $iFontSize, $iTextAngle,
            $iXPos, $iYPos, $imageColor, $sFont, $sText
        );
    }

    function convert($sTargetType)
    {
        /* convert image to given type [ jpg | gif | png ] */
        if (!$this->_imageStream) {
            throw new Lms_ImageProcessor_Exception('image not loaded');
        }

        switch ($sTargetType) {
            case 'gif':
                $this->setImageType(1);
                break;
            case 'jpg':
                $this->setImageType(2);
                break;
            case 'png':
                $this->setImageType(3);
                break;
            default: 
                throw new Lms_ImageProcessor_Exception('invalid imagetype');
                break;
        }
    }
}