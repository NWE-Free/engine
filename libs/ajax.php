<?php

class Ajax
{
    static $knownFunctions = array();
    static $knownReturnFunctions = array();
    static $stopRefresh = null;
    static $timerFunction = null;
    
    /**
     * Stops the AJAX refresher.
     *
     * @param $functionName specify
     *                      the function to stop
     */
    static function StopRefresh($functionName = null)
    {
        if ($functionName == null) {
            Ajax::$stopRefresh = Ajax::$timerFunction;
        } else {
            Ajax::$stopRefresh = $functionName;
        }
    }
    
    /**
     * Registers a function which should modify the DOM content.
     *
     * @param $functionName  string
     * @param $domIdToUpdate string
     *                       ID of the DOM to modify
     */
    static function RegisterFunction($functionName, $domIdToUpdate = null, $delay = null)
    {
        Ajax::LinkFunction($functionName, "AjaxCallback", $delay);
        Ajax::$knownFunctions[$functionName] = $domIdToUpdate;
    }
    
    static private function LinkFunction($functionName, $callBack = "AjaxCallback", $delay = null)
    {
        global $content;
        if (!function_exists($functionName)) {
            throw new Exception("The function $functionName doesn't exists.");
        }
        Ajax::IncludeLib();
        $d = new ReflectionFunction($functionName);
        $params = $d->getParameters();
        
        if (count($params) == 0) {
            if ($callBack != null && $delay != null) {
                $content['footerJS'] .= "var {$functionName}_timeout = null;\n";
                $content['footerJS'] .= "function $functionName() { if ({$functionName}_timeout != null) { clearTimeout({$functionName}_timeout); } {$functionName}_timeout = setTimeout('do_{$functionName}()',$delay); }\n";
                $content['footerJS'] .= "function do_{$functionName}() { {$functionName}_timeout = null; \$.ajax({type:'POST',data:{AJAX:'CALLBACK',func:'$functionName'},";
                $content['footerJS'] .= "success:$callBack,dataType:'text'}); }\n";
            } else if ($callBack != null) {
                $content['footerJS'] .= "function $functionName() { \$.ajax({type:'POST',data:{AJAX:'CALLBACK',func:'$functionName'},";
                $content['footerJS'] .= "success:$callBack,dataType:'text'}); }\n";
            } else {
                $content['footerJS'] .= "function $functionName() {resultData=null; \$.ajax({type:'POST',async: false,";
                $content['footerJS'] .= "data:{AJAX:'CALLBACK',func:'$functionName'},";
                $content['footerJS'] .= "success:function (r) { resultData = r; },dataType:'text'}); return $.parseJSON(resultData);}\n";
            }
        } else {
            $pNames = array();
            $pVals = array();
            foreach ($params as $p) {
                $pNames[] = $p->name;
                $pVals[] = $p->name . ":" . $p->name;
            }
            if ($callBack != null && $delay != null) {
                $content['footerJS'] .= "var {$functionName}_timeout = null;\n";
                $content['footerJS'] .= "function $functionName(" . implode(",",
                        $pNames) . ") { if ({$functionName}_timeout != null) ";
                $content['footerJS'] .= "{ clearTimeout({$functionName}_timeout); } {$functionName}_timeout = setTimeout('do_{$functionName}(";
                $isFirst = true;
                foreach ($pNames as $p) {
                    if (!$isFirst) {
                        $content['footerJS'] .= ",";
                    }
                    $content['footerJS'] .= "eval(unescape(\"'+escape(jsSerializer($p))+'\"))";
                    $isFirst = false;
                }
                $content['footerJS'] .= ");',$delay); }\n";
                $content['footerJS'] .= "function do_$functionName(" . implode(",",
                        $pNames) . ") { \$.ajax({type:'POST',";
                $content['footerJS'] .= "data:{AJAX:'CALLBACK',func:'$functionName'," . implode(",", $pVals) . "},";
                $content['footerJS'] .= "success:$callBack,dataType:'text'}); }\n";
            } else if ($callBack != null) {
                $content['footerJS'] .= "function $functionName(" . implode(",", $pNames) . ") { \$.ajax({type:'POST',";
                $content['footerJS'] .= "data:{AJAX:'CALLBACK',func:'$functionName'," . implode(",", $pVals) . "},";
                $content['footerJS'] .= "success:$callBack,dataType:'text'}); }\n";
            } else {
                $content['footerJS'] .= "function $functionName(" . implode(",", $pNames) . ") ";
                $content['footerJS'] .= "{resultData=null; \$.ajax({type:'POST',async: false,data:{AJAX:'CALLBACK',func:'$functionName',";
                $content['footerJS'] .= implode(",",
                        $pVals) . "},success:function (r) { resultData = r; },dataType:'text'}); return $.parseJSON(resultData);}\n";
            }
        }
    }
    
