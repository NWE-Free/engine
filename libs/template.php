<?php

$loadedTemplateFuncs = false;

/**
 * Display the page with the template.
 *
 * @param $stats       string
 * @param $sideMenu    string
 * @param $mainContent string
 */
function ShowTemplate()
{
    global $content, $baseDir, $template, $gameName, $webBaseDir;
    
    include "$baseDir/templates/$template/main.php";
}

/**
 * Starts a table
 *
 * @param $title string
 */
function TableHeader($title = "", $translate = true, $expandable = false)
{
    global $loadedTemplateFuncs, $baseDir, $template, $content, $webBaseDir;
    static $headerId = 1;
    
    if ($translate) {
        $title = Translate($title);
    }
    
    if ($expandable === true) {
        if (strpos($content['footerScript'], "content_table.js") === false) {
            $content['footerScript'] .= "<script src='{$webBaseDir}js/content_table.js'></script>";
        }
    }
    
    if (!$loadedTemplateFuncs) {
        if (file_exists("$baseDir/templates/$template/functions.php")) {
            include "$baseDir/templates/$template/functions.php";
        }
        $loadedTemplateFuncs = true;
    }
    
    if (function_exists("TemplateTableHeader")) {
        TemplateTableHeader($title, $headerId, $expandable);
    } else {
        echo "<div>";
        if ($expandable === true) {
            echo "<div class='windowTitle' style='cursor: pointer;' onclick='expandHeader($headerId);'>$title <img src='images/contracted.png' align='right' id='content_img_$headerId'></div>";
            echo "<div class='windowContent' style='height: 0px; overflow: hidden;' id='content_table_$headerId'>";
        } else {
            echo "<div class='windowTitle'>$title</div>";
            echo "<div class='windowContent'>";
        }
    }
    $headerId++;
}

/**
 * Ends a table
 */
function TableFooter()
{
    global $loadedTemplateFuncs, $baseDir, $template;
    if (!$loadedTemplateFuncs) {
        if (file_exists("$baseDir/templates/$template/functions.php")) {
            include "$baseDir/templates/$template/functions.php";
        }
        $loadedTemplateFuncs = true;
    }
    
    if (function_exists("TemplateTableFooter")) {
        TemplateTableFooter();
    } else {
        echo "</div>";
        echo "</div>";
    }
}

/**
 * Creates a submit button
 *
 * @param $label string
 *               label displayed on the button
 * @param $form  integer/string
 *               form to activate inside the page
 */
function SubmitButton($label, $form = 0)
{
    if (is_integer($form)) {
        LinkButton($label, "#", "document.forms[$form].submit();return false;");
    } else {
        LinkButton($label, "#", "document.forms['$form'].submit();return false;");
    }
}

/**
 * Creates a link button
 *
 * @param $label      string
 * @param $url        string
 * @param $javascript string
 * @param $target     string
 * @param $return     bool
 *                    if set to true it will return the HTML instead of echoing it.
 * @param $enabled    bool
 *                    defines if the button can be clicked or not.
 *
 * @return string
 */
function LinkButton($label, $url, $javascript = null, $target = null, $return = false, $enabled = true)
{
    global $baseDir, $template;
    $res = "";
    
    $id = MakeId("btn_", $label);
    $label = str_replace(" ", "&nbsp;", Translate($label));
    
    global $loadedTemplateFuncs, $baseDir, $template;
    if (!$loadedTemplateFuncs) {
        if (file_exists("$baseDir/templates/$template/functions.php")) {
            include "$baseDir/templates/$template/functions.php";
        }
        $loadedTemplateFuncs = true;
    }
    
    if (function_exists("TemplateLinkButton")) {
        $res = TemplateLinkButton($id, $label, $url, $javascript, $target, $enabled);
    } else {
        if (!$enabled) {
            $res .= "<span id='$id' class='disabledLinkButton'>$label</span>";
        } else {
            $res .= "<a href=\"$url\"";
            if ($javascript != null) {
                $res .= " onclick=\"$javascript\"";
            }
            if ($target != null) {
                $res .= " target=\"$target\"";
            }
            $res .= " id='$id' class='linkButton'>$label</a>";
        }
    }
    if (!$return) {
        echo $res;
    }
    return $res;
}

/**
 * Starts the button area
 *
 * @param $return bool
 *                if set to true it will return the HTML instead of echoing it.
 *
 * @return string
 */
