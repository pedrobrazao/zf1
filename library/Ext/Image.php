<?php
/**
 * Image
 * 
 * Examples:
 * 
 * $img = Ext_Image::factory('mac.jpg')
 *    ->trim()
 *     ->output();
 * if ($img)
 *     $img->destroy();
 *
 * $img = Ext_Image::factory('Sunset.jpg')
 *     ->scale(200, 120)
 *     ->crop(100, 100)
 *     ->output();
 *
 * $img = Ext_Image::factory('Winter.jpg')
 *     ->grayscale()
 *     ->brightness(20)
 *     ->contrast(-30)
 *     ->output();
 *
 * $img = Ext_Image::factory('Water lilies.jpg')
 *     ->colorize('ff0000')
 *     ->blur(5)
 *     ->reverse()
 *     ->output();
 * 
 * $img = Ext_Image::factory()
 *   ->from_string($str)
 *   ->output();
 */
class Ext_Image
{

    // GD image
    protected $im;

    // file name
    protected $file = '';

    // file size
    protected $size = 0;

    // file extension
    protected $extension = '';

    // image type
    protected $type = 0;

    // MIME type string
    protected $mime_type = '';

    // mage width and height
    protected $width = 0;
    protected $height = 0;

    // background color
    protected $bgcolor = 'FFFFFF';

    // foreground color
    protected $fgcolor = '000000';

    /**
     *
     * @param string $file
     * @return Image 
     */
    public static function factory($file='') {
        return new Image($file);
    }

    /**
     *
     * @param string $file
     * @return Image
     */
    public function __construct($file='') {
        if (file_exists($file)) {
            $this->from_file($file);
        }
        return $this;
    }

    /**
     *
     * @param string $file
     */
    public function from_file($file) {
        $this->_get_image_data($file);
        if ($this->size) {
            $this->file = $file;
            if ($this->type == IMAGETYPE_GIF) {
                $this->im = imagecreatefromgif($file);
            } elseif ($this->type == IMAGETYPE_JPEG) {
                $this->im = imagecreatefromjpeg($file);
            } elseif ($this->type == IMAGETYPE_PNG) {
                $this->im = imagecreatefrompng($file);
            }
        }
        return $this;
    }

    /**
     *
     * @param string $string
     * @param string $tmpname
     */
    public function from_string($string, $tmpname='') {
        if ($tmpname == '')
            $tmpname = tempnam('', '');
        if ($fp = fopen($tmpname, 'wb')) {
            fwrite($fp, $string, strlen($string));
            fclose($fp);
            $this->from_file($tmpname);
            unlink($tmpname);
        }
        return $this;
    }

    /**
     *
     * @param string $file
     */
    protected function _get_image_data($file) {
        if (file_exists($file)) {
            $img = @getimagesize($file);
            if (is_array($img)) {
                $this->width = $img[0];
                $this->height = $img[1];
                $this->type = $img[2];
                $this->size = filesize($file);
                $this->mime_type = image_type_to_mime_type($this->type);
                $this->extension = image_type_to_extension($this->type);
            }
        }
    }

    /**
     *
     */
    protected  function _get_tmp_data() {
        $tmpname = tempnam('', '');
        if ($this->type == IMAGETYPE_GIF) {
            imagegif($this->im, $tmpname);
        } elseif ($this->type == IMAGETYPE_JPEG) {
            imagejpeg($this->im, $tmpname);
        } elseif ($this->type == IMAGETYPE_PNG) {
            imagepng($this->im, $tmpname);
        } else {
            // TODO
        }
        $this->_get_image_data($tmpname);
        unlink($tmpname);
    }

    /**
     *
     */
    public function output() {
        if ($this->im) {
            if ($this->type == IMAGETYPE_GIF) {
                header('Content-Type: ' . image_type_to_mime_type(IMAGETYPE_GIF));
                imagegif($this->im);
            } elseif ($this->type == IMAGETYPE_JPEG) {
                header('Content-Type: ' . image_type_to_mime_type(IMAGETYPE_JPEG));
                imagejpeg($this->im);
            } else {
                header('Content-Type: ' . image_type_to_mime_type(IMAGETYPE_PNG));
                imagepng($this->im);
            }
        } else {
            if ($fc = @file_get_contents(str_replace("\\", "/", $this->file))) {
                header('Content-Type: ' . image_type_to_mime_type($this->type));
                echo $fc;
            }
        }
    }

