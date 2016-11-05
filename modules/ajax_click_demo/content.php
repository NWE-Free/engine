<?php
function PartUpdate()
{
    echo Translate("Good you updated on %s", FormatDate());
}

Ajax::RegisterFunction("PartUpdate", "partToUpdate");

TableHeader("This part will be updated");
echo "<div id='partToUpdate'>";
echo Translate("Click the button to update via AJAX");
echo "</div>";
TableFooter();

ButtonArea();
Ajax::Button("Click Me", "PartUpdate()");
EndButtonArea();
