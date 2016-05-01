<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

@session_start() ;

//Module includes
require_once "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Data Admin/snapshot_manage.php'>" . __($guid, 'Manage Snapshots') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Create Snapshot') . "</div>" ;
		print "</div>" ;

	print "<div class='warning'>" ;
	print __($guid, 'Database snapshots allow you to save and restore your entire Gibbon database, which can be useful before importing data. They should NOT be used on live systems or when other users are online. Snapshots should NOT be used in place of standard backup procedures. A snapshot only saves MySQL data and does not save uploaded files or preserve any changes to the file system.');
	print "</div>" ;

	print "<div class='warning'>" ;
	print __($guid, 'Database files can be quite large, do not refresh the page after pressing submit. Also, this may fail if PHP does not have access to execute system commands.');
	print "</div>" ;
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/snapshot_manage_addProcess.php" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td>
					
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print __($guid, "Create Snapshot") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
}	
?>