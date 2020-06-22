function AjaxCallback(data)
{
    data = $.parseJSON(data);
    for ( var i = 0; i < data.length; i++)
    {
        if (data[i].stopRefresh != undefined && data[i].stopRefresh != null)
            eval("clearTimeout(timer_" + data[i].stopRefresh + ");");
        else
            $("#" + data[i].dom).html(data[i].value);
    }
}

if (typeof getCookie != 'function')
{
    function getCookie(cookieName)
    {
        var cookies = document.cookie.split(";");
        for ( var i = 0; i < cookies.length; i++)
        {
            var name = cookies[i].substr(0, cookies[i].indexOf("="));
            var value = cookies[i].substr(cookies[i].indexOf("=") + 1);
            name = name.replace(/^\s+|\s+$/g, "");
            if (name == cookieName)
            {
                return decodeURI(value);
            }
        }
        return null;
    }
}

if (typeof setCookie != 'function')
{
    function setCookie(cookieName, value, exdays)
    {
        var exdate = new Date();
        exdate.setDate(exdate.getDate() + exdays);
        var c_value = encodeURI(value)
            + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString());
        document.cookie = cookieName + "=" + c_value;
    }
}

if (typeof jsSerializer != 'function')
{
    function jsSerializer(obj)
    {
        var t = (typeof obj);
        if (t == "string")
        {
            return "decodeURI('" + escape(obj) + "')";
        }
        else if (t == "number")
        {
            return "" + obj;
        }
        else if (obj.constructor == Array)
        {
            var result = "[";
            var isFirst = true;
            for ( var i = 0; i < obj.length; i++)
            {
                if (!isFirst)
                    result += ",";
                result += jsSerializer(obj[i]);
                isFirst = false;
            }
            result += "]";
        }
        else
        {
            var result = "{";
            var isFirst = true;
            for ( var i in obj)
            {
                if (!isFirst)
                    result += ",";
                result += i + ":" + jsSerializer(obj[i]);
                isFirst = false;
            }
            result += "}";
        }
        return result;
    }
}

if (typeof jsDeserializer != 'function')
{
    function jsDeserializer(objStr)
    {
        var ret = new Array();
        eval("ret=" + objStr);
        return ret;
    }
}

if (typeof replaceAt != 'function')
{
    function replaceAt(str, index, char)
    {
        return str.substr(0, index) + char + str.substr(index + char.length);
    }
}

if (typeof pad != 'function')
{
    function pad(str, len, pad)
    {
        var res = str;
        while (res.length < len)
            res += pad;
        return res;
    }
}
