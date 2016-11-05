if (typeof isNumber != 'function')
{
	function isNumber(n)
	{
		return !isNaN(parseFloat(n)) && isFinite(n);
	}
}


if (typeof htmlEntities != 'function')
{
	function htmlEntities(str)
	{
		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;')
				.replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g,'&#39;');
	}
}

if (typeof Trim != 'function')
{
	function Trim(str)
	{
		return str.replace(/^\s+|\s+$/g,"");
	}
}

function AddConditionWizard(colId)
{
	var sel = document.getElementById('new_condition_' + colId);
	var selId = sel.options[sel.selectedIndex].value;
	var value = document.getElementById('col_' + colId).value;

	for ( var i = 0; i < conditionWizard.length; i++)
	{
		if (selId == conditionWizard[i].id)
		{
			var code = conditionWizard[i].code;
			for ( var j = 0; j < 5; j++)
			{
				if (conditionWizard[i]['options_' + (j + 1)] != null
						&& conditionWizard[i]['options_' + (j + 1)] != undefined)
					code = code.replace(new RegExp('@p' + (j + 1) + '@','g'),
							conditionWizard[i]['options_' + (j + 1)][0].id);
				else if (conditionWizard[i]['default_' + (j + 1)] != null
						&& conditionWizard[i]['default_' + (j + 1)] != undefined)
					code = code.replace(new RegExp('@p' + (j + 1) + '@', 'g'),
							conditionWizard[i]['default_' + (j + 1)]);
				else if(conditionWizard[i]['type_' + (j + 1)] == "n")
					code = code.replace(new RegExp('@p' + (j + 1) + '@','g'), '0');
				else
					code = code.replace(new RegExp('@p' + (j + 1) + '@','g'), '');
			}
			if (value != "")
				value += " && ";
			value += code;
			document.getElementById('col_' + colId).value = value;
			break;
		}
	}
	DisplayConditionWizard(colId);
}

function DeleteConditionWizard(colId, cond)
{
	var value = document.getElementById('col_' + colId).value;
	var cmd = value.split(' && ');
	cmd.splice(cond, 1);

	if (cmd.length == 0)
		document.getElementById('col_' + colId).value = "";
	else
	{
		var val = "";
		for ( var i = 0; i < cmd.length; i++)
		{
			if (val != "")
				val += " && ";
			val += cmd[i];
		}
		document.getElementById('col_' + colId).value = val;
	}
	DisplayConditionWizard(colId);
	return false;
}

function TextChangeConditionWizard(colId, cmdId, fieldId)
{
	var value = document.getElementById('col_' + colId).value;

	var cmd = value.split(' && ');

	for ( var j = 0; j < conditionWizard.length; j++)
	{
		if (conditionWizard[j].expCode.test(cmd[cmdId]))
		{
			switch (conditionWizard[j]['type_' + (fieldId + 1)])
			{
				case 'n':
					if (!isNumber(document.getElementById('cwiz_' + colId + '_'
							+ cmdId + '_' + fieldId).value))
					{
						DisplayConditionWizard(colId);
						return;
					}
					break;
				case 't':
					document.getElementById('cwiz_' + colId + '_' + cmdId + '_'
							+ fieldId).value = escape(document
							.getElementById('cwiz_' + colId + '_' + cmdId + '_'
									+ fieldId).value);
					break;					
				default:
					break;
			}

			var match = cmd[cmdId].match(conditionWizard[j].expCode);
			match[fieldId + 1] = document.getElementById('cwiz_' + colId + '_'
					+ cmdId + '_' + fieldId).value;
			var code = conditionWizard[j].code;
			for ( var i = 0; i < 5; i++)
			{
				code = code.replace(new RegExp("@p" + (i + 1) + "@",'g'), match[i + 1]);
			}
			cmd[cmdId] = code;
			break;
		}
	}

	var val = "";
	for ( var i = 0; i < cmd.length; i++)
	{
		if (val != "")
			val += " && ";
		val += cmd[i];
	}
	document.getElementById('col_' + colId).value = val;

	DisplayConditionWizard(colId);
}

