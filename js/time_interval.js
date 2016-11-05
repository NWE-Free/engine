var intervalGoalTime = new Array();
var intervalGoalReload = new Array();
var intervalTextDays = "days";
var intervalTextDay = "day";
var intervalTextHours = "hours";
var intervalTextHour = "hour";
var intervalTextMinutes = "minutes";
var intervalTextMinute = "minute";
var intervalTextSeconds = "seconds";
var intervalTextSeconds = "second";

function HandleTimeInterval()
{
	var now = new Date();
	var unix = Math.round(now.getTime() / 1000);
	for ( var i = 0; i < intervalGoalTime.length; i++)
	{
		var html = "";
		var diff = intervalGoalTime[i] - unix;

		if (diff <= 0 && intervalGoalReload[i] == true)
		{
			window.location.reload(true);
			intervalGoalReload[i] = false;
		}
		else if(diff < 0)
			continue;

		var days = Math.floor(diff / 86400.0);
		diff = diff % 86400;
		var hours = Math.floor(diff / 3600);
		diff = diff % 3600;
		var min = Math.floor(diff / 60);
		diff = diff % 60;
		var sec = diff;

		if (days == 1)
			html += days + " " + intervalTextDay + " ";
		else if (days > 1)
			html += days + " " + intervalTextDays + " ";

		if (hours == 1)
			html += hours + " " + intervalTextHour + " ";
		else if (hours > 1)
			html += hours + " " + intervalTextHours + " ";

		if (min == 1)
			html += min + " " + intervalTextMinute + " ";
		else if (min > 1)
			html += min + " " + intervalTextMinutes + " ";

		if (sec == 1)
			html += sec + " " + intervalTextSecond;
		else if (sec > 1)
			html += sec + " " + intervalTextSeconds;

		if (html == "")
			html = "0 " + intervalTextSeconds;

		$("#diff_time_" + i).html(html);
	}

	setTimeout('HandleTimeInterval();', 1000);
}

function InitTimeInterval()
{
	var now = new Date();
	var unix = Math.round(now.getTime() / 1000);

	for ( var i = 0; i < intervalGoalTime.length; i++)
	{
		intervalGoalTime[i] += unix;
	}

	HandleTimeInterval();
}

$(document).ready(InitTimeInterval);