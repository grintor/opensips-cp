<!--
 /*
 * $Id$
 * Copyright (C) 2011 OpenSIPS Project
 *
 * This file is part of opensips-cp, a free Web Control Panel Application for 
 * OpenSIPS SIP server.
 *
 * opensips-cp is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * opensips-cp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
-->

<?php
require("lib/".$page_id.".main.js");

$sql_search="";
$search_groupid=$_SESSION['lb_groupid'];
$search_dsturi=$_SESSION['lb_dsturi'];
$search_resources=$_SESSION['lb_resources'];

if(!$_SESSION['read_only']){
	$colspan = 10;
}else{
	$colspan = 8;
}
?>

<div id="dialog" class="dialog" style="display:none"></div>
<div onclick="closeDialog();" id="overlay" style="display:none"></div>
<div id="content" style="display:none"></div>

<form action="<?=$page_name?>?action=dp_act" method="post">
<table width="50%" cellspacing="2" cellpadding="2" border="0">
 <tr align="center">
  <td colspan="2" height="10" class="loadbalancerTitle"></td>
 </tr>
  <tr>
  <td class="searchRecord">Group ID</td>
  <td class="searchRecord" width="200"><input type="text" name="lb_groupid" 
  value="<?=$search_groupid?>" class="searchInput"></td>
 </tr>
  <tr>
  <td class="searchRecord">Destination URI</td>
  <td class="searchRecord" width="200"><input type="text" name="lb_dsturi" 
  value="<?=$search_dsturi?>" maxlength="16" class="searchInput"></td>
 </tr>
  <tr>
  <td class="searchRecord">Resources</td>
  <td class="searchRecord" width="200"><input type="text" name="lb_resources" 
  value="<?=$search_resources?>" maxlength="128" class="searchInput"></td>
 </tr>
  <tr height="10">
  <td colspan="2" class="searchRecord" align="center">
  <input type="submit" name="search" value="Search" class="searchButton">&nbsp;&nbsp;&nbsp;
  <input type="submit" name="show_all" value="Show All" class="searchButton"></td>
 </tr>

 <tr height="10">
  <td colspan="2" class="loadbalancerTitle"><img src="../../../images/share/spacer.gif" width="5" height="5"></td>
 </tr>

</table>
</form>

<table width="50%" cellspacing="2" cellpadding="2" border="0">
<tr>
<td align="center">
<form action="<?=$page_name?>?action=add&clone=0" method="post">
 <?php if (!$_SESSION['read_only']) echo('<input type="submit" name="add_new" value="Add New LB Entry" class="formButton">') ?>
</form>
</td>
</tr>
</table>

<form action="<?=$page_name?>?action=refresh" method="post">
<table width="95%" cellspacing="2" cellpadding="2" border="0">
	<tr height="10">
		<td colspan="3"  align="right"><input type="submit" name="refresh" value="Refresh from Cache" class="searchButton"></td>
	</tr>
</table>
</form>


<table class="ttable" width="95%" cellspacing="2" cellpadding="2" border="0">
 <tr align="center">
  <th class="loadbalancerTitle">ID</th>
  <th class="loadbalancerTitle">Group ID</th>
  <th class="loadbalancerTitle">Destination URI</th>
  <th class="loadbalancerTitle">Resources</th>
  <th class="loadbalancerTitle">Probe Mode</th>
  <th class="loadbalancerTitle">Auto Re-enable</th>
  <th class="loadbalancerTitle">Status</th>
  <th class="loadbalancerTitle">Description</th>
  <?
  if(!$_SESSION['read_only']){

  	echo('<th class="loadbalancerTitle">Edit</th>
  		<th class="loadbalancerTitle">Delete</th>');
  }
  ?>
 </tr>

<?php
if($search_groupid!="") { 
	$sql_search.=" and group_id=".$search_groupid;
}
if ( $search_dsturi!="" ) {
	$sql_search.=" and dst_uri like '%".$search_dsturi."%'";
}
if ( $search_resources!="" ) {
	$sql_search.=" and resources like '%".$search_resources."%'";
}

$sql_command="select count(*) from ".$table." where (1=1) ".$sql_search;
$result = $link->queryAll($sql_command);
if(PEAR::isError($result)) {
         die('Failed to issue query, error message : ' . $result->getMessage());
}

$data_no=$result[0]['count(*)'];
if ($data_no==0)
	echo('<tr><td colspan="'.$colspan.'" class="rowEven" align="center"><br>'.$no_result.'<br><br></td></tr>');
else {
	// get in memory status for the entries we want to list
	$mi_connectors=get_proxys_by_assoc_id($talk_to_this_assoc_id);
	$message = mi_command('lb_list', $mi_connectors[0], $mi_type, $errors,$status);

	$lb_state = array();
	$lb_res = array();
	$lb_auro = array();

	if ($mi_type != "json"){
		$message = trim($message);
		$pattern = '/Destination\:\:\s+(?P<destination>sip\:[a-zA-Z0-9.:-]+)\s+id=(?P<id>\d+)\s+group=(?P<group>\d+)\s+enabled=(?P<enabled>yes|no)\s+auto-reenable=(?P<autore>on|off)\s+Resources(\:\:)?(?P<resources>(\s+Resource\:\:\s+[a-zA-Z0-9]+\s+max=\d+\s+load=\d+)*)/';
		preg_match_all($pattern,$message,$matches);
		for ($i=0; $i<count($matches[0]);$i++) {
			$id			= $matches['id'][$i];

			$pattern	= '/\s+Resource\:\:\s+(?P<resource_name>[a-zA-Z0-9_-]+)\s+max=(?P<resource_max_load>\d+)\s+load=(?P<resource_load>\d+)/';
			preg_match_all($pattern,$matches['resources'][$i],$resources);

			$resource="";
			for ($j=0;$j<count($resources[0]);$j++) {
				$resource .= "<tr>";
				$resource .= "<td>".$resources['resource_name'][$j]."=".$resources['resource_load'][$j]."/".$resources['resource_max_load'][$j]."</td>";
				$resource .= "</tr>";
			}
			$lb_res[$id] = "<table>".$resource."</table>";
			$lb_state[$id] = ($matches['enabled'][$i]=="yes")?"enabled":"disabled";
			$lb_auto[$id] = $matches['autore'][$i];
		}

	} else {

		//no more stupid parsing
		$message = json_decode($message,true);
		$message = $message['Destination'];
		for ($i=0; $i<count($message);$i++) {
			$id 		= $message[$i]['attributes']['id'];

			$resource="";
			$res = $message[$i]['children']['Resources']['children']['Resource'];
			for ($j=0;$j<count($res);$j++) {
				$resource .= "<tr>";
				$resource .= "<td>".$res[$j]['value']."=".$res[$j]['attributes']['load']."/".$res[$j]['attributes']['max']."</td>";
				$resource .= "</tr>";
			}
			$lb_res[$id] = "<table>".$resource."</table>";
			$lb_state[$id] = ($message[$i]['attributes']['enabled']=="yes")?"enabled":"disabled";
			$lb_auto[$id] = $message[$i]['attributes']['auto-reenable'];
		}
	}


	$res_no=$config->results_per_page;
	$page=$_SESSION[$current_page];
	$page_no=ceil($data_no/$res_no);
	if ($page>$page_no) {
		$page=$page_no;
		$_SESSION[$current_page]=$page;
	}
	$start_limit=($page-1)*$res_no;

	$sql_command = "select * from ".$table." where (1=1) ".$sql_search." order by id asc";
	if ($start_limit==0) $sql_command.=" limit ".$res_no;
	else $sql_command.=" limit ". $res_no . " OFFSET " . $start_limit;
	$result = $link->queryAll($sql_command);
	if(PEAR::isError($result)) {
		die('Failed to issue query, error message : ' . $resultset->getMessage());
	}

	// display the resulting rows in the table
	$index_row=0;
	for ($i=0;count($result)>$i;$i++)
	{
		$index_row++;
		$id = $result[$i]['id'];

		if ($index_row%2==1) $row_style="rowOdd";
		else $row_style="rowEven";

		/* if the resources were not fetched via MI, used
		   the DB values */
		if ($lb_res[$id]==NULL || $lb_res[$id]=="")
			$lb_res[$id] = $result[$i]['resources'];
		?>
		<tr>
			<td class="<?=$row_style?>">&nbsp;<?=$result[$i]['id']?></td>
			<td class="<?=$row_style?>">&nbsp;<?=$result[$i]['group_id']?></td>
			<td class="<?=$row_style?>">&nbsp;<?=$result[$i]['dst_uri']?></td>
			<td class="<?=$row_style?>"><?=$lb_res[$id]?></td>
			<td class="<?=$row_style?>">&nbsp;<?=get_probe_mode($result[$i]['probe_mode'])?></td>
			<td class="<?=$row_style?>">&nbsp;<?=$lb_auto[$id]?></td>
			<td class="<?=$row_style?>">&nbsp;
				<div align="center">
					<form action="<?=$page_name?>?action=toggle&toggle_button=<?=$lb_state[$id]?>&id=<?=$id?>" method="post">
					<? if ( $lb_state[$id] == "enabled" ) {
						echo '<input type="submit" name="toggle" value="'.$lb_state[$id].'" class="formButton" style="background-color: #00ff00; ">';
					} else if  ( $lb_state[$id] == "disabled" ) {
						echo '<input type="submit" name="toggle" value="'.$lb_state[$id].'" class="formButton" style="background-color: #ff0000; ">';
					}
					?>
					</form>
				</div>
			</td>
			<td class="<?=$row_style?>">&nbsp;<?=$result[$i]['description']?></td>
			<? 
			if(!$_SESSION['read_only']){
				echo('<td class="'.$row_style.'" align="center"><a href="'.$page_name.'?action=edit&clone=0&id='.$result[$i]['id'].'"><img src="../../../images/share/edit.gif" border="0"></a></td>');
				echo('<td class="'.$row_style.'" align="center"><a href="'.$page_name.'?action=delete&clone=0&id='.$result[$i]['id'].'"onclick="return confirmDelete()"><img src="../../../images/share/trash.gif" border="0"></a></td>');
   			}
			?>  
		</tr>  