    /**
     * Includes JQuery to the page
     */
    static function IncludeLib()
    {
        global $content, $jQueryLib, $webBaseDir;
        static $isJqueryIncluded = false;
        
        if ($isJqueryIncluded) {
            return;
        }
        
        $isJqueryIncluded = true;
        
        if (!isset($jQueryLib)) {
            $content['footerScript'] .= "<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'></script>";
        } else {
            $content['footerScript'] .= "<script src='$jQueryLib'></script>";
        }
        $content['footerScript'] .= "<script src='{$webBaseDir}js/ajax_helper.js'></script>";
    }
    
    /**
     * Registers a PHP function to be used synchronously on JS
     *
     * @param $functionName string
     */
    static function RegisterReturnFunction($functionName)
    {
        Ajax::LinkFunction($functionName, null);
        Ajax::$knownReturnFunctions[$functionName] = true;
    }
    
    /**
     * Let the PHP code issue updates on various DOM parts.
     *
     * @param $domId      string
     * @param $newContent string
     */
    static function AssignHtml($domId, $newContent)
    {
        global $content;
        if (!array_key_exists('dom_update', $content)) {
            $content['dom_update'] = array();
        }
        $content['dom_update'][$domId] = $newContent;
    }
    
    /**
     * Setups a timer which will invoke periodically a JS/PHP function.
     *
     * @param $functionName  string
     * @param $domIdToUpdate string
     * @param $timeout       integer
     *
     * @throws Exception in case the functionName is not a defined PHP function.
     */
    static function UpdateTimer($functionName, $domIdToUpdate = null, $timeout = 5000)
    {
        global $content;
        if (!function_exists($functionName)) {
            throw new Exception("The function $functionName doesn't exists.");
        }
        Ajax::IncludeLib();
        $content['footerJS'] .= "function $functionName() { \$.ajax({type:'POST',data:{AJAX:'CALLBACK',func:'$functionName'},success:AjaxCallback,dataType:'text'}); timer_$functionName = setTimeout('$functionName()', $timeout); }\n";
        $content['footerJS'] .= "var timer_$functionName = setTimeout('$functionName();', $timeout);\n";
        Ajax::$timerFunction = $functionName;
        Ajax::$knownFunctions[$functionName] = $domIdToUpdate;
    }
    
    /**
     * Returns true if the page has been loaded via AJAX
     *
     * @return boolean
     */
    static function IsAjaxCallback()
    {
        if (isset($_POST["AJAX"]) && $_POST["AJAX"] == "CALLBACK") {
            return true;
        }
        return false;
    }
    