function ListChangeConditionWizard(colId, cmdId, fieldId)
{
	var lst = document.getElementById('cwiz_' + colId + '_' + cmdId + '_'
			+ fieldId);
	var value = document.getElementById('col_' + colId).value;

	var cmd = value.split(' && ');

	for ( var j = 0; j < conditionWizard.length; j++)
	{
		if (conditionWizard[j].expCode.test(cmd[cmdId]))
		{
			var match = cmd[cmdId].match(conditionWizard[j].expCode);
			match[fieldId + 1] = lst.options[lst.selectedIndex].value;
			var code = conditionWizard[j].code;
			for ( var i = 0; i < 5; i++)
			{
				code = code.replace(new RegExp("@p" + (i + 1) + "@",'g'), match[i + 1]);
			}
			cmd[cmdId] = code;
			break;
		}
	}

	var val = "";
	for ( var i = 0; i < cmd.length; i++)
	{
		if (val != "")
			val += " && ";
		val += cmd[i];
	}
	document.getElementById('col_' + colId).value = val;

	DisplayConditionWizard(colId);
}

function DisplayConditionWizard(colId)
{
	var value = document.getElementById('col_' + colId).value;
	var dis = document.getElementById('cond_wizard_' + colId);

	var html = "";
	var preHtml = "";
	
	var cmd = value.split(' && ');
	html += "<table class='plainTable'>";
	for ( var i = 0; i < cmd.length; i++)
	{
		if(Trim(cmd[i]) == "")
			continue;
		var foundCode = false;
		for ( var j = 0; j < conditionWizard.length; j++)
		{
			if (conditionWizard[j].expCode.test(cmd[i]))
			{
				html += "<tr class='oddLine'><td width='1%'>[<a href='#' onclick='return DeleteConditionWizard("
						+ colId
						+ ","
						+ i
						+ ");'>Delete</a>]&nbsp;<td>"
						+ conditionWizard[j].name + "</td></tr>";

				var match = cmd[i].match(conditionWizard[j].expCode);

				for (var k = 0; k < 5; k++)
				{
					if (conditionWizard[j]['label_' + (k + 1)] == "")
						continue;
					html += "<tr><td width='1%'><b>"
							+ conditionWizard[j]['label_' + (k + 1)].replace(
									/ /g, '&nbsp;') + ":</b></td>";
					if (conditionWizard[j]['options_' + (k + 1)] != undefined
							&& conditionWizard[j]['options_' + (k + 1)] != null)
					{
						var opts = conditionWizard[j]['options_' + (k + 1)];
						html += "<td><select id='cwiz_" + colId + "_" + i + "_"
								+ k + "' onchange='ListChangeConditionWizard("
								+ colId + "," + i + "," + k + ")'>";
						for ( var l = 0; l < opts.length; l++)
						{
							if (match[k + 1] == opts[l].id)
								html += "<option selected value='" + opts[l].id
										+ "'>" + opts[l].value + "</options>";
							else
								html += "<option value='" + opts[l].id + "'>"
										+ opts[l].value + "</options>";
						}
						html += "</select></td>";
					}
					else
					{
						if (conditionWizard[j]['type_' + (k + 1)] == 't')
							match[k + 1] = unescape(match[k + 1]);
						
						html += "<td><input type='text' id='cwiz_" + colId
								+ "_" + i + "_" + k + "' value='"
								+ htmlEntities(match[k + 1])
								+ "' onchange='TextChangeConditionWizard("
								+ colId + "," + i + "," + k + ")'></td>";
					}
					html += "</tr>";
				}
				foundCode = true;
				break;
			}
		}
		if (!foundCode)
			preHtml = "<span class='errorInfo'>Some logic are not supported. Disable the wizard to be able to really edit this row.</span>";
	}
	html += "</table>";

	html += "<select onchange='AddConditionWizard(" + colId
			+ ");' id='new_condition_" + colId + "'>";
	html += "<option value=''>-- ADD A CONDITION --</option>";
	for ( var i = 0; i < conditionWizard.length; i++)
	{
		html += "<option value='" + conditionWizard[i].id + "'>"
				+ conditionWizard[i].name + "</option>";
	}
	html += "</select>";
	dis.innerHTML = preHtml + html;
}

function initConditionWizard()
{
	for ( var i = 0; i < conditionWizardColumns.length; i++)
		DisplayConditionWizard(conditionWizardColumns[i]);
}

initConditionWizard();