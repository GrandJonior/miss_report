<?php
	set_time_limit(0);

	date_default_timezone_set("PRC");
	$dir=dirname(__FILE__);//查找当前脚本所在路径
	require $dir."/db2.php";//引入mysql操作类文件
	require $dir."/PHPExcel/PHPExcel.php";
	// echo time();die;
	$db = new db2($phpexcel);
 	$results = $db->getOriStatus3_order_sn();
 	$i= 0; 

 		// echo $order_id['order_id'] = $db->find_Order_id('1703239093315');
 	foreach ($results as $result) {
 		print_r($result);
 		$final_orders = array();
 		$res = $db->find_Order_id($result['order_sn']);
	 	$order_id = $res[0]['order_id'];
	 	// $detail = 	$db->find_Detail($order_id);
	 	// $res = $db->isRealloc($order_id);
	 	// if(!empty($res)){
 			// $deal_code = $res[0]['deal_code'];
 			// $realloc_orders = $db->find_Realloc_order($deal_code);
 			 // echo '<pre>';
 			 // findFianlRealloc($deal_code, $result['order_sn'],$db, $final_orders);
 			
 			// echo $deal_code;
 			
 			// echo '最终订单是:';
 			// print_r($final_orders);	
	 		// }
	 		// echo $order_id ; die;
 		// $detail = 	$db->find_Detail($order_id);
 		findFinall($result['order_sn'],$db,$final_orders);
 		 echo '<pre>';
 		 print_r($final_orders);
 		
 		
 		$i++;
 		echo '第'.$i.'次循环';
 		if($i>5){
 			break;
 		}
 	}
 	// echo $i;




	 function findFinall($order_sn,$db,&$final_orders = array()){
	 		$is_copy = 0; 
	 		$is_realloc = 0;
	 		$is_split = 0;
	 		echo $order_sn;
	 		// echo $order_id['order_id'] = $db->find_Order_id($result);
	 		$res = $db->find_Order_id($order_sn);
	 		$order_id = $res[0]['order_id'];
	 		
	 		$copy_order = $db->find_Copy_order($order_id);
	 		$split_orders = $db->find_Split_order($order_id);
	 		$res = $db->isRealloc($order_id);
	 		if(!empty($res)){
	 			$deal_code =  $res[0]['deal_code'];
	 			$realloc_orders = $db->find_Realloc_order($deal_code);
 				sort_Realloc($realloc_orders);
 				$realloc_order = $realloc_orders[0];
	 		}
	 		if(!empty($copy_order)){
	 			$is_copy =1 ;
	 			echo '是在复制订单:'.$is_copy ; 
	 			findFinall($copy_order[0]['order_sn'],$db,$final_orders);

	 		}
	 		if(!empty($split_orders)){
	 			$is_split =1 ;
	 			echo '是在拆单订单:'.$is_split ;
	 			foreach ($split_orders as $split_order ) {

	 				print_r($split_order);
	 				
	 				findFinall($split_order['order_sn'],$db,$final_orders);
	 			}
	 		}
	 		if(!empty($realloc_order)){
	 			$is_realloc =1 ;
	 			echo '是在分配订单:'.$is_realloc ;
	 			// print_r($realloc_order);die;
	 			findFinall($realloc_order['order_sn'],$db,$final_orders);

	 		}
	 		// 
	 		if( $is_realloc == 0 && $is_copy == 0 && $is_split == 0){
	 			$detail = 	$db->find_Detail($order_id);
	 			$final_orders [] = $detail[0];
	 		}

	 		// echo $is_copy ; 
	 		// echo $is_realloc ;
	 		// echo $is_split ;

	 		// die;

	 		return $final_orders;
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

 	// function findFianlRealloc($deal_code,$order_sn,$db,&$final_orders = array()){

 	// 	do {
 	// 		$is_realloc = 0;
	 // 		$res = $db->find_Order_id($order_sn);
		//  	$order_id = $res[0]['order_id'];
		//  	$detail = 	$db->find_Detail($order_id);
		  	 
		//   	 // echo $detail[0]['order_sn']; 
		// 	$res = $db->isRealloc($order_id);
		// 	if(!empty($res)){
		// 		$is_realloc = 1;
		// 		$realloc_orders = $db->find_Realloc_order($deal_code);
		// 					echo "再分配订单列表:";
		// 					// print_r($realloc_orders);
		// 					sort_Realloc($realloc_orders);
		// 					print_r($realloc_orders);

		// 		$oder_sn = $realloc_orders[0]['order_sn']; 
	 			
	 // 		}

	 // 		if($is_realloc ==0){
	 // 			echo
	 // 			$final_orders []  = $detail[0];
	 // 		}
	 // 	} while ($is_realloc == 0);

	 // 	return $final_orders;
 	// }





	function findFianlRealloc($deal_code, $order_sn, $db, &$final_orders = array()){
		$is_realloc = 0;
		$res = $db->find_Order_id($order_sn);
	 	$order_id = $res[0]['order_id'];
	 	$detail = 	$db->find_Detail($order_id);
	 	echo '源订单是:';
	 	print_r($detail);
	 	// echo $detail[0]['order_sn']; 
	 	$res = $db->isRealloc($order_id);
		if(!empty($res)){
				$is_realloc = 1;
 				$deal_code = $res[0]['deal_code'];
 				$realloc_orders = $db->find_Realloc_order($deal_code);
 				sort_Realloc($realloc_orders);
 				echo '再分配订单是:';
 				print_r($realloc_orders);
 				findFianlRealloc($realloc_orders[0]['deal_code'], $detail[0]['order_sn'],$db, $final_orders);

		 
		 }
		
		if($is_realloc == 0){
			
			$final_orders [] = $detail;
		}

		return $final_orders;
	}





 

