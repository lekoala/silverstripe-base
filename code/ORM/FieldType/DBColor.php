<?php
namespace LeKoala\Base\ORM\FieldType;

use LeKoala\Base\Forms\ColorField;
use SilverStripe\ORM\FieldType\DBVarchar;

/**
 * Color field-type stored as hex value
 */
class DBColor extends DBVarchar
{
    private static $casting = [
        'Luminance' => 'Float',
        'AlteredColorHSV' => Color::class
    ];

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, 16, $options);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        $field = ColorField::create($this->name, $title);
        return $field;
    }

    /**
     * Helper function to convert RGB to HSV
     * @param int $R red channel, 0-255
     * @param int $G green channel, 0-255
     * @param int $B blue channel, 0-255
     * @return array containing 3 values, H,S,V 0-1
     */
    public static function RGB_TO_HSV($R, $G, $B)  // RGB Values:Number 0-255
    {
        $HSV = [];
        $var_R = self::clamp($R / 255);
        $var_G = self::clamp($G / 255);
        $var_B = self::clamp($B / 255);
        $var_Min = min($var_R, $var_G, $var_B);
        $var_Max = max($var_R, $var_G, $var_B);
        $del_Max = $var_Max - $var_Min;
        $V = $var_Max;
        $H = 0;
        if ($del_Max == 0) {
            $H = 0;
            $S = 0;
        } else {
            $S = $del_Max / $var_Max;
            $del_R = ((($var_Max - $var_R) / 6) + ($del_Max / 2)) / $del_Max;
            $del_G = ((($var_Max - $var_G) / 6) + ($del_Max / 2)) / $del_Max;
            $del_B = ((($var_Max - $var_B) / 6) + ($del_Max / 2)) / $del_Max;
            if ($var_R == $var_Max) {
                $H = $del_B - $del_G;
            } elseif ($var_G == $var_Max) {
                $H = (1 / 3) + $del_R - $del_B;
            } elseif ($var_B == $var_Max) {
                $H = (2 / 3) + $del_G - $del_R;
            }
            if ($H < 0) {
                $H++;
            }
            if ($H > 1) {
                $H--;
            }
        }
        $HSV[] = $H;
        $HSV[] = $S;
        $HSV[] = $V;
        return $HSV;
    }

    /**
     * Helper function to convert HSV to RGB
     * @param float $H hue 0-1
     * @param float $S saturation 0-1
     * @param float $V brightness 0-1
     * @return array containing 3 values in the range from 0-255, R,G,B
     */
    public static function HSV_TO_RGB($H, $S, $V)
    {
        $H = self::clamp($H * 6, 0, 6);
        $S = self::clamp($S);
        $V = self::clamp($V);
        $I = floor($H);
        $F = $H - $I;
        $M = $V * (1 - $S);
        $N = $V * (1 - $S * $F);
        $K = $V * (1 - $S * (1 - $F));
        $R = $G = $B = 0;
        switch ($I) {
            case 0:
                list($R, $G, $B) = [$V, $K, $M];
                break;
            case 1:
                list($R, $G, $B) = [$N, $V, $M];
                break;
            case 2:
                list($R, $G, $B) = [$M, $V, $K];
                break;
            case 3:
                list($R, $G, $B) = [$M, $N, $V];
                break;
            case 4:
                list($R, $G, $B) = [$K, $M, $V];
                break;
            case 5:
            case 6: //for when $H=1 is given
                list($R, $G, $B) = [$V, $M, $N];
                break;
        }
        return [$R * 255.0, $G * 255.0, $B * 255.0];
    }

    /**
     * Convert a hex string to separate R,G,B values
     * @param string $hex
     * @return array containing 3 integers (0-255) R,G,B
     */
    public static function HEX_TO_RGB($hex)
    {
        $RGB = [];
        $color = intval(ltrim($hex, '#'), 16);
        $r = ($color >> 16) & 0xff;
        $g = ($color >> 8) & 0xff;
        $b = $color & 0xff;
        $RGB[] = $r;
        $RGB[] = $g;
        $RGB[] = $b;
        return $RGB;
    }

    /**
     * Convert R,G,B to hex
     * @param int $R
     * @param int $G
     * @param int $B
     * @return string
     */
    public static function RGB_TO_HEX($R, $G, $B)
    {
        $R = self::clamp($R, 0, 255);
        $G = self::clamp($G, 0, 255);
        $B = self::clamp($B, 0, 255);
        return sprintf("%06X", ($R << 16) | ($G << 8) | $B);
    }

    /**
     * Calculate luminance (Photometric/digital ITU-R)
     * @param int $R
     * @param int $G
     * @param int $B
     * @return number 0-1
     */
    public static function RGB_TO_LUMINANCE($R, $G, $B)
    {
        return self::clamp(0.2126 * ($R / 255) + 0.7152 * ($G / 255) + 0.0722 * ($B / 255));
    }

    /**
     * Get a contrast color
     *
     * @param string $dark
     * @param string $light
     * @return string
     */
    public function ContrastColor($dark = '#000000', $light = '#ffffff')
    {
        list($R, $G, $B) = self::HEX_TO_RGB($this->Color());
        $yiq = (($R * 299) + ($G * 587) + ($B * 114)) / 1000;
        return ($yiq >= 128) ? $dark : $light;
    }

    /**
    * Get the color
    *
    * @param string $default
    * @return string
    */
    public function Color($default = '#ffffff')
    {
        if (!$this->value) {
            return $default;
        }
        return $this->value;
    }

    /**
     * Get the red component of this color
     * @return int red-component 0-255
     */
    public function Red()
    {
        list($R, $G, $B) = self::HEX_TO_RGB($this->value);
        return $R;
    }

    /**
     * Get the green component of this color
     * @return int green-component 0-255
     */
    public function Green()
    {
        list($R, $G, $B) = self::HEX_TO_RGB($this->value);
        return $G;
    }

    /**
     * Get the blue component of this color
     * @return int blue-component 0-255
     */
    public function Blue()
    {
        list($R, $G, $B) = self::HEX_TO_RGB($this->value);
        return $B;
    }

    /**
     * Get the color as CSS3 color definition with optional alpha value.
     * Will return "rgba(RED, GREEN, BLUE, OPACITY)"
     * @param number $opacity opacity value from 0-1
     * @return string css3 color definition
     */
    public function CSSColor($opacity = 1)
    {
        list($R, $G, $B) = self::HEX_TO_RGB($this->value);
        $A = self::clamp($opacity);
        return sprintf('rgba(%d,%d,%d,%f)', $R, $G, $B, $A);
    }

    /**
     * Return the luminance of the color
     * @return float the luminance 0-1
     */
    public function Luminance()
    {
        list($R, $G, $B) = self::HEX_TO_RGB($this->value);
        return self::RGB_TO_LUMINANCE($R, $G, $B);
    }

    /**
     * Change the color by the given HSV values and return a new color
     * @param float $hChange hue change
     * @param float $sChange saturation change
     * @param float $vChange brightness change
     * @return Color the new color
     */
    public function AlteredColorHSV($hChange, $sChange, $vChange)
    {
        list($R, $G, $B) = self::HEX_TO_RGB($this->value);
        list($H, $S, $V) = self::RGB_TO_HSV($R, $G, $B);
        list($R, $G, $B) = self::HSV_TO_RGB(
            fmod($H + $hChange + 1, 1),
            self::clamp($S + $sChange),
            self::clamp($V + $vChange)
        );
        $color = new Color();
        $color->setValue(self::RGB_TO_HEX($R, $G, $B));
        return $color;
    }

    /**
     * Blend the color with a background color, with the given opacity level
     * @param float $opacity Opacity level of the current color (between 0 - 1)
     * @param string $background The background color
     * @return Color the new color
     */
    public function Blend($opacity, $background = 'FFFFFF')
    {
        list($R, $G, $B) = self::HEX_TO_RGB($this->value);
        list($bgR, $bgG, $bgB) = self::HEX_TO_RGB($background);
        $transparency = self::clamp(1 - $opacity);
        $R += intval(($bgR - $R) * $transparency);
        $G += intval(($bgG - $G) * $transparency);
        $B += intval(($bgB - $B) * $transparency);
        $color = new Color();
        $color->setValue(self::RGB_TO_HEX($R, $G, $B));
        return $color;
    }

    /**
     * Clamp value to min/max
     * @param float|int $val
     * @param float|int $min, defaults to 0
     * @param float|int $max, defaults to 1
     * @return float|int value clamped to min/max.
     */
    private static function clamp($val, $min = 0, $max = 1)
    {
        return min($max, max($min, $val));
    }
}
