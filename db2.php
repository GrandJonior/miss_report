<?php 
	require ("dbconfig.php");//引入配置文件

	class db2{
		public $conn=null;

		public function __construct($config)
		{//构造方法 实例化类时自动调用 
				$this->conn = new mysqli($config['host'],$config['username'],$config['password'],$config['database']) or die(mysql_error());//连接数据库
				
				$this->conn->query("set names ".$config['charset']);
			
		}
		/**
		**根据传入sql语句 查询mysql结果集
		**/
		public function getResult($sql){

			// echo $sql;
			$resource=$this->conn->query($sql) or die($this->conn->error);//查询sql语句
			$res=array();
			while(($row=$resource->fetch_assoc())!=false){
				$res[]=$row;
			}
			return $res;
		}

		/*
			逐条插入临时表
		 */
		public function insertInto($order = array(),$dbname){
			$sql = "INSERT INTO 
						".$dbname."(	
								original_order_sn,
								original_deal_code,
								pay_time,
								channel,
								original_sku_count,
								original_shop_goods_amount,
								final_order_sn,
								final_deal_code,
								final_order_status,
								final_sku_count,
								final_shop_goods_amount,
								rate
								)
					VALUES(
								".$order['original_order_sn'].",	
								'".$order['original_deal_code']."',	
								'".strtotime($order['pay_time'])."',	
								'".$order['channel']."',	
								'".$order['original_sku_count']."',	
								'".$order['original_shop_goods_amount']."',	
								'".$order['final_order_sn']."',	
								'".$order['final_deal_code']."',
								'".$order['final_order_stauts']."',	
								'".$order['final_sku_count']."',	
								'".$order['final_shop_goods_amount']."',	
								'".$order['rate']."'
										)";
			// print_r($order);
			// die;
			$res = $this->conn->query($sql) or die($this->conn->error);

			return $res;
		}

		
		/*
			根据order_sn查询order_id
		 */
		public function find_Order_id($order_sn){
			$sql = "SELECT order_id FROM shymt.order_info WHERE order_sn ='".$order_sn."'";

			$res=self::getResult($sql);

			return $res;
		}


		/*
			查询同一交易号下所有订单
		 */
		public function findAll($deal_code){
			$sql = "select
			 			order_info.order_id,
						order_info.order_sn,
						order_info.deal_code,
						order_info.order_status,
						order_info.shop_goods_amount,
						order_info.sku_count,
						order_info.split_orders,
						order_info.copy_from
					from 
						shymt.order_info 
					where
						deal_code like '".$deal_code."%'";

				$res=self::getResult($sql);

				return $res;
		}



			
			/*
				查询源单金额
			 */
			public function get_Original_Count($start_time, $end_time){

					$sql = "select
							  count(DISTINCT di_order2sa_info.order_sn) as count,
							  sum(di_order2sa_goods.goods_number*di_order2sa_goods.share_price)as amount
							from 
								etam_ims.di_order2sa_info ,etam_ims.di_order2sa_goods 
							where 
								di_order2sa_info.order_sn = di_order2sa_goods.order_sn 
								and IFNULL(realloc_from, '') = ''	
								and from_unixtime(pay_time) >= '".$start_time."'
								and from_unixtime(pay_time) < '".$end_time."'";

					$res=self::getResult($sql);

					return $res;	
			}



			/*
				查询再分配订单明细
			 */
			public function getOriStatus3($start_time, $end_time){


					$sql = "
							SELECT
							 di_order2sa_info.order_sn,
							 di_order2sa_info.deal_code,
							 order_info.order_id
							FROM
							 etam_ims.di_order2sa_info,shymt.order_info
							WHERE
							 FROM_UNIXTIME(di_order2sa_info.pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(di_order2sa_info.pay_time) < '".$end_time."'
							AND di_order2sa_info.order_status = 3
							
							AND IFNULL(di_order2sa_info.realloc_from, '') = ''
							AND di_order2sa_info.order_sn = order_info.order_sn
							";

					$res=self::getResult($sql);
					return $res;	
			}

			
		
			



			/*
				根据order_id,查询order_action检查此单是否被再分配	
			 */
			public function isRealloc($order_id){

				// echo $order_id;
				$sql = "SELECT
							substr(action_note,12) as deal_code 
						from
							shymt.order_action 
						WHERE
							order_id = '".$order_id."' 
						and 
							action_name like '%再分配接口订单'";
				
				$res=self::getResult($sql);
		
				return $res;
		

			}

			/*
				查询同一再分配交易号下的所有订单
			 */
			public function find_Realloc_order($deal_code){
				$sql = "select
				 			order_info.order_id,
							order_info.order_sn,
							order_info.deal_code,
							order_info.order_status,
							order_info.shop_goods_amount,
							order_info.sku_count 
						from shymt.order_info where deal_code = '".$deal_code."'";

				$res=self::getResult($sql);
				return $res;
		

			}


			/*
				根据order_sn查询源单渠道
			 */
			public function find_Tel($order_sn){
				$sql = "select 	
							cangku.tel 			
						from 
							etam_ims.di_order2sa_info,
							dbtest.cangku 
						where di_order2sa_info.order_sn = '".$order_sn."'		
						and di_order2sa_info.sd_code=cangku.ckdm			
						";

				$res=self::getResult($sql);
				return $res;
			}

			/*
				根据order_sn查询支付时间
			 */
			public function find_PayTime($order_sn){
				$sql = "select 	
							from_unixtime(pay_time) as pay_time			
						from 
							etam_ims.di_order2sa_info
						
						where 
							di_order2sa_info.order_sn = '".$order_sn."'					
						";

				$res=self::getResult($sql);
				return $res;
			}


			public function show_Tables(){
				$sql = 'show tables';
				$res = self::getResult($sql);
				return $res;
			}


	}