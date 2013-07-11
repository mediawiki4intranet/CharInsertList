<?php

/**
 * Copyright (C) 2010+ Vitaliy Filippov <vitalif at mail.ru>
 * http://wiki.4intra.net/CharInsertList
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Extension is very similar to CharInsert, but also allows to create HTML
 * listboxes (<select>) with charinsert items instead of simple hyperlinks.
 *
 * type=dropdown (default) means to generate <select>boxes
 * type=links is similar to normal CharInsert output with hyperlinks, but
 * a title (Item Name) can be specified for each of them.
 *
 * Usage syntax:
 * <listinsert [type=links|dropdown] [attributes]>
 * Item Name = Item Text
 * Item Name = Long and multiline \
 *             Item Text
 * Item Name = What_is_inserted_before_cursor + What_is_inserted_after_cursor \
 *             CharInsert-like syntax
 * Item Name = This is a real \+ character, not cursor marker (with slash)
 * </listinsert>
 *
 * [attributes] (all except 'type') are copied to HTML <select> or <a> tag
 * attributes without any change (for example you can specify style="...").
 *
 * @author Vitaliy Filippov <vitalif at mail.ru>
 * @addtogroup Extensions
 */

if (!defined('MEDIAWIKI'))
{
    die();
}

if (defined('MW_SUPPORTS_PARSERFIRSTCALLINIT'))
{
    $wgHooks['ParserFirstCallInit'][] = 'efListInsertSetup';
}
else
{
    $wgExtensionFunctions[] = 'efListInsertSetup';
}

$wgExtensionCredits['parserhook'][] = array(
    'name' => 'CharInsertList',
    'author' => 'VitaliyFilippov',
    'version' => '2013-07-11',
    'url' => 'http://wiki.4intra.net/CharInsertList',
    'description' => 'Allows creation of HTML selectboxes for inserting non-standard characters',
);
define('CIL_TYPE_DROPDOWN', 'dropdown');
define('CIL_TYPE_LINKS', 'links');

function efListInsertSetup()
{
    global $wgParser;
    $wgParser->setHook('listinsert', 'efListInsertParserHook');
    return true;
}

function efListInsertParserHook($text, $attrs, $parser)
{
    $data = explode("\n", trim($text));
    if (!$data)
    {
        return '';
    }
    $type = CIL_TYPE_DROPDOWN;
    if (isset($attrs['type']))
    {
        $type = strtolower($attrs['type']);
        if ($type != CIL_TYPE_LINKS)
        {
            $type = CIL_TYPE_DROPDOWN;
        }
        unset($attrs['type']);
    }
    $line = trim($data[count($data)-1]);
    $html = '';
    $select_attr = '';
    foreach ($attrs as $k => $v)
    {
        $select_attr .= htmlspecialchars($k, ENT_QUOTES) . '="' . htmlspecialchars($v, ENT_QUOTES).'" ';
    }
    if ($type == CIL_TYPE_LINKS)
    {
        $select_attr .= ' onclick="' . efListItemChange($type) . '"';
    }
    for ($i = count($data)-2; $i >= 0; $i--)
    {
        $prev = trim($data[$i]);
        if (substr($prev, -1) == "\\")
        {
            $line = substr($prev, 0, -1) . "\n" . $line;
        }
        else
        {
            $html = efListInsertOption($line, $type, $select_attr) . $html;
            $line = $prev;
        }
    }
    $html = efListInsertOption($line, $type, $select_attr) . $html;
    switch ($type)
    {
        case CIL_TYPE_LINKS:
            // do nothing
            break;
        case CIL_TYPE_DROPDOWN:
        default:
            $html = '<select '.$select_attr.'onchange="' . efListItemChange($type) . '"><option value="">-</option>' . $html . '</select>';
            break;
    }
    return $html;
}

function efListInsertOption($line, $type, $select_attr = '')
{
    list($name, $value) = explode("=", $line, 2);
    $name = trim($name);
    $value = trim($value);
    switch ($type)
    {
        case CIL_TYPE_LINKS:
            return '<a href="#" rel="'.htmlspecialchars($value, ENT_QUOTES).'" '.$select_attr.'>'.htmlspecialchars($name, ENT_QUOTES).'</a> ';
        case CIL_TYPE_DROPDOWN:
        default:
            return '<option value="'.htmlspecialchars($value, ENT_QUOTES).'">'.htmlspecialchars($name, ENT_QUOTES).'</option>';
    }
}

function efListItemChange($type)
{
    $value = '';
    $select = '';
    switch ($type)
    {
        case CIL_TYPE_LINKS:
            $value = 'this.rel';
            break;
        case CIL_TYPE_DROPDOWN:
        default:
            $value = 'this.value';
            $select = 'this.selectedIndex=0;';
            break;
    }
    $res = <<<END_STRING
if($value) {
    var p=-1;
    while(
        (p=$value.indexOf('+',p+1))>0 &&
        $value.substr(p-1,1)=='\\\\'
    ){}
    if(p>=0)
    {
        insertTags(
            $value.substr(0,p).replace('\\\\+','+'),
            $value.substr(p+1).replace('\\\\+','+'),
            ''
        );
    }
    else
    {
        insertTags($value.replace('\\\\+','+'),'','');
    }
    $select
}
return false;
END_STRING;
    $res = preg_replace('/\s*\n\s*/', ' ', $res);
    return $res;
}
