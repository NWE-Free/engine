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
				.replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g,
						'&#39;');
	}
}

function AddActionWizard(colId)
{
	var sel = document.getElementById('new_condition_' + colId);
	var selId = sel.options[sel.selectedIndex].value;
	var value = document.getElementById('col_' + colId).value;

	for ( var i = 0; i < actionWizard.length; i++)
	{
		if (selId == actionWizard[i].id)
		{
			var code = actionWizard[i].code;
			for ( var j = 0; j < 5; j++)
			{
				if (actionWizard[i]['options_' + (j + 1)] != null
						&& actionWizard[i]['options_' + (j + 1)] != undefined)
					code = code.replace(new RegExp('@p' + (j + 1) + '@', 'g'),
							actionWizard[i]['options_' + (j + 1)][0].id);
				else if (actionWizard[i]['default_' + (j + 1)] != null
						&& actionWizard[i]['default_' + (j + 1)] != undefined)
					code = code.replace(new RegExp('@p' + (j + 1) + '@', 'g'),
							actionWizard[i]['default_' + (j + 1)]);
				else if (actionWizard[i]['type_' + (j + 1)] == "n")
					code = code.replace(new RegExp('@p' + (j + 1) + '@', 'g'),
							'0');
				else
					code = code.replace(new RegExp('@p' + (j + 1) + '@', 'g'),
							'');
			}
			value += code + ";";
			document.getElementById('col_' + colId).value = value;
			break;
		}
	}
	DisplayActionWizard(colId);
}

function DeleteActionWizard(colId, cond)
{
	var value = document.getElementById('col_' + colId).value;
	var cmd = value.split(';');
	cmd.splice(cond, 1);

	if (cmd.length == 0)
		document.getElementById('col_' + colId).value = "";
	else
	{
		var val = "";
		for ( var i = 0; i < cmd.length; i++)
		{
			if (cmd[i] == "")
				continue;
			val += cmd[i] + ";";
		}
		document.getElementById('col_' + colId).value = val;
	}
	DisplayActionWizard(colId);
	return false;
}

function TextChangeActionWizard(colId, cmdId, fieldId)
{
	var value = document.getElementById('col_' + colId).value;

	var cmd = value.split(';');

	for ( var j = 0; j < actionWizard.length; j++)
	{
		if (actionWizard[j].expCode.test(cmd[cmdId]))
		{
			switch (actionWizard[j]['type_' + (fieldId + 1)])
			{
				case 'n':
					if (!isNumber(document.getElementById('awiz_' + colId + '_'
							+ cmdId + '_' + fieldId).value))
					{
						DisplayActionWizard(colId);
						return;
					}
					break;
				case 't':
					document.getElementById('awiz_' + colId + '_' + cmdId + '_'
							+ fieldId).value = escape(document
							.getElementById('awiz_' + colId + '_' + cmdId + '_'
									+ fieldId).value);
					break;
				default:
					break;
			}

			var match = cmd[cmdId].match(actionWizard[j].expCode);
			match[fieldId + 1] = document.getElementById('awiz_' + colId + '_'
					+ cmdId + '_' + fieldId).value;
			var code = actionWizard[j].code;
			for ( var i = 0; i < 5; i++)
			{
				code = code.replace(new RegExp('@p' + (i + 1) + '@', 'g'),
						match[i + 1]);
			}
			cmd[cmdId] = code;
			break;
		}
	}

	var val = "";
	for ( var i = 0; i < cmd.length; i++)
	{
		if (cmd[i] == "")
			continue;
		val += cmd[i] + ";";
	}
	document.getElementById('col_' + colId).value = val;

	DisplayActionWizard(colId);
}

function ListChangeActionWizard(colId, cmdId, fieldId)
{
	var lst = document.getElementById('awiz_' + colId + '_' + cmdId + '_'
			+ fieldId);
	var value = document.getElementById('col_' + colId).value;

	var cmd = value.split(';');

	for ( var j = 0; j < actionWizard.length; j++)
	{
		if (actionWizard[j].expCode.test(cmd[cmdId]))
		{
			var match = cmd[cmdId].match(actionWizard[j].expCode);
			match[fieldId + 1] = lst.options[lst.selectedIndex].value;
			var code = actionWizard[j].code;
			for ( var i = 0; i < 5; i++)
			{
				code = code.replace(new RegExp('@p' + (i + 1) + '@', 'g'),
						match[i + 1]);
			}
			cmd[cmdId] = code;
			break;
		}
	}

	var val = "";
	for ( var i = 0; i < cmd.length; i++)
	{
		if (cmd[i] == "")
			continue;
		val += cmd[i] + ";";
	}
	document.getElementById('col_' + colId).value = val;

	DisplayActionWizard(colId);
}

function DisplayActionWizard(colId)
{
	var value = document.getElementById('col_' + colId).value;
	var dis = document.getElementById('act_wizard_' + colId);

	var html = "";
	var preHtml = "";

	var cmd = value.split(';');
	html += "<table class='plainTable'>";
	for ( var i = 0; i < cmd.length; i++)
	{
		if (cmd[i] == "")
			continue;
		var foundCode = false;
		for ( var j = 0; j < actionWizard.length; j++)
		{
			if (actionWizard[j].expCode.test(cmd[i]))
			{
				html += "<tr class='oddLine'><td width='1%'>[<a href='#' onclick='return DeleteActionWizard("
						+ colId
						+ ","
						+ i
						+ ");'>Delete</a>]&nbsp;<td>"
						+ actionWizard[j].name + "</td></tr>";

				var match = cmd[i].match(actionWizard[j].expCode);

				for ( var k = 0; k < 5; k++)
				{
					if (actionWizard[j]['label_' + (k + 1)] == "")
						continue;
					html += "<tr><td width='1%'><b>"
							+ actionWizard[j]['label_' + (k + 1)].replace(/ /g,
									'&nbsp;') + ":</b></td>";
					if (actionWizard[j]['options_' + (k + 1)] != undefined
							&& actionWizard[j]['options_' + (k + 1)] != null)
					{
						var opts = actionWizard[j]['options_' + (k + 1)];
						html += "<td><select id='awiz_" + colId + "_" + i + "_"
								+ k + "' onchange='ListChangeActionWizard("
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
						if (actionWizard[j]['type_' + (k + 1)] == 't')
							match[k + 1] = unescape(match[k + 1]);
						html += "<td><input type='text' id='awiz_" + colId
								+ "_" + i + "_" + k + "' value='"
								+ htmlEntities(match[k + 1])
								+ "' onchange='TextChangeActionWizard(" + colId
								+ "," + i + "," + k + ")'></td>";
					}
					html += "</tr>";
				}
				foundCode = true;
				break;
			}
		}
		if (!foundCode)
			preHtml = "<span class='errorInfo'>Some actions are not supported. Disable the wizard to be able to really edit this row.</span>";
	}
	html += "</table>";

	html += "<select onchange='AddActionWizard(" + colId
			+ ");' id='new_condition_" + colId + "'>";
	html += "<option value=''>-- ADD AN ACTION --</option>";
	for ( var i = 0; i < actionWizard.length; i++)
	{
		html += "<option value='" + actionWizard[i].id + "'>"
				+ actionWizard[i].name + "</option>";
	}
	html += "</select>";
	dis.innerHTML = preHtml + html;
}

function initActionWizard()
{
	for ( var i = 0; i < actionWizardColumns.length; i++)
		DisplayActionWizard(actionWizardColumns[i]);
}

initActionWizard();