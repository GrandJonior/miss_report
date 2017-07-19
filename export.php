<?php 
	set_time_limit(0);

	date_default_timezone_set("PRC");

	$start_time = $_POST["start_time"];
	$status = $_POST['status'];
	// echo $status; die;
	$start_time = $status == 0 ? date('Ymd',strtotime($_POST["start_time"])):$_POST["start_time"];
	$end_time = $status== 0 ? date('Ymd',strtotime($_POST["end_time"])):$_POST["end_time"]; 
	$a1_value = $status == 0 ? '按batch_id查询' : '按pay_time查询';
	// $date = substr($_POST['dbname'], -4);
	// $str="AAA|BBB|CCC";
	$date = substr(strrchr($_POST['dbname'], "_"), 1);
	$dir=dirname(__FILE__);//查找当前脚本所在路径
	require $dir."/db.php";//引入mysql操作类文件
	require $dir."/PHPExcel/PHPExcel.php";//引入PHPExcel

	$rateStatus0_1 = 0.0;
	$rateStatus5 = 0.0;
	$rateStatus5_9 = 0.0;
	$rateMiss = 0.0;

	$db=new db($phpexcel);//实例化db类 连接数据库
	$objPHPExcel=new PHPExcel();//实例化PHPExcel类， 等同于在桌面上新建一个excel
	
	
		$objPHPExcel->setActiveSheetIndex();//把新创建的sheet设定为当前活动sheet
		$objSheet=$objPHPExcel->getActiveSheet();//获取当前活动sheet
		$objSheet->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//设置excel文件默认水平垂直方向居中
		$objSheet->getDefaultStyle()->getFont()->setSize(14)->setName("微软雅黑");//设置默认字体大小和格式
		// $objSheet->getDefaultStyle()->getAlignment()->applyFromArray($styleArray);
		$status ==1?$objSheet->setTitle("按pay_time查询"):$objSheet->setTitle("按batch_id查询");//给当前活动sheet起个名称
		$objSheet->mergeCells('A1:J1')
				 ->mergeCells('A2:A37')
				 ->mergeCells('B2:B37')
			     ->mergeCells('C4:C37')
				 ->mergeCells('D4:D37')
				 ->mergeCells('E5:E37')
				 ->mergeCells('F5:F37')
				// ->mergeCells('G5:G37')
				// ->mergeCells('H5:H37')
		 		 ->mergeCells('D2:J2')
		 		 ->mergeCells('D3:J3')
				 ->mergeCells('f4:h4')
				 ->mergeCells('I5:I12')
				 ->mergeCells('I13:I14')
				 ->mergeCells('I16:I37')
				 ->mergeCells('I5:I12')
				 ->mergeCells('J5:J12')
				 ->mergeCells('J13:J14')
				 ->mergeCells('J16:J37')
			 	 ;

		$objSheet->setCellValue('A2','源订单')
				 ->setCellValue('c2','待发货')
				 ->setCellValue('c3','已发货')

				 ->setCellValue('c4','已取消')
				 ->setCellValue('e4','未再分配')
				 ->setCellValue('e5','再分配')
				 ->setCellValue('A2','源订单')
				 ->setCellValue('i5','已发货')
				 ->setCellValue('i13','未发货')
				 ->setCellValue('i15','5.8')
				 ->setCellValue('i16','MISS')
				 ->setCellValue('A1', $a1_value.$start_time.'to'.$end_time)
				 ;

		
		$resultsI = $db->getDataStep1($date, $start_time, $end_time, $status);
		
		$res = $db->getDataStep2($start_time, $end_time, $status);
		$redistribution = $res[0]['count(1)'];
		// echo $redistribution ; die;
		$notForward = 0;
		$hasForward = 0;
		$hasCannel = 0;
		foreach ($resultsI as $result ) {
			$status_step2 = $result['order_status'];
			switch ($status_step2) {
				case '0':
				case '1':
					$notForward += $result['count(order_sn)'];
					break;
				case '3':
					$hasCannel += $result['count(order_sn)'];
					break;
				case '5':
					$hasForward += $result['count(order_sn)'];
					break;
				
				default:
					# code...
					break;
			}
		}
		$countAll = $notForward+ $hasForward + $hasCannel;
		$objSheet->setCellValue("b2",$countAll)
				 ->setCellValue("d4",$hasCannel)
				 ->setCellValue("d2",$notForward)
				 ->setCellValue("d3",$hasForward)
				 ;

		$datas=$db->getDataStep4_2($date, $start_time, $end_time, $status);
	
		// $objSheet->setCellValue("G5","type")->setCellValue("H5","sum(rate)");//填充数据
		$j = 5;
		$k = 13;
		$m = 15;
		$n = 16;
		foreach($datas as $data){
				// echo '<pre>';
				// print_r($data);die;
				$type = interceptionType($data["type"]);
			
					switch ($type) {
					case '1.1':
					case '1.2':
					case '1.3':
					case '5.3':
					case '5.3.1':
					case '5.4':
					case '5.4.1':
					case '5.8':
						//已发货
						$objSheet->setCellValue('g'.$j,$data["type"] )->setCellValue('h'.$j,$data['sum(rate)']);
						// $objSheet->getStyle('g'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID); 
                        // $objSheet->getStyle('g'.$j)->getFill()->getStartColor()->setRGB('ddddff');
                        $objSheet->getStyle('g'.$j)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
						$rateStatus5 += $data["sum(rate)"];
						$j++;
						break;

					case '2.1':
					case '2.2':
						# 待发货
						$objSheet->setCellValue('g'.$k,$data["type"] )->setCellValue('h'.$k,$data['sum(rate)']);
						 $objSheet->getStyle('g'.$k)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
						// $objSheet->getStyle('g'.$k)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID); 
                        // $objSheet->getStyle('g'.$k)->getFill()->getStartColor()->setRGB('ddddff');
						$rateStatus0_1 += $data["sum(rate)"];
						$k++;
						break;


					case '5.9':
						// 什么都不算
						$objSheet->setCellValue('g'.$m,$data["type"] )->setCellValue('h'.$m,$data['sum(rate)']);
						 $objSheet->getStyle('g'.$m)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
						// $objSheet->getStyle('g'.$m)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID); 
                        // $objSheet->getStyle('g'.$m)->getFill()->getStartColor()->setRGB('ddddff');
						$rateStatus5_9 += $data["sum(rate)"];
						$m++;
						break;

					case '3.1':
					case '3.2':
					case '4.1':
					case '4.2':
					case '5.1':
					case '5.2':
					case '5.5':
					case '5.6':
					case '5.7':
					case '6.1':
					case '6.2':
					case '7.1':
					case '8.1':
					case '9.1':
					case '9.2':
					case '9.3':
					case '9.4':
					case '10.1':
					case '10.2':
					case '11.1':
						# miss
						$objSheet->setCellValue('g'.$n,$data["type"] )->setCellValue('h'.$n,$data['sum(rate)']);
						 $objSheet->getStyle('g'.$n)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
						// $objSheet->getStyle('g'.$n)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID); 
                        // $objSheet->getStyle('g'.$n)->getFill()->getStartColor()->setRGB('ddddff');
						$rateMiss += $data["sum(rate)"];
						$n++;
						break;
					default:
						$otherError []= $data["type"];
						break;

			}


					
		}
		$objSheet->getStyle('a1:a37')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID); 
        $objSheet->getStyle('a1:a37')->getFill()->getStartColor()->setRGB('ddddff');
        $objSheet->getStyle('c1:c37')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID); 
        $objSheet->getStyle('c1:c37')->getFill()->getStartColor()->setRGB('ddddff');
		$objSheet->getStyle('e1:e37')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID); 
        $objSheet->getStyle('e1:e37')->getFill()->getStartColor()->setRGB('ddddff');
		$objSheet->getStyle('g1:g37')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID); 
        $objSheet->getStyle('g1:g37')->getFill()->getStartColor()->setRGB('ddddff');
        $objSheet->getStyle('i1:i37')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID); 
        $objSheet->getStyle('i1:i37')->getFill()->getStartColor()->setRGB('ddddff');
		
		// $redistribution = $rateStatus5 + $rateStatus0_1 + $rateStatus5_9 + $rateMiss;
		$no_redistribution = '';
        
		$objSheet->setCellValue("h4",$no_redistribution)
				 ->setCellValue("f5",$redistribution)
				 ->setCellValue("j5",$rateStatus5)
				 ->setCellValue("j13",$rateStatus0_1)
				 ->setCellValue("j15",$rateStatus5_9)
				 ->setCellValue("j16",$rateMiss)
				 ;

								

				// $objSheet->setCellValue("G".$j,$data['type'])->setCellValue("H".$j,$data['sum(rate)']);
				// $j++;

	//创建第二张表
	$objPHPExcel->createSheet();
	$objPHPExcel->setActiveSheetIndex(1);//把新创建的sheet设定为当前活动sheet
	$objSheet=$objPHPExcel->getActiveSheet();
	$objSheet->setTitle("4.1 查找包含多种再分配情况的原订单");
	$result_1 = $db->getDataStep4_1_1($date, $start_time, $end_time, $status);
	
	$arr = array ();
	foreach ($result_1 as $result) {
		$arr [] = $result['original_order_sn'];

	}

	
	if(!empty($arr))
	{
		$str = implode(',', $arr);
		
		// die('输出完成');
		$result_2 = $db->getDataStep4_1_2($date, $str, $status);
		
		$list =2;
		$objSheet->setCellValue("a1",'original_order_sn')
					 ->setCellValue("b1",'order_sn')
					 ->setCellValue("c1",'rate')
					 ->setCellValue("d1",'type');
		foreach ($result_2 as $result) {
			$objSheet->setCellValue("A".$list,$result['original_order_sn'])
					->setCellValue("B".$list,$result['order_sn'])
					->setCellValue("C".$list,$result['rate'])
					->setCellValue("d".$list,$result['type'])
					;
					$list++;
		}
	}else{
		$objSheet->setCellValue("a1",'original_order_sn')
					 ->setCellValue("b1",'order_sn')
					 ->setCellValue("c1",'rate')
					 ->setCellValue("d1",'type');
		 $objSheet->setCellValue("a2",'空')
		 	->setCellValue("b2",'空')
		 	->setCellValue("c2",'空')
		 	->setCellValue("d2",'空');
	}

	//第三张

	$objPHPExcel->createSheet();
	$objPHPExcel->setActiveSheetIndex(2);//把新创建的sheet设定为当前活动sheet
	$objSheet=$objPHPExcel->getActiveSheet();
	$objSheet->setTitle("4.3 查找再分配源单有订单，所有情况中没有的订单");
	$result_4_3 = $db->getDataStep4_3($date, $start_time, $end_time, $status);
	
	
	$objSheet->setCellValue("a1",'order_sn')
			 ->setCellValue("b1",'order_status')
			 ;
	
	$list = 2;
	foreach ($result_4_3 as $result) {
		$objSheet->setCellValue("A".$list,$result['order_sn'])
				->setCellValue("B".$list,$result['order_status'])
				;
				$list++;
	}
		

	ob_end_clean();

	ob_start();
	$objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');//生成excel文件
	//$objWriter->save($dir."/export_1.xls");//保存文件
	browser_export('Excel5',$start_time.'至'.$end_time.'Miss报表.xls');//输出到浏览器
	$objWriter->save("php://output");

	/*
		过滤字符串
	 */
	function interceptionType($str)
	{

		$result = array(); 
		preg_match_all("/(?:\()(.*)(?:\))/iU",$str, $result); 
		return $result[1][0]; 

	}

	/*
		添加报头
	 */
	function browser_export($type,$filename){
		if($type=="Excel5"){
				header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
		}else{
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器数据excel07文件
		}
		header('Content-Disposition: attachment;filename="'.$filename.'"');//告诉浏览器将输出文件的名称
		header('Cache-Control: max-age=0');//禁止缓存
	}

?>