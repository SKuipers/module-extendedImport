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

//Increase max execution time, as this stuff gets big
ini_set('max_execution_time', 600);

include "../../config.php" ;
include "../../functions.php" ;
include "../../version.php" ;

$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);


//Module includes
include "./moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/export_run.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {

	$dataExport = (isset($_GET['data']) && $_GET['data'] == true);

	//Class includes
	require_once "./src/importType.class.php" ;

	// Get the importType information
	$type = (isset($_GET['type']))? $_GET['type'] : '';
	$importType = DataAdmin\importType::loadImportType( $type, $pdo );

	if ( empty($importType)  ) {
		print "<div class='error'>" ;
		print __($guid, "Your request failed because your inputs were invalid.") ;
		print "</div>" ;
		return;
	} else if ( !$importType->isValid() ) {
		print "<div class='error'>";
		printf( __($guid, 'Import cannot proceed, as the selected Import Type "%s" did not validate with the database.'), $type) ;
		print "<br/></div>";
		return;
	}

	/** Include PHPExcel */
	require_once $_SESSION[$guid]["absolutePath"] . '/lib/PHPExcel/Classes/PHPExcel.php';

	// Create new PHPExcel object
	$excel = new PHPExcel();

	//Create border styles
	$style_head_fill=array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'eeeeee')),
							'borders' => array('top' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => '444444'),), 'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => '444444'),)),
							) ;

	// Set document properties
	$excel->getProperties()->setCreator(formatName("",$_SESSION[$guid]["preferredName"], $_SESSION[$guid]["surname"], "Staff"))
		 ->setLastModifiedBy(formatName("",$_SESSION[$guid]["preferredName"], $_SESSION[$guid]["surname"], "Staff"))
		 ->setTitle( __($guid, "Activity Attendance") )
		 ->setDescription(__($guid, 'This information is confidential. Generated by Gibbon (https://gibbonedu.org).')) ;

	$filename = ( ($dataExport)? __($guid, "DataExport") : __($guid, "DataStructure") ).'-'.$type;

	$excel->setActiveSheetIndex(0) ;

	$tableName = $importType->getDetail('table');
	$primaryKey = $importType->getPrimaryKey();

	$tableFieldsAll = $importType->getTableFields();
	$tableFields = array();
	$columnFields = array();

	foreach ($tableFieldsAll as $fieldName ) {
		if ($importType->isFieldHidden($fieldName)) continue; // Skip hidden fields
		
		$columnFields[] = $fieldName;

		if ($importType->isFieldReadOnly($fieldName) && $dataExport == true) continue; // Skip readonly fields when exporting data
		
		$tableFields[] = $fieldName;
	}

	if ($dataExport && !empty($primaryKey)) {
		$tableFields = array_merge( array($primaryKey), $tableFields);
		$columnFields = array_merge( array($primaryKey), $columnFields);
	}

	// Create the header row
	$count = 0;
	foreach ($columnFields as $fieldName ) {
		
		$excel->getActiveSheet()->setCellValue( num2alpha($count).'1', $importType->getField($fieldName, 'name', $fieldName ) );
		$excel->getActiveSheet()->getStyle( num2alpha($count).'1')->applyFromArray($style_head_fill);

		// Dont auto-size giant text fields
		if ( $importType->getField($fieldName, 'kind') == 'text' ) {
			$excel->getActiveSheet()->getColumnDimension( num2alpha($count) )->setWidth(25);
		} else {
			$excel->getActiveSheet()->getColumnDimension( num2alpha($count) )->setAutoSize(true);
		}

		// Add notes to column headings
		$info = ($importType->isFieldRequired($fieldName))? "* required\n" : '';
		$info .= $importType->readableFieldType($fieldName)."\n";
		$info .= $importType->getField($fieldName, 'desc', '' );

		if (!empty($info)) {
			$excel->getActiveSheet()->getComment( num2alpha($count).'1' )->getText()->createTextRun( $info );
		}

		$count++;
	}

	// Get the data
	if ($dataExport) {
 		try {
			$data=array(); 
			$sql="SELECT ".implode(', ', $tableFields)." FROM $tableName" ;

			// Limit all exports to the current school year by default, to avoid massive files
			$gibbonSchoolYearID = $importType->getField('gibbonSchoolYearID', 'name', null);
			if ($gibbonSchoolYearID != null && $importType->isFieldReadOnly('gibbonSchoolYearID') == false ) {
				$data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
				$sql.= " WHERE gibbonSchoolYearID=:gibbonSchoolYearID ";
			}

			$sql.= " ORDER BY $primaryKey ASC";
			$result = $pdo->executeQuery($data, $sql);
		}
		catch(PDOException $e) { print $e->getMessage(); }

		if ($result && $result->rowCount() > 0) {
			$rowCount = 2;
			while ($row = $result->fetch()) {

				$fieldCount = 0;
				//foreach ($tableFields as $fieldName ) {
				
				// Work backwards, so we can potentially fill in any relational read-only fields 
				for ($i=count($columnFields)-1; $i >= 0; $i--) {

					$fieldName = $columnFields[$i];
					$value = (isset($row[ $fieldName ]))? $row[ $fieldName ] : null;

					// Handle relational fields (going backwards)
					if (!empty($value) && $importType->isFieldRelational($fieldName)) {
						extract( $importType->getField($fieldName, 'relationship') );

						$queryFields = (is_array($field))? implode(',', $field) : $field;

						$relationalData = array( $key => $value );
						$relationalSQL = "SELECT $queryFields FROM $table WHERE $key=:$key";
						$resultRelation = $pdo->executeQuery($relationalData, $relationalSQL);

						
						if ($resultRelation->rowCount() == 1) {
                        	if ( is_array($field) && count($field) > 1 ) {
	                    		// Multi-key relational field
								$relationalRow = $resultRelation->fetch();
	                        	$value = $relationalRow[ $field[0] ];

	                        	for ($n=1; $n < count($relationalRow); $n++) {
	                        		$relationalField = $field[$n];


	                        		// Does the field exist in the import definition but not in the current table?
	                        		if ( $importType->isFieldReadOnly($relationalField) ) { //isset($columnFields[$relationalField]) && !isset($tableFields[$relationalField])
	                        			$row[ $relationalField ] = $relationalRow[ $relationalField ];
	                        		}
	                        	}
	                    	} else {
	                    		// Single key relation
	                    		$value = $resultRelation->fetchColumn(0);
	                    	}
                    	}
					}

					// Set the cell value
					$excel->getActiveSheet()->setCellValue( num2alpha($i).$rowCount, $value );
					$fieldCount++;
				}

				$rowCount++;
			}
		}

	}

	//FINALISE THE DOCUMENT SO IT IS READY FOR DOWNLOAD
	// Set active sheet index to the first sheet, so Excel opens this as the first sheet
	$excel->setActiveSheetIndex(0);

	// Redirect output to a client’s web browser (Excel2007)
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
	header('Cache-Control: max-age=0');
	// If you're serving to IE 9, then the following may be needed
	header('Cache-Control: max-age=1');

	// If you're serving to IE over SSL, then the following may be needed
	header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	header ('Pragma: public'); // HTTP/1.0

	$objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
	$objWriter->save('php://output');
	exit;
}	
?>