<?php
   require_once "Xls/PHPExcel.php";
   $prueba = new PHPExcel();
   $prueba->setActiveSheetIndex(0)->setCellValue("A1","PRUEBA");

   $prueba->getActiveSheet()->setTitle("Hoja de prueba");

   $objWriter = PHPExcel_IOFactory::createWriter($prueba, 'Excel2007');
   $objWriter->save('prueba.xlsx'); 
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
