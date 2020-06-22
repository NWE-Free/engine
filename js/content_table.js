function expandHeader(id)
{
    var div = document.getElementById('content_table_' + id);
    if (div.style.height == '0px')
    {
        div.style.height = '';
        div.style.overflow = '';
        document.getElementById('content_img_' + id).src = 'images/expanded.png';
    }
    else
    {
        div.style.height = '0px';
        div.style.overflow = 'hidden';
        document.getElementById('content_img_' + id).src = 'images/contracted.png';
    }
}
