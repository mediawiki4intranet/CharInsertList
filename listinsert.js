window.insertTagsWithSplit = function(e)
{
    var value = e.nodeName == 'A' ? e.rel : e.value;
    if (value)
    {
        var p = -1;
        while ((p = value.indexOf('+', p+1)) > 0 &&
            value.substr(p-1, 1) == '\\') {}
        if (p >= 0)
        {
            insertTags(
                value.substr(0,p).replace('\\+','+'),
                value.substr(p+1).replace('\\+','+'),
                ''
            );
        }
        else
        {
            insertTags(value.replace('\\+','+'),'','');
        }
        if (e.nodeName == 'SELECT')
        {
            e.selectedIndex = 0;
        }
    }
    return false;
}
