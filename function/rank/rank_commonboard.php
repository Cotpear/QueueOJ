<?php
if(!defined('IN_SKYOJSYSTEM'))
{
    exit('Access denied');
}
$id='error';
$data = false;

$_E['template']['dbg'] = '';
$_E['template']['cbrebuild'] = false; 

//Check id
if( isset($_GET['id']) )
{
    $id = $_GET['id'];
    if(!is_string($id) || !preg_match('/^[0-9]+$/',$id))
    {
        $id = 'error';
    }
    else
    {
        $id = intval($id);
        if( $cache = DB::loadcache("cache_board_$id") )
        {
            $boarddata = $cache['data'];
            $_E['template']['dbg'].="Load from global cache!<br>";
            if( $cache['time']<time() ){
                $_E['template']['cbrebuild'] = true;
                $_E['template']['dbg'].="Rebuild call!";
            }
        }
        else
        {
            $boarddata = buildcbboard($id,array());
            if($boarddata)
            {
                DB::putcache("cache_board_$id",
                            array('data'=>$boarddata,'time'=>time()+900),
                            'forever');
                $_E['template']['dbg'].="Call Build!<br>";
                $_E['template']['cbrebuild'] = true;
            }
        }
    }
}

if( !isset($_GET['id']) || !$boarddata )
{
    $_E['template']['alert'].="沒有這一個記分板";
    include('rank_list.php');
    exit('');
}

//頁面資訊

if($_E['template']['cbrebuild'])
{
    $key = uniqid('cbedit',true);
    $_SESSION["cbsyscall"][$key] = $id;
    $_E['template']['cbrebuildkey'] = $key;
}

$_E['template']['title'] = $boarddata['name'];
$_E['template']['plist'] = $boarddata['probinfo'];
$_E['template']['user']  = $boarddata['userlist'];
$_E['template']['owner'] = $boarddata['owner'];
$_E['template']['id'] = $id;

//導覽列
$tbstats = DB::tname('statsboard');
$res = DB::query("SELECT COUNT(1) FROM `$tbstats`");
$maxid = DB::fetch($res);$maxid = $maxid[0];
//it sholuld be use SQL!
$_E['template']['leftid'] = 0;
$_E['template']['rightid'] = 0;
if($id-1 > 0) $_E['template']['leftid'] = $id-1;
if($id+1 <= $maxid)$_E['template']['rightid'] = $id+1;

$_E['template']['homeid'] = 0;
for( $t=$maxid; $t>0 ;$t-=10)
{
    if($t<$id)
        break;
    $_E['template']['homeid']++;
}


foreach($boarddata['userlist'] as $uid)
{
    foreach($boarddata['problems'] as $pname)
    {
        $_E['template']['s'][$uid][$pname] = verdictIDtoword($boarddata['ratemap'][$uid][$pname]);
    }
}

#add nickname
nickname($boarddata['userlist']);

Render::render('rank_statboard_cm','rank');