    /**
     * Used by the engine to call back the PHP function from JS.
     *
     * @param $functionName string
     */
    static function RunRegisteredFunction($functionName)
    {
        global $content;
        
        if (!array_key_exists($functionName, Ajax::$knownFunctions) && !array_key_exists($functionName,
                Ajax::$knownReturnFunctions)
        ) {
            return;
        }
        ob_end_clean();
        ob_start();
        
        $d = new ReflectionFunction($functionName);
        $params = $d->getParameters();
        $ret = "";
        
        if (count($params) == 0) {
            if (array_key_exists($functionName, Ajax::$knownReturnFunctions)) {
                eval("\$ret={$functionName}();");
            } else {
                eval($functionName . "();");
            }
        } else {
            $code = "$functionName(";
            $isFirst = true;
            foreach ($params as $p) {
                if (!$isFirst) {
                    $code .= ",";
                }
                $code .= "\$_POST['{$p->name}']";
                $isFirst = false;
            }
            $code .= ");";
            
            if (array_key_exists($functionName, Ajax::$knownReturnFunctions)) {
                $code = "\$ret=$code";
            }
            eval($code);
        }
        $out = ob_get_clean();
        header('Content-type: application/json');
        
        if (array_key_exists($functionName, Ajax::$knownReturnFunctions)) {
            echo Ajax::ToJSON($ret);
        } else {
            echo "[";
            $isFirst = true;
            if (Ajax::$stopRefresh != null) {
                echo "{\"stopRefresh\":\"" . Ajax::$stopRefresh . "\"}";
                $isFirst = false;
            }
            if (array_key_exists('dom_update', $content)) {
                foreach ($content['dom_update'] as $dom => $val) {
                    if (!$isFirst) {
                        echo ",";
                    }
                    echo "{\"dom\":\"" . $dom . "\",\"value\":" . Ajax::ToJSON($val) . "}\n";
                    $isFirst = false;
                }
            }
            if ($out != "" && Ajax::$knownFunctions[$functionName] != null) {
                if (!$isFirst) {
                    echo ",";
                }
                echo "{\"dom\":\"" . Ajax::$knownFunctions[$functionName] . "\",\"value\":" . Ajax::ToJSON($out) . "}\n";
                $isFirst = false;
            }
            echo "]";
        }
    }
    
    /**
     * Transforms PHP data (string, numbers, arrays) into a JSON format.
     *
     * @param $data mixed
     *
     * @return string the JSON representation
     */
    static function ToJSON($data)
    {
        if (is_numeric($data)) {
            return $data;
        } else if (is_string($data)) {
            return "\"" . str_replace(array("\\", "\n", "\"", "\r"), array("\\\\", "\\n", "\\\"", "\\r"), $data) . "\"";
        } else if (is_array($data)) {
            // Checks if the array is a normal numbered array
            if (implode(",", array_keys($data)) == implode(",", range(0, count($data) - 1))) {
                $result = "[";
                $isFirst = true;
                foreach ($data as $val) {
                    if (!$isFirst) {
                        $result .= ",";
                    }
                    $result .= Ajax::ToJSON($val);
                    $isFirst = false;
                }
                return $result . "]";
            } // Value / Key
            else {
                $result = "{";
                $isFirst = true;
                foreach ($data as $key => $val) {
                    if (!$isFirst) {
                        $result .= ",";
                    }
                    $result .= "\"$key\":" . Ajax::ToJSON($val);
                    $isFirst = false;
                }
                return $result . "}";
            }
        }
    }
    
    /**
     * Shows a standard button with label and glues a JS code to it.
     *
     * @param $label      string
     * @param $jsFunction string
     * @param $return     bool
     *                    set it to true to be able to get back the HTML produced.
     */
    static function Button($label, $jsFunction, $return = false)
    {
        global $baseDir, $template;
        $res = "";
        
        $id = MakeId("btn_", $label);
        $label = Translate($label);
        
        global $loadedTemplateFuncs, $baseDir, $template;
        if (!$loadedTemplateFuncs) {
            if (file_exists("$baseDir/templates/$template/functions.php")) {
                include "$baseDir/templates/$template/functions.php";
            }
            $loadedTemplateFuncs = true;
        }
        
        if (function_exists("TemplateLinkButton")) {
            $res = TemplateLinkButton($id, $label, "#", $jsFunction . ";return false;", null, true);
        } else {
            $res .= "<a href=\"#\"";
            $res .= " onclick=\"$jsFunction;return false\"";
            $res .= " id='$id' class='linkButton'>$label</a>";
        }
        if ($return) {
            return $res;
        }
        echo $res;
    }
    
    static function EnterSubmit($formName)
    {
        global $content;
        Ajax::IncludeLib();
        $content['footerScript'] .= "<script>\$(':input').keypress(function(e) { if(e.which == 13) { document.forms['$formName'].submit(); } });</script>";
    }
}