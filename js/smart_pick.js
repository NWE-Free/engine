function smartPickClick(fieldId)
{
    var list = document.getElementById('choice_' + fieldId);
    document.getElementById('type_' + fieldId).value = list.options[list.selectedIndex].text;
    document.getElementById(fieldId).value = list.options[list.selectedIndex].value;
}

var smartTimeout = null;
function smartKeyPress(fieldId)
{
    if (smartTimeout != null)
    {
        clearTimeout(smartTimeout);
        smartTimeout = null;
    }
    document.getElementById(fieldId).value = "";
    smartTimeout = setTimeout("smartLookup('" + fieldId + "');", 500);
}

function smartLookup(fieldId)
{
    smartTimeout = null;
    var dataToSend = document.getElementById('type_' + fieldId).value;

    var http = null;

    try
    {
        http = new XMLHttpRequest();
    }
    catch (e)
    {
        try
        {
            http = new ActiveXObject("Microsoft.XMLHTTP");
        }
        catch (e2)
        {
            http = false;
        }
    }
    if (!http)
    {
        return false;
    }

    var now = new Date();
    var url = 'js/smart_picker_callback.php?f=' + fieldId + '&q='
        + escape(dataToSend).replace(/\+/g, "%2B") + '&r='
        + escape(now.toGMTString());
    http.open("GET", url);
    http.onreadystatechange = function()
    {
        if (http.readyState == 4)
        {
            var result = eval(http.responseText);
            var list = document.getElementById('choice_' + fieldId);
            while (list.length > 0)
                list.remove(0);
            for ( var i = 0; i < result.length; i++)
            {
                list.options[i] = new Option(result[i].text, result[i].id);
            }
            if (list.length == 1
                && list.options[0].value != document
                    .getElementById(fieldId).value)
            {
                list.selectedIndex = 0;
                smartPickClick(fieldId);
            }
        }
    }

    http.send(null);
    return false;
}

var smartBlurTimeout = null;
var smartBlurField = null;
function smartFocus(fieldId)
{
    if (smartBlurTimeout != null)
    {
        clearTimeout(smartBlurTimeout);
        if (smartBlurField != fieldId)
            doSmartBlur(smartBlurField);
    }
    document.getElementById("row_choice_" + fieldId).style.visibility = 'visible';
    document.getElementById("row_choice_" + fieldId).style.position = '';
}

function smartBlur(fieldId)
{
    if (smartBlurTimeout != null)
    {
        clearTimeout(smartBlurTimeout);
        smartBlurTimeout = null;
        if (smartBlurField != fieldId)
            doSmartBlur(smartBlurField);
    }

    smartBlurField = fieldId;
    smartBlurTimeout = setTimeout("doSmartBlur('" + fieldId + "');", 100);
}

function doSmartBlur(fieldId)
{
    smartBlurTimeout = null;
    smartBlurField = null;
    document.getElementById("row_choice_" + fieldId).style.visibility = 'hidden';
    document.getElementById("row_choice_" + fieldId).style.position = 'absolute';
}
