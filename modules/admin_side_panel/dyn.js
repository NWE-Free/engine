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
				return unescape(value);
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
		var c_value = escape(value)
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
			return "unescape('" + escape(obj) + "')";
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

function expandContractSideMenuGroup(grp, position)
{
	var div = document.getElementById("adminSideGroup" + grp);

	var c = getCookie("settings");
	var data = new Object();
	if (c != null)
		data = jsDeserializer(c);

	if (position == 'open'
			|| ((position == null || position == undefined) && div.style.position == "absolute"))
	{
		div.style.position = "";
		div.style.visibility = "visible";
		div.style.left = "";
		div.style.height = "";
		document.getElementById("adminSideImage" + grp).src = 'images/minus.png';
		data['side_grp_' + grp] = 'open';
	}
	else
	{
		div.style.position = "absolute";
		div.style.visibility = "hidden";
		div.style.left = "0px";
		div.style.height = "0px";
		document.getElementById("adminSideImage" + grp).src = 'images/plus.png';
		data['side_grp_' + grp] = 'closed';
	}
	setCookie("settings", jsSerializer(data), 30);
}

function expandContractSideMenu(position)
{
	var div = document.getElementById('adminSideMenuContent');

	var c = getCookie("settings");
	var data = new Object();
	if (c != null)
		data = jsDeserializer(c);

	if (position == 'open'
			|| ((position == null || position == undefined) && div.style.position == "absolute"))
	{
		div.style.position = "";
		div.style.visibility = "visible";
		div.style.width = "180px;";
		div.style.left = "";
		div.style.height = "";
		document.getElementById('adminSideSeparator').src = 'images/side_contract.png';
		data['adm_side_menu'] = 'open';
	}
	else
	{
		div.style.position = "absolute";
		div.style.visibility = "hidden";
		div.style.width = "0px;";
		div.style.left = "0px";
		div.style.height = "0px";
		document.getElementById('adminSideSeparator').src = 'images/side_expand.png';
		data['adm_side_menu'] = 'closed';
	}
	setCookie("settings", jsSerializer(data), 30);
}

function initAdminSidePanel()
{
	var c = getCookie("settings");
	var data = new Object();
	if (c != null)
		data = jsDeserializer(c);

	if (data['adm_side_menu'] != null && data['adm_side_menu'] != undefined)
		expandContractSideMenu(data['adm_side_menu']);
	if (data['side_grp_Links'] != null && data['side_grp_Links'] != undefined)
		expandContractSideMenuGroup("Links", data['side_grp_Links']);
	if (data['side_grp_Keys'] != null && data['side_grp_Keys'] != undefined)
		expandContractSideMenuGroup("Keys", data['side_grp_Keys']);
	if (data['side_grp_Tables'] != null && data['side_grp_Tables'] != undefined)
		expandContractSideMenuGroup("Tables", data['side_grp_Tables']);
}

initAdminSidePanel();