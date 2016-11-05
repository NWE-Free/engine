<?php
/*if(!IsAdmin())
    return;
foreach ($modules as $module)
{
    if (file_exists("$baseDir/modules/$module/config.xml"))
    {
        $doc = new XMLReader();
        $doc->open("$baseDir/modules/$module/config.xml");
        while ($doc->read())
        {
            if ($doc->nodeType == XMLReader::END_ELEMENT)
                continue;
            if ($doc->name == "table")
            {
                $table = $doc->getAttribute("name");
                $adminEntries[] = new MenuEntry($table, "Tables", null, 1000, "admin_edit_tables&table=$table");
            }
        }
        $doc->close();
    }
}*/