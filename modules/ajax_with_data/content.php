<?php
function PartUpdate($a, $b)
{
    echo Translate("Result: %d", $a + $b);
}

Ajax::RegisterFunction("PartUpdate", "partToUpdate");

TableHeader("This part will be updated");
echo "<div id='partToUpdate'>";
echo Translate("Result: %d", 0);
echo "</div>";
echo "Value A: <input type='text' id='valuea' value='0'><br>";
echo "Value B: <input type='text' id='valueb' value='0'><br>";
TableFooter();

ButtonArea();
Ajax::Button('Click to sum', 'PartUpdate($(\'#valuea\').val(),$(\'#valueb\').val())');
EndButtonArea();