    /**
     *
     */
    function destroy() {
        if ($this->im)
            imagedestroy($this->im);
    }

    /**
     *
     * @param string $type
     */
    public function convert($type=0) {
        if ($this->im && $type > 0) {
            $this->type = $type;
            $this->_get_tmp_data();
        }
        return $this;
    }

    /**
     *
     * @param int $width
     * @param int $height
     */
    public function resize($width, $height) {
        if ($this->im && $width > 0 && $height > 0) {
            $ni = imagecreatetruecolor($width, $height);
            imagecopyresampled($ni, $this->im, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
            imagedestroy($this->im);
            $this->im = imagecreatetruecolor($width, $height);
            imagecopy($this->im, $ni, 0, 0, 0, 0, $width, $height);
            imagedestroy($ni);
            $this->_get_tmp_data();
        }
        return $this;
    }

    /**
     *
     * @param int|string $rate
     */
    public function scale($rate) {
        if ($this->im) {
            if (substr(trim($rate), -1, 1) == '%') {
                $rate = substr(trim($rate), 0, -1);
                $rate /= 100;
            } else {
                $rate = $rate / max($this->width, $this->height);
            }
            $width = ceil($this->width * $rate);
            $height = ceil($this->height * $rate);
            $this->resize($width, $height);
        }
        return $this;
    }

    /**
     *
     * @param int $width
     * @param int $height
     * @param int $anchor
     */
    public function crop($width=0, $height=0, $anchor=5) {
        if ($this->im) {
            $width = $width > 0 ? $width : $this->width;
            $height = $height > 0 ? $height : $this->height;
            if ((int) $anchor < 1 || (int) $anchor > 9) $anchor = 5;
            if ($anchor == 1) {
                $nx = 0;
                $ny = 0;
                $ix = 0;
                $iy = 0;
            } elseif ($anchor == 2) {
                $nx = $width > $this->width ? ($width - $this->width) / 2 : 0;
                $ny = 0;
                $ix = $width < $this->width ? ($this->width - $width) / 2 : 0;
                $iy = 0;
            } elseif ($anchor == 3) {
                $nx = $width > $this->width ? ($width - $this->width) : 0;
                $ny = 0;
                $ix = $width < $this->width ? ($this->width - $width) : 0;
                $iy = 0;
            } elseif ($anchor == 4) {
                $nx = 0;
                $ny = $height > $this->height ? ($height - $this->height) / 2 : 0;
                $ix = 0;
                $iy = $height < $this->height ? ($this->height - $height) / 2 : 0;
            } elseif ($anchor == 5) {
                $nx = $width > $this->width ? ($width - $this->width) / 2 : 0;
                $ny = $height > $this->height ? ($height - $this->height) / 2 : 0;
                $ix = $width < $this->width ? ($this->width - $width) / 2 : 0;
                $iy = $height < $this->height ? ($this->height - $height) / 2 : 0;
            } elseif ($anchor == 6) {
                $nx = $width > $this->width ? ($width - $this->width) : 0;
                $ny = $height > $this->height ? ($height - $this->height) / 2 : 0;
                $ix = $width < $this->width ? ($this->width - $width) : 0;
                $iy = $height < $this->height ? ($this->height - $height) / 2 : 0;
            } elseif ($anchor == 7) {
                $nx = 0;
                $ny = $height > $this->height ? ($height - $this->height) : 0;
                $ix = 0;
                $iy = $height < $this->height ? ($this->height - $height) : 0;
            } elseif ($anchor == 8) {
                $nx = $width > $this->width ? ($width - $this->width) / 2 : 0;
                $ny = $height > $this->height ? ($height - $this->height) : 0;
                $ix = $width < $this->width ? ($this->width - $width) / 2 : 0;
                $iy = $height < $this->height ? ($this->height - $height) : 0;
            } elseif ($anchor == 9) {
                $nx = $width > $this->width ? ($width - $this->width) : 0;
                $ny = $height > $this->height ? ($height - $this->height) : 0;
                $ix = $width < $this->width ? ($this->width - $width) : 0;
                $iy = $height < $this->height ? ($this->height - $height) : 0;
            }
            $ni = imagecreatetruecolor($width, $height);
            $r = hexdec(substr($this->bgcolor, 0, 2));
            $g = hexdec(substr($this->bgcolor, 2, 2));
            $b = hexdec(substr($this->bgcolor, 4, 2));
            imagefill($ni, 0, 0, imagecolorallocate($ni, $r, $g, $b));
            imagecopy($ni, $this->im, $nx, $ny, $ix, $iy, $this->width, $this->height);
            imagedestroy($this->im);
            $this->im = imagecreatetruecolor($width, $height);
            imagefill($this->im, 0, 0, imagecolorallocate($ni, $r, $g, $b));
            imagecopy($this->im, $ni, 0, 0, 0, 0, $width, $height);
            imagedestroy($ni);
            $this->_get_tmp_data();
        }
        return $this;
    }

    /**
     *
     * @param string $bgcolor
     * @param decimal $tolerance
     */
    public function trim($bgcolor='', $tolerance=0.1) {
        if ($bgcolor == '')
            $bgcolor = $this->bgcolor;
        $bg = array(
            'red' => hexdec(substr($bgcolor, 0, 2)),
            'green' => hexdec(substr($bgcolor, 2, 2)),
            'blue' => hexdec(substr($bgcolor, 4, 2))
        );
        if ($this->im) {
            $nw = $this->width;
            $nh = $this->height;
            $nx = 0;
            $ny = 0;
            $ix = 0;
            $iy = 0;
            for ($x = 0; $x < $this->width; $x++) {
                for ($y = 0; $y < $this->height; $y++) {
                    $c = imagecolorsforindex($this->im, imagecolorat($this->im, $x, $y));
                    if (!$this->_match_color($bg, $c, $tolerance)) {
                        break;
                    }
                }
                if (!$this->_match_color($bg, $c, $tolerance)) {
                    $nw = $nw - $x;
                    $ix = $x;
                    break;
                }
            }
            for ($y = 0; $y < $this->height; $y++) {
                for ($x = 0; $x < $this->width; $x++) {
                    $c = imagecolorsforindex($this->im, imagecolorat($this->im, $x, $y));
                    if (!$this->_match_color($bg, $c, $tolerance)) {
                        break;
                    }
                }
                if (!$this->_match_color($bg, $c, $tolerance)) {
                    $nh = $nh - $y;
                    $iy = $y;
                    break;
                }
            }
            for ($x = $this->width - 1; $x >= 0; $x--) {
                for ($y = $this->height - 1; $y >= 0; $y--) {
                    $c = imagecolorsforindex($this->im, imagecolorat($this->im, $x, $y));
                    if (!$this->_match_color($bg, $c, $tolerance)) {
                        break;
                    }
                }
                if (!$this->_match_color($bg, $c, $tolerance)) {
                    $nw = $nw - ($this->width - $x) + 1;
                    break;
                }
            }
            for ($y = $this->height - 1; $y >= 0; $y--) {
                for ($x = $this->width - 1; $x >= 0; $x--) {
                    $c = imagecolorsforindex($this->im, imagecolorat($this->im, $x, $y));
                    if (!$this->_match_color($bg, $c, $tolerance)) {
                        break;
                    }
                }
                if (!$this->_match_color($bg, $c, $tolerance)) {
                    $nh = $nh - ($this->height - $y) + 1;
                    break;
                }
            }
            $ni = imagecreatetruecolor($nw, $nh);
            imagecopy($ni, $this->im, $nx, $ny, $ix, $iy, $nw, $nh);
            imagedestroy($this->im);
            $this->im = imagecreatetruecolor($nw, $nh);
            imagecopy($this->im, $ni, 0, 0, 0, 0, $nw, $nh);
            imagedestroy($ni);
            $this->_get_tmp_data();
        }
        return $this;
    }

    /**
     *
     * @param array $old
     * @param array $new
     * @param decimal $tolerance
     * @return boolean
     */
    protected function _match_color($old, $new, $tolerance=0.1) {
        $tolerance = $tolerance < 0 ? 0 : $tolerance;
        $tolerance = $tolerance > 1 ? 1 : $tolerance;
        $match = FALSE;
        if (is_array($old) && sizeof($old) >= 3 && is_array($new) && sizeof($new) >= 3) {
            if ($new['red'] >= $old['red'] * (1 - $tolerance)
                    && $new['red'] <= $old['red'] * (1 + $tolerance)
                    && $new['green'] >= $old['green'] * (1 - $tolerance)
                    && $new['green'] <= $old['green'] * (1 + $tolerance)
                    && $new['blue'] >= $old['blue'] * (1 - $tolerance)
                    && $new['blue'] <= $old['blue'] * (1 + $tolerance)
            )
                $match = true;
        }
        return $match;
    }

    /**
     *
     */
    public function reverse() {
        if ($this->im) {
            imagefilter($this->im, IMG_FILTER_NEGATE);
        }
        return $this;
    }

    /**
     *
     */
    public function grayscale() {
        if ($this->im) {
            imagefilter($this->im, IMG_FILTER_GRAYSCALE);
        }
        return $this;
    }

    /**
     *
     * @param int $level
     */
    public function brightness($level=50) {
        if ($this->im) {
            imagefilter($this->im, IMG_FILTER_BRIGHTNESS, $level);
        }
        return $this;
    }

    /**
     *
     * @param int $level
     */
    public function contrast($level=-50) {
        if ($this->im) {
            imagefilter($this->im, IMG_FILTER_CONTRAST, $level);
        }
        return $this;
    }

    /**
     *
     * @param string $color
     */
    public function colorize($color='') {
        if (!empty($color)) {
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));
            if ($this->im) {
                imagefilter($this->im, IMG_FILTER_COLORIZE, $r, $g, $b);
            }
        }
        return $this;
    }

    /**
     *
     */
    public function edges() {
        if ($this->im) {
            imagefilter($this->im, IMG_FILTER_EDGEDETECT);
        }
        return $this;
    }

    /**
     *
     */
    public function emboss() {
        if ($this->im) {
            imagefilter($this->im, IMG_FILTER_EMBOSS);
        }
        return $this;
    }

    /**
     *
     * @param int $steps
     */
    public function gaussian($steps=1) {
        if ($this->im) {
            for ($i = 0; $i < $steps; $i++) {
                imagefilter($this->im, IMG_FILTER_GAUSSIAN_BLUR);
            }
        }
        return $this;
    }

    /**
     *
     * @param int $steps
     */
    public function blur($steps=1) {
        if ($this->im) {
            for ($i = 0; $i < $steps; $i++) {
                imagefilter($this->im, IMG_FILTER_SELECTIVE_BLUR);
            }
        }
        return $this;
    }

    /**
     *
     */
    public function sketch() {
        if ($this->im) {
            imagefilter($this->im, IMG_FILTER_MEAN_REMOVAL);
        }
        return $this;
    }

    /**
     *
     * @param int $level
     */
    public function smooth($level=10) {
        if ($this->im) {
            imagefilter($this->im, IMG_FILTER_SMOOTH, $level);
        }
        return $this;
    }

    /**
     *
     * @param string $color
     * @param int $percent
     */
    public function cover($color, $percent=20) {
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $ni = imagecreatetruecolor($this->width, $this->height);
        $c = imagecolorallocate($ni, $r, $g, $b);
        imagefilledrectangle($ni, 0, 0, $this->width, $this->height, $c);
        imagecopymerge($this->im, $ni, 0, 0, 0, 0, $this->width, $this->height, $percent);
        imagedestroy($ni);
        return $this;
    }

    /**
     *
     * @param string $imagetype
     * @return string|boolean
     */
    protected function _extension($imagetype) {
        switch ($imagetype) {
            case IMAGETYPE_GIF : return 'gif';
            case IMAGETYPE_JPEG : return 'jpg';
            case IMAGETYPE_PNG : return 'png';
            case IMAGETYPE_SWF : return 'swf';
            case IMAGETYPE_PSD : return 'psd';
            case IMAGETYPE_BMP : return 'bmp';
            case IMAGETYPE_TIFF_II : return 'tif';
            case IMAGETYPE_TIFF_MM : return 'tif';
            case IMAGETYPE_JPC : return 'jpc';
            case IMAGETYPE_JP2 : return 'jp2';
            case IMAGETYPE_JPX : return 'jpf';
            case IMAGETYPE_JB2 : return 'jb2';
            case IMAGETYPE_SWC : return 'swc';
            case IMAGETYPE_IFF : return 'aiff';
            case IMAGETYPE_WBMP : return 'wbmp';
            case IMAGETYPE_XBM : return 'xbm';
            default : return false;
        }
    }

}