<?php
	}
}
?>
 <tr>
  <th colspan="<?=$colspan?>" class="loadbalancerTitle">
    <table width="100%" cellspacing="0" cellpadding="0" border="0">
     <tr>
      <th align="left">
       &nbsp;Page:
       <?php
       if ($data_no==0) echo('<font class="pageActive">0</font>&nbsp;');
       else {
       	$max_pages = $config->results_page_range;
       	// start page
       	if ($page % $max_pages == 0) $start_page = $page - $max_pages + 1;
       	else $start_page = $page - ($page % $max_pages) + 1;
       	// end page
       	$end_page = $start_page + $max_pages - 1;
       	if ($end_page > $page_no) $end_page = $page_no;
       	// back block
       	if ($start_page!=1) echo('&nbsp;<a href="'.$page_name.'?page='.($start_page-$max_pages).'" class="menuItem"><b>&lt;&lt;</b></a>&nbsp;');
       	// current pages
       	for($i=$start_page;$i<=$end_page;$i++)
       	if ($i==$page) echo('<font class="pageActive">'.$i.'</font>&nbsp;');
       	else echo('<a href="'.$page_name.'?page='.$i.'" class="pageList">'.$i.'</a>&nbsp;');
       	// next block
       	if ($end_page!=$page_no) echo('&nbsp;<a href="'.$page_name.'?page='.($start_page+$max_pages).'" class="menuItem"><b>&gt;&gt;</b></a>&nbsp;');
       }
       ?>
      </th>
      <th align="right">Total Records: <?=$data_no?>&nbsp;</th>
     </tr>
    </table>
  </th>
 </tr>
</table>
<br>


