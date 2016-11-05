function ExpandContractSideMenu(self,animated)
{
    var block=self.next('div');    
    if(block.attr('class') != 'subMenuGroup')
        return true;
        
    if(block.css('position') == 'relative')
    {
        self.attr('class','menuEntry');
        if(animated)
            block.fadeOut(300, function() { $(this).css('position','absolute'); });
        else
            block.fadeOut(0, function() { $(this).css('position','absolute'); });
    }
    else
    {
        $('.menuEntry.menuEntrySelected').next().fadeOut('fast', function() {  $(this).css('position','absolute');  });
        $('.menuEntry.menuEntrySelected').attr('class','menuEntry');
    
        self.attr('class','menuEntry menuEntrySelected');
        block.css('position','relative');
        if(animated)
            block.fadeIn(600, function() { });
        else
            block.fadeIn(0, function() { });
    }
    return false;
}

$('.menuEntry > a').click(function() { return ExpandContractSideMenu($(this).parent(), true); });