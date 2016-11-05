<?php

/**
 * Generate the HTML for the captcha
 *
 * @return string returns the HTML for the captcha
 */
function ModuleCaptchaGenerate()
{
    return "<table border='0' width='100%'><tr><td><img id='img_captcha' src='modules/simple_image_captcha/captcha.php?getcaptcha=true' /> <a href='#' onclick='document.getElementById(\"img_captcha\").src=\"modules/simple_image_captcha/captcha.php?getcaptcha=true&v=\"+escape((new Date()).toGMTString());return false;'><img src='modules/simple_image_captcha/refresh.png' border='0' width='25' height='25'></a></td></tr><tr><td>" .
    Translate("Write down the %d numbers that appear in the image showed above.",
        GetConfigValue("captchaLength")) . "</td></tr><tr><td><input type='text' name='captcha' style='width: 100%;'></td></tr></table>";
}

/**
 * Checks if the captcha is correct by comparing what is stored in the session.
 *
 * @return boolean true if the captcha is correct.
 */
function ModuleCaptchaCheck()
{
    $value = "";
    if (isset($_GET["captcha"])) {
        $value = $_GET["captcha"];
    } else if (isset($_POST["captcha"])) {
        $value = $_POST["captcha"];
    }
    return ($_SESSION["captcha"] == $value);
}

/**
 * Generates the image of the captcha and store the generated number in the
 * session.
 */
if (isset($_GET["getcaptcha"]) && $_GET["getcaptcha"] == "true") {
    include_once "../../config/config.php";
    include_once "../../libs/db.php";
    include_once "../../libs/common.php";
    $db = new Database($dbhost, $dbuser, $dbpass, $dbname);
    /**
     * Allows to use the session array.
     */
    session_start();
    
    /**
     * The number as simple pixel font.
     * Allows to draw them as we want.
     */
    $font["0"] = array(".***..", "*...*.", "*.*.*.", "*...*.", "*...*.", ".***..");
    $font["1"] = array(".**...", "*.*...", "..*...", "..*...", "..*...", "*****.");
    $font["2"] = array(".****.", "*...*.", "...*..", "..*...", ".*....", "*****.");
    $font["3"] = array(".***..", "*...*.", "...*..", "....*.", "*...*.", ".***..");
    $font["4"] = array("..**..", ".*.*..", "*..*..", "*****.", "...*..", "...*..");
    $font["5"] = array("*****.", "*.....", "****..", "....*.", "....*.", "****..");
    $font["6"] = array(".***..", "*.....", "****..", "*...*.", "*...*.", "****..");
    $font["7"] = array("*****.", "....*.", "...*..", "..*...", ".*....", "*.....");
    $font["8"] = array(".***..", "*...*.", ".***..", "*...*.", "*...*.", ".***..");
    $font["9"] = array(".***..", "*...*.", "*****.", "....*.", "....*.", "****..");
    
    $width = intval(GetConfigValue("captchaLength")) * 18 + 10;
    
    /**
     * Create an image of $width pixel width per 25 pixel height.
     */
    $img = imagecreate($width, 25);
    /**
     * Allocate the white color we will use as background.
     *
     * @var unknown_type
     */
    $white = imagecolorallocate($img, 255, 255, 255);
    /**
     * Clear the image using the white color.
     */
    imagefilledrectangle($img, 0, 0, $width, 25, $white);
    /**
     * Allocate the black color we will use to draw the image.
     */
    $black = imagecolorallocate($img, 0, 0, 0);
    
    /**
     * Creates a memory array for the whole string.
     */
    $array = array();
    for ($x = 0; $x < 30; $x++) {
        $array[$x] = "......";
    }
    
    $value = "";
    for ($i = 0; $i < intval(GetConfigValue("captchaLength")); $i++) {
        $c = chr(rand(0, 9) + ord("0"));
        $value .= $c;
        for ($x = 0; $x < 6; $x++) {
            for ($y = 0; $y < 6; $y++) {
                $array[$x + $i * 6][$y] = $font[$c][$y][$x];
            }
        }
    }
    
    /**
     * Now start to draw on the image
     */
    $angle = rand(0, 1000) / 300.0;
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < 25; $y++) {
            $a = $x - 5;
            $b = round($y - 3 + sin($x / 6.0 + $angle) * floatval(GetConfigValue("captchaDeform")));
            $a = round($a / 3);
            $b = round($b / 3);
            if (!($a < 0 || $a >= intval(GetConfigValue("captchaLength")) * 6 || $b < 0 || $b >= 6)) {
                if ($array[$a][$b] == "*") {
                    imagesetpixel($img, $x, $y + 1, $black);
                }
            }
            
            if (GetConfigValue("captchaNoise") == "yes") {
                if (rand(0, 20) == 0) {
                    imagesetpixel($img, $x, $y, $black);
                } else if (rand(0, 10) == 0) {
                    imagesetpixel($img, $x, $y, $white);
                }
            }
        }
    }
    /**
     * Create a black border around.
     */
    imagerectangle($img, 0, 0, $width - 1, 24, $black);
    
    /**
     * Store the random number in the session array.
     */
    $_SESSION['captcha'] = $value;
    
    /**
     * Return the image to the browser.
     */
    header("Content-type: image/png");
    header("Expires: now");
    imagepng($img);
    imagedestroy($img);
}
