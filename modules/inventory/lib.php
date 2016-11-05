<?php

function LinkItemDetails($objectName, $objectId)
{
    global $webBaseDir;
    
    return "<a href='#' onclick='window.open(\"{$webBaseDir}modules/inventory/item_details.php?id=$objectId\",\"itemInfo\",\"status=no,toolbar=no,location=no,menubar=no,reizable=no,height=400,width=300\");return false;'>$objectName</a>";
}