function ButtonArea($return = false)
{
    global $loadedTemplateFuncs, $baseDir, $template;
    if (!$loadedTemplateFuncs) {
        if (file_exists("$baseDir/templates/$template/functions.php")) {
            include "$baseDir/templates/$template/functions.php";
        }
        $loadedTemplateFuncs = true;
    }
    
    if (function_exists("TemplateButtonArea")) {
        if ($return) {
            return TemplateButtonArea(true);
        } else {
            TemplateButtonArea();
        }
    } else {
        if ($return) {
            return "<div class='buttonArea'>";
        } else {
            echo "<div class='buttonArea'>";
        }
    }
}

/**
 * Ends the button area
 *
 * @param $return bool
 *                if set to true it will return the HTML instead of echoing it.
 *
 * @return string
 */
function EndButtonArea($return = false)
{
    global $loadedTemplateFuncs, $baseDir, $template;
    if (!$loadedTemplateFuncs) {
        if (file_exists("$baseDir/templates/$template/functions.php")) {
            include "$baseDir/templates/$template/functions.php";
        }
        $loadedTemplateFuncs = true;
    }
    
    if (function_exists("TemplateEndButtonArea")) {
        if ($return) {
            return TemplateEndButtonArea(true);
        } else {
            TemplateEndButtonArea();
        }
    } else {
        if ($return) {
            return "</div>";
        } else {
            echo "</div>";
        }
    }
}

/**
 * Display an error message.
 *
 * @param $message   string
 *                   message to display (after translation)
 * @param $translate bool
 *                   defines if the message needs to be translated or not.
 */
function ErrorMessage($message, $translate = true)
{
    if ($translate) {
        $message = Translate($message);
    }
    
    global $loadedTemplateFuncs, $baseDir, $template;
    if (!$loadedTemplateFuncs) {
        if (file_exists("$baseDir/templates/$template/functions.php")) {
            include "$baseDir/templates/$template/functions.php";
        }
        $loadedTemplateFuncs = true;
    }
    
    if (function_exists("TemplateErrorMessage")) {
        TemplateErrorMessage($message);
    } else {
        echo "<span class='errorInfo'>$message</span>";
    }
}

/**
 * Display an result message.
 *
 * @param $message   string
 *                   message to display (after translation)
 * @param $translate bool
 *                   defines if the message needs to be translated or not.
 */
function ResultMessage($message, $translate = true)
{
    if ($translate) {
        $message = Translate($message);
    }
    
    global $loadedTemplateFuncs, $baseDir, $template;
    if (!$loadedTemplateFuncs) {
        if (file_exists("$baseDir/templates/$template/functions.php")) {
            include "$baseDir/templates/$template/functions.php";
        }
        $loadedTemplateFuncs = true;
    }
    
    if (function_exists("TemplateResultMessage")) {
        TemplateResultMessage($message);
    } else {
        echo "<span class='resultInfo'>$message</span>";
    }
}

function VerifyIt()
{
    global $engineLicenseKey;
    
    echo "<a href='http://nwe.funmayhem.com/verify.php?l=" . substr($engineLicenseKey, 0, 10);
    echo "&sip=" . $_SERVER['SERVER_ADDR'] . "' target='_blank'><img src='http://nwe.funmayhem.com/verify.php?image=button&l=";
    echo substr($engineLicenseKey, 0, 10) . "' border='0'></a>";
}

/**
 * Displays the game title by using images of each characters one after the
 * other.
 */
function ImageGameTitle()
{
    global $gameName, $webBaseDir;
    
    $name = strtolower($gameName);
    $fontName = GetConfigValue("titleFont", "admin_font_change");
    if ($fontName == null) {
        $fontName = "old";
    }
    
    $allowed = "abcdefghijklmnopqrstuvwxyz";
    for ($i = 0; $i < strlen($name); $i++) {
        if (substr($name, $i, 1) == " ") {
            echo "<img src='{$webBaseDir}images/separator.gif' width='25'>";
        }
        if (strpos($allowed, substr($name, $i, 1)) === false) {
            continue;
        }
        echo "<img src='{$webBaseDir}images/fonts/$fontName/" . substr($name, $i, 1) . ".png'>";
    }
}

function RichEditor($elementName, $defaultValue = "")
{
    global $modules, $baseDir;
    
    foreach ($modules as $module) {
        if (file_exists("$baseDir/modules/$module/rich_editor.php")) {
            include_once "$baseDir/modules/$module/rich_editor.php";
            return RichEditorField($elementName, $defaultValue);
        }
    }
    
    return "<textarea name='$elementName' rows='10'>" . htmlentities($defaultValue) . "</textarea>";
}