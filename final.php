
<?php 

	set_time_limit(0);

	date_default_timezone_set("PRC");

	$start_time = $_POST["start_time"];
	$end_time = $_POST["end_time"];
	$dir=dirname(__FILE__);//查找当前脚本所在路径
	require $dir."/db2.php";//引入mysql操作类文件
	require $dir."/PHPExcel/PHPExcel.php";
	// echo time();die;
	$db = new db2($phpexcel);
 	$i= 0; 
 	$objPHPExcel=new PHPExcel();//实例化PHPExcel类， 等同于在桌面上新建一个excel
	$objPHPExcel->setActiveSheetIndex();//把新创建的sheet设定为当前活动sheet
	$objSheet=$objPHPExcel->getActiveSheet();//获取当前活动sheet
	$objSheet->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//设置excel文件默认水平垂直方向居中
	$objSheet->getDefaultStyle()->getFont()->setSize(14)->setName("微软雅黑");//设置默认字体大小和格式
	// $objSheet->getDefaultStyle()->getAlignment()->applyFromArray($styleArray);
	$objSheet->setTitle("按pay_time查询");
	$objSheet->setCellValue('A1','发货订单数')
			 ->setCellValue('a3','miss订单数')
			 ->setCellValue('a5','源单订单数')
			 ->setCellValue('b1','金额')
			 ->setCellValue('b3','金额')
			 ->setCellValue('b5','金额')
			 ;
	$list =2;
	$res  = $db->