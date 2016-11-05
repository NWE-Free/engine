<?php
function PartUpdate()
{
    echo Translate("Good you updated on %s", FormatDate());
}

function StopRefreshPage()
{
    Ajax::StopRefresh();
}

Ajax::UpdateTimer("PartUpdate", "partToUpdate");

Ajax::RegisterFunction("StopRefreshPage");

if (Ajax::IsAjaxCallback()) {
    return;
}

TableHeader("This part will be updated");
echo "<div id='partToUpdate'>";
echo Translate("Will update every 5 sec via ajax");
echo "</div>";
TableFooter();

ButtonArea();
Ajax::Button("Stop refresh", "StopRefreshPage()");
EndButtonArea();
