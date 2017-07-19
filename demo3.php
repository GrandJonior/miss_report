<?php 

	set_time_limit(0);

	date_default_timezone_set("PRC");
	$dbname = $_POST['dbname'];
	$start_time = $_POST["start_time"];
	$end_time = $_POST["end_time"];
	$dir=dirname(__FILE__);//查找当前脚本所在路径
	require $dir."/db2.php";//引入mysql操作类文件
	require $dir."/PHPExcel/PHPExcel.php";
	// echo time();die;
	$db = new db2($phpexcel);
 	$results = $db->getOriStatus3($start_time, $end_time);
 	$i= 0; 
 	$objPHPExcel=new PHPExcel();//实例化PHPExcel类， 等同于在桌面上新建一个excel
	$objPHPExcel->setActiveSheetIndex();//把新创建的sheet设定为当前活动sheet
	$objSheet=$objPHPExcel->getActiveSheet();//获取当前活动sheet
	$objSheet->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//设置excel文件默认水平垂直方向居中
	$objSheet->getDefaultStyle()->getFont()->setSize(14)->setName("微软雅黑");//设置默认字体大小和格式
	// $objSheet->getDefaultStyle()->getAlignment()->applyFromArray($styleArray);
	$objSheet->setTitle("按pay_time查询");
	$objSheet->setCellValue('A1','源单订单号')
			 ->setCellValue('b1','源单交易号')
			 ->setCellValue('c1','支付时间')
			 ->setCellValue('d1','渠道')
			 ->setCellValue('e1','源单商品数量')
			 ->setCellValue('f1','源单商品应付金额')
			 ->setCellValue('g1','终单订单号')
			 ->setCellValue('h1','终单交易号')
			 ->setCellValue('i1','终单状态')
			 ->setCellValue('j1','终单商品数量')
			 ->setCellValue('k1','终单商品应付金额')
			 ->setCellValue('l1','rate')
			 ;
	$list =2;

 	

 		// echo $order_id['order_id'] = $db->find_Order_id('1703239093315');
 	foreach ($results as $result) {
 
 		$final_orders = array();
 		$all_orders = array();
 		 // echo '<pre>';
 		$all_orders = $db->findAll($result['deal_code']);
 		// echo '修改前';
 		// print_r($all_orders);
 		find_Realloc_sn($db, $all_orders);
 		// echo '修改后';
 		// print_r($all_orders);
 		$original_order = 0 ;
 		foreach ($all_orders as $all_order) {
 			 if($all_order['order_sn'] == $result['order_sn']){
 			 	$original_order = $all_order;
 			 }
 		}
 		// echo '源单是:';
 		// print_r($original_order);
 		findFinall($original_order,$all_orders, $final_orders);
 		// echo '最终订单是';
 		// print_r($final_orders);
 		
 		foreach ($final_orders as $final_order ) {
 				// echo "<pre>";
 				// print_r($final_order);
 				// echo '<pre>';
 				// print_r($original_order);
 				// die;
 				$res = $db->find_Tel($original_order['order_sn']);
				$tel = $res[0]['tel'];
				$res = $db->find_PayTime($original_order['order_sn']);
				$pay_time = $res[0]['pay_time'];
				// echo $tel ; die;
				$rate = $final_order['shop_goods_amount']/$original_order['shop_goods_amount'];
				$objSheet->setCellValue("A".$list,$original_order['order_sn'])
				->setCellValue("B".$list,$original_order['deal_code'])
				->setCellValue("c".$list,$pay_time)
				->setCellValue("d".$list,$tel)
				->setCellValue("e".$list,$original_order['sku_count'] )
				->setCellValue("f".$list,$original_order['shop_goods_amount'] )
				->setCellValue("g".$list,$final_order['order_sn'])
				->setCellValue("h".$list,$final_order['deal_code'])
				->setCellValue("i".$list,$final_order['order_status'])
				->setCellValue("j".$list,$final_order['sku_count'])
				->setCellValue("k".$list,$final_order['shop_goods_amount'])
				->setCellValue("l".$list,$rate)
				;
				$list++;


				}
 		
 	}

 	ob_end_clean();

	ob_start();
	$objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');//生成excel文件
	//$objWriter->save($dir."/export_1.xls");//保存文件
	browser_export('Excel2007','Miss报表'.time().'.xlsx');//输出到浏览器
	$objWriter->save("php://output");


	function browser_export($type,$filename){
			if($type=="Excel5"){
					header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
			}else{
					header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器数据excel07文件
			}
			header('Content-Disposition: attachment;filename="'.$filename.'"');//告诉浏览器将输出文件的名称
			header('Cache-Control: max-age=0');//禁止缓存
	}

 	function find_Realloc_sn($db,&$all_orders = array()){
			foreach ($all_orders as &$all_order) {
			 			$res = $db->isRealloc($all_order['order_id']);
			 			$all_order['realloc_sn'] =  "";
				 		if(!empty($res)){
				 			$deal_code =  $res[0]['deal_code'];
				 			$realloc_orders = $db->find_Realloc_order($deal_code);
			 				sort_Realloc($realloc_orders);
			 				$all_order['realloc_sn'] = $realloc_orders[0]['order_sn'];

			 			}

			 		}
 	 }


	 function findFinall($original_order, $all_orders, &$final_orders = array()){
	 	
	 		
	 		$is_copy = 0; 
	 		$is_realloc = 0;
	 		$is_split = 0;
	
	 		$order_id = $original_order['order_id'];
	 		$realloc_sn = $original_order['realloc_sn'];
	 		$copy_orders = find_Copy_order($order_id, $all_orders);
	 		$split_orders = find_Split_order($order_id, $all_orders);
	 		$realloc_orders = find_Realloc_order($realloc_sn, $all_orders);
	 		if(!empty($realloc_orders)){
	 			
	 			$is_realloc = 1;
	 			// echo '是被再分配订单:'.$is_realloc ;
	 			foreach ($realloc_orders as $realloc_order) {
	 				findFinall($realloc_order, $all_orders, $final_orders);
	 			}
	 		}
	 		if(!empty($copy_orders)){
	 			$is_copy =1 ;
	 			// echo '是被复制订单:';
	 			foreach ($copy_orders as $copy_order) {

	 				findFinall($copy_order, $all_orders, $final_orders);
	 			}
	 		}
	 		if(!empty($split_orders)){
	 			$is_split =1 ;
	 			// echo '是被拆单订单:'.$is_split ;
	 			foreach ($split_orders as $split_order) {
	 				// print_r($split_order);
	 				findFinall($split_order, $all_orders, $final_orders);
	 			}
	 		}
	 		
	 		// 
	 		if( $is_realloc == 0 && $is_copy == 0 && $is_split == 0){

	 			$final_orders [] = $original_order;
	 		}

	 		return $final_orders;
 		}

 		function find_Copy_order($order_id,$orders){
 			$copy_orders = array();
 			foreach ($orders as $order) {
 				if($order['copy_from'] == $order_id){
 					$copy_orders [] = $order ;
 				}
 			
 			}

 			return $copy_orders;

 		} 		

 		function find_Split_order($order_id,$orders){
 			$split_orders = array();
 			foreach ($orders as $order) {

 				if($order['split_orders'] == $order_id){
 					$split_orders [] = $order ;
 				}
 			
 			}

 			return $split_orders;

 		}

 		function find_Realloc_order($realloc_sn,$orders){
 			$realloc_orders = array();

 		
 			foreach ($orders as $order) {

 				if($order['order_sn'] == $realloc_sn){
 					
 					$realloc_orders [] = $order ;

 				}
 			
 			}

 			return $realloc_orders;

 		}

 	 function sort_Realloc(&$realloc_orders = array()){
 	 	$sort = array(  
        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
        'field'     => 'order_sn',       //排序字段  
		);  
		$arrSort = array();  
		foreach($realloc_orders AS $uniqid => $row){  
		    foreach($row AS $key=>$value){  
		        $arrSort[$key][$uniqid] = $value;  
		    }  
		}  
		if($sort['direction']){  
		    array_multisort($arrSort[$sort['field']], constant($sort['direction']), $realloc_orders);  
		}  
 	 }

	function findFianlRealloc($deal_code, $order_sn, $db, &$final_orders = array()){
		$is_realloc = 0;
		$res = $db->find_Order_id($order_sn);
	 	$order_id = $res[0]['order_id'];
	 	$detail = 	$db->find_Detail($order_id);
	 	// echo '源订单是:';
	 	// print_r($detail);
	 	// echo $detail[0]['order_sn']; 
	 	$res = $db->isRealloc($order_id);
		if(!empty($res)){
				$is_realloc = 1;
 				$deal_code = $res[0]['deal_code'];
 				$realloc_orders = $db->find_Realloc_order($deal_code);
 				sort_Realloc($realloc_orders);
 				// echo '再分配订单是:';
 				// print_r($realloc_orders);
 				findFianlRealloc($realloc_orders[0]['deal_code'], $detail[0]['order_sn'],$db, $final_orders);

		 
		 }
		
		if($is_realloc == 0){
			
			$final_orders [] = $detail;
		}

		return $final_orders;
	}





 

