<?php 

	set_time_limit(0);

	date_default_timezone_set("PRC");
	$dir=dirname(__FILE__);//查找当前脚本所在路径
	require $dir."/db2.php";//引入mysql操作类文件
	require $dir."/PHPExcel/PHPExcel.php";
	// echo time();die;
	$db = new db2($phpexcel);
 	// $results = $db->getOriStatus3();
 	$i= 0; 

 		$res = $db->isRealloc('2354305');
 		echo '<pre>';
 		print_r($res[0]);
 		die;
 		$final_orders = array();
 		$all_orders = array();
 		$original_order = 0;
 		$all_orders = $db->findAll('3351881485291471');
 		echo '<pre>';
 		print_r($all_orders);
 		find_Realloc_sn($db, $all_orders);
 		print_r($all_orders);
 		foreach ($all_orders as $all_order) {
 			 if($all_order['order_sn'] == '1703309780626'){
 			 	$original_order = $all_order;
 			 }
 		}
 		findFinall($original_order,$all_orders, $final_orders);
 		echo '<pre>';
 		print_r($final_orders);die;
 		// echo $order_id['order_id'] = $db->find_Order_id('1703239093315');
 	foreach ($results as $result) {
 
 		$final_orders = array();
 		$all_orders = array();
 		 echo '<pre>';
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
 		echo '源单是:';
 		print_r($original_order);
 		findFinall($original_order,$all_orders, $final_orders);
 		echo '最终订单是';
 		print_r($final_orders);
 		// $res = $db->find_Order_id($result['order_sn']);
	 	// $order_id = $res[0]['order_id'];
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
 		//
 		
 		 // print_r($final_orders);
 		
 		
 		$i++;
 		echo '第'.$i.'次循环';
 		if($i>5){
 			break;
 		}
 	}
 	echo $i;
 	 function find_Realloc_sn($db,&$all_orders = array()){
			foreach ($all_orders as &$all_order) {
			 			$res = $db->isRealloc($all_order['order_id']);
			 			$all_order['realloc_sn'] =  "";
				 		if(!empty($res)){
				 			$deal_code =  $res[0]['deal_code'];
				 			$realloc_orders = $db->find_Realloc_order($deal_code);
			 				sort_Realloc($realloc_orders);
			 				$all_order['realloc_sn'] = $realloc_orders[0]['order_sn'];
			 			// print_r($all_order);
			 			}
			 			// print_r($all_orders);# code...
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
	 			echo '是被再分配订单:'.$is_realloc ;
	 			foreach ($realloc_orders as $realloc_order) {
	 				findFinall($realloc_order, $all_orders, $final_orders);
	 			}
	 		}
	 		if(!empty($copy_orders)){
	 			$is_copy =1 ;
	 			echo '是被复制订单:';
	 			foreach ($copy_orders as $copy_order) {
	 				// print_r($copy_order);
	 				findFinall($copy_order, $all_orders, $final_orders);
	 			}
	 		}
	 		if(!empty($split_orders)){
	 			$is_split =1 ;
	 			echo '是被拆单订单:'.$is_split ;
	 			foreach ($split_orders as $split_order) {
	 				print_r($split_order);
	 				findFinall($split_order, $all_orders, $final_orders);
	 			}
	 		}
	 		
	 		// 
	 		if( $is_realloc == 0 && $is_copy == 0 && $is_split == 0){
	 			// $detail = 	$db->find_Detail($order_id);
	 			$final_orders [] = $original_order;
	 		}

	 		// echo $is_copy ; 
	 		// echo $is_realloc ;
	 		// echo $is_split ;

	 		// die;

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

 			// print_r($realloc_orders);
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





 

