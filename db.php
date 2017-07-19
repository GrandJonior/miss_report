<?php 
	require ("dbconfig.php");//引入配置文件

	class db{
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
		/**
		** I. 查询所有原订单数
		**/
		public function getDataStep1($date, $start_time, $end_time, $status){

			if($status == 0){
				$sql=

					"SELECT
					 order_status,
					 count(order_sn)
					FROM
					 etam_ims.di_order2sa_info
					WHERE
						batch_id >= '".$start_time."'
					AND batch_id < '".$end_time."'	
					AND IFNULL(realloc_from, '') = ''
					GROUP BY
					 order_status
					";

				}else{
					$sql=
					"SELECT
					 order_status,
					 count(order_sn)
					FROM
					 etam_ims.di_order2sa_info
					WHERE
					 FROM_UNIXTIME(pay_time) >= '".$start_time."'
					AND FROM_UNIXTIME(pay_time) < '".$end_time."'
					AND IFNULL(realloc_from, '') = ''
					GROUP BY
					 order_status
					";
				}
					
						$res=self::getResult($sql);

			return $res;
		}
		/*
			II. 查询被再分配的原订单数

		 */
		public function getDataStep2($start_time, $end_time, $status){

			if($status==0){
				$sql=
					"SELECT
					 count(1)
					FROM
					 etam_ims.di_order2sa_info
					WHERE
					 batch_id >= '".$start_time."'
					AND batch_id < '".$end_time."'
					AND order_status = 3
					AND is_realloc = 1
					AND IFNULL(realloc_from, '') = ''
					";
				}else{
					$sql=
					"SELECT
					 count(1)
					FROM
					 etam_ims.di_order2sa_info
					WHERE
					 FROM_UNIXTIME(pay_time) >= '".$start_time."'
					AND FROM_UNIXTIME(pay_time) < '".$end_time."'
					AND order_status = 3
					AND is_realloc = 1
					AND IFNULL(realloc_from, '') = ''
					";
				}

			
		
			$res=self::getResult($sql);
			return $res;
		}
		/*
			4.1 查找包含多种再分配情况的原订单
			step1 
		 */
		public function getDataStep4_1_1($date,$start_time, $end_time, $status){

			if($status == 0){
				$sql=
					"SELECT
					 original_order_sn
			 		
					FROM
			 		shymt.temp_o2op3_stat_".$date."
			 		WHERE
						original_order_sn in(
							SELECT
								order_sn
							FROM
								etam_ims.di_order2sa_info
							WHERE
								(
							 batch_id >= '".$start_time."'
							AND batch_id < '".$end_time."'								)
						)
					GROUP BY
			 		original_order_sn
					HAVING
			 		sum(rate) > 1";
			 	}else{
			 		$sql=
					"SELECT
					 original_order_sn
			 		
					FROM
			 		shymt.temp_o2op3_stat_".$date."
			 		WHERE
						original_order_sn in(
							SELECT
								order_sn
							FROM
								etam_ims.di_order2sa_info
							WHERE
								(
									FROM_UNIXTIME(pay_time) >= '".$start_time."'
									AND FROM_UNIXTIME(pay_time) < '".$end_time."'
								)
						)
					GROUP BY
			 		original_order_sn
					HAVING
			 		sum(rate) > 1";
				   
			 	}
			
			
			$res=self::getResult($sql);
			

			return $res;
		}

		/*
			4.1 查找包含多种再分配情况的原订单
			step2 
		 */
		public function getDataStep4_1_2($date, $str){

			
				$sql=
				   "SELECT
					 original_order_sn,order_sn,rate,type
					FROM
					 shymt.temp_o2op3_stat_".$date."
					where original_order_sn in 
					(
						".$str."
					)
					ORDER BY original_order_sn";
				
			
			$res=self::getResult($sql);
			return $res;
		}

	

		/**
		** 4.2 从临时表中取结果，按情况分组
		**/
		public function getDataStep4_2($date, $start_time, $end_time, $status){
			if($status == 0)
			{
				$sql=
					"SELECT
					 type,
					 sum(rate)
					FROM
					 shymt.temp_o2op3_stat_".$date."
					WHERE
					 original_order_sn in(
						SELECT
							order_sn
						FROM
							etam_ims.di_order2sa_info
						WHERE
							(
							 batch_id >= '".$start_time."'
							AND batch_id < '".$end_time."'						)
						)
					GROUP BY
					 type";
				}else{
					$sql=
						"SELECT
						 type,
						 sum(rate)
						FROM
						 shymt.temp_o2op3_stat_".$date."
						WHERE
						 original_order_sn in(
							SELECT
								order_sn
							FROM
								etam_ims.di_order2sa_info
							WHERE
								(
									FROM_UNIXTIME(pay_time) >= '".$start_time."'
									AND FROM_UNIXTIME(pay_time) < '".$end_time."'
								)
						)
						GROUP BY
						 type";

				}
			
				 // echo $sql ;die;
		
			$res=$this->getResult($sql);
			return $res;
		}


		/**
		** 4.3 查找再分配源单有订单，所有情况中没有的订单
		**/
		public function getDataStep4_3($date,$start_time, $end_time, $status){

		
			if($status == 0){
				$sql=
					"SELECT
					 order_sn,order_status
					FROM
					 etam_ims.di_order2sa_info
					WHERE
					 batch_id >= '".$start_time."'
					AND batch_id < '".$end_time."'					AND order_status = 3
					AND is_realloc = 1
					AND IFNULL(realloc_from, '') = ''
					and order_sn not in 
					(
					 select original_order_sn from shymt.temp_o2op3_stat_".$date."
					)";

				}else{
					$sql=
					"SELECT
					 order_sn,order_status
					FROM
					 etam_ims.di_order2sa_info
					WHERE
					FROM_UNIXTIME(pay_time) >= '".$start_time."'
					   AND FROM_UNIXTIME(pay_time) < '".$end_time."'
					AND order_status = 3
					AND is_realloc = 1
					AND IFNULL(realloc_from, '') = ''
					and order_sn not in 
					(
					 select original_order_sn from shymt.temp_o2op3_stat_".$date."
					)";
				}

				
			
			
			$res=$this->getResult($sql);
			return $res;
		}


		/*
			计算指定日期内原单数和原单总金额

		 */
		public function count_original_order_All($date,$start_time, $end_time,$status)
		{
			if($status == 0){
				$sql = "SELECT		
						count(DISTINCT di_order2sa_info.order_sn) as num,	
						SUM(etam_ims.di_order2sa_goods.goods_number*etam_ims.di_order2sa_goods.share_price) as amount	
					FROM		
					 etam_ims.di_order2sa_info,		
					 etam_ims.di_order2sa_goods		
					WHERE		
					 batch_id >= '".$start_time."'		
					AND batch_id < '".$end_time."'		
					AND IFNULL(realloc_from, '') = ''		
					and etam_ims.di_order2sa_info.order_sn=etam_ims.di_order2sa_goods.order_sn		
					and etam_ims.di_order2sa_info.order_sn not in 		
					(		
					select original_order_sn from etam_ecbi.temp_o2op3_stat_".$date."	 where type IN	
						(	
							'(5.8)再分配订单 - 需从源订单删除',
							'(5.9)再分配订单 - 需从源订单和再分配订单中删除'
						)
					)	
					";
			}else{
				$sql = "SELECT		
						count(DISTINCT di_order2sa_info.order_sn),	
						SUM(etam_ims.di_order2sa_goods.goods_number*di_order2sa_goods.share_price)	
					FROM		
					 etam_ims.di_order2sa_info,		
					 etam_ims.di_order2sa_goods		
					WHERE		
					FROM_UNIXTIME(pay_time) >= '".$start_time."'
					   AND FROM_UNIXTIME(pay_time) < '".$end_time."'
					AND IFNULL(realloc_from, '') = ''		
					and etam_ims.di_order2sa_info.order_sn=di_order2sa_goods.order_sn		
					and etam_ims.di_order2sa_info.order_sn not in 		
					(		
					select original_order_sn from etam_ecbi.temp_o2op3_stat_".$date."	 where type IN	
						(	
							'(5.8)再分配订单 - 需从源订单删除',
							'(5.9)再分配订单 - 需从源订单和再分配订单中删除'
						)
					)	
					";
			}
			echo "<pre>".$sql."</pre>";die;
			$res = $this->getResult($sql);
			return $res;

		}

		public function original_order_All($date,$start_time, $end_time,$status)
		{

		
			if($status == 0){
				$sql=
					"SELECT
					  di_order2sa_info.order_sn,di_order2sa_info.deal_code,from_unixtime(di_order2sa_info.pay_time), di_order2sa_goods.sku , di_order2sa_goods.goods_number,di_order2sa_goods.share_price, (goods_number*share_price)as amount
					FROM
						etam_ims.di_order2sa_info ,etam_ims.di_order2sa_goods 
					WHERE 
						di_order2sa_info.order_sn = di_order2sa_goods.order_sn 
						AND IFNULL(realloc_from, '') = ''	
						AND from_unixtime(pay_time) >= '".$start_time."'
						AND from_unixtime(pay_time) < '".$end_time."'
					";
			}else{
					$sql=
					"SELECT
					  di_order2sa_info.order_sn,di_order2sa_info.deal_code,from_unixtime(di_order2sa_info.pay_time), di_order2sa_goods.sku , di_order2sa_goods.goods_number,di_order2sa_goods.share_price, (goods_number*share_price)as amount
					FROM
						etam_ims.di_order2sa_info ,etam_ims.di_order2sa_goods 
					WHERE 
						di_order2sa_info.order_sn = di_order2sa_goods.order_sn 
						AND IFNULL(realloc_from, '') = ''	
						AND from_unixtime(pay_time) >= '".$start_time."'
						AND from_unixtime(pay_time) < '".$end_time."'
					";
			}		
			
			$res=$this->getResult($sql);
			return $res;
		}



		public function count_Miss($date,$start_time, $end_time,$status)
		{
		
			if($status == 0){
				$sql=
					"SELECT		
					         sum(order_goods.goods_number),		
					         sum(		
					                   order_goods.goods_number * order_goods.share_price		
					         )		
					FROM		
					         dbtest.order_goods,		
					         shymt.temp_o2op3_stat_".$date."		
					WHERE		
					 shymt.temp_o2op3_stat_".$date.".original_order_sn in(
												SELECT
													order_sn
												FROM
													etam_ims.di_order2sa_info
												WHERE
													(
														FROM_UNIXTIME(pay_time) >= '".$start_time."'
														AND FROM_UNIXTIME(pay_time) < '".$end_time."'
													)
											)

					AND         temp_o2op3_stat_".$date.".order_sn = order_goods.order_sn		
					and temp_o2op3_stat_".$date.".type in 		
					(		
					'(3.1)再分配订单 - 分不出去,不含复制订单（原单和新单）',		
					'(3.2)再分配订单 - 分不出去,复制新单',		
					'(4.1)再分配订单 - 顾客退款 - 客服截停,不含复制订单（原单和新单）',		
					'(4.2)再分配订单 - 顾客退款 - 客服截停,复制订单',		
					'(5.1)再分配订单 - 超过最大次数 - EC自动停止再分配',		
					'(5.2)再分配订单 - 超过付款时间72小时，停止自动再分配',		
					'(5.5)再分配订单 - 原单超过付款时间72小时，客服手动分配后未分到SA，仍无效',		
					'(5.6)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA后无效',		
					'(5.7)再分配订单 - 原单超过付款时间72小时，源单直接无效',		
					'(6.1)再分配订单 - 已上传SA，客服误操作取消订单,再分配订单误操作',		
					'(6.2)源订单 - 客服误操作',		
					'(7.1)再分配订单 - 9100库位缺货 - EC自动停止再分配',		
					'(8.1)源订单 - 取消未再分配',		
					'(9.1)源订单 买家退款，EC自动停止再分配',		
					'(9.2)源订单 买家部分退款，EC自动停止再分配',		
					'(9.3)再分配订单 - 顾客退款 - EC自动停止再分配',		
					'(9.4)再分配订单 - 顾客部分退款退款 - EC自动停止再分配',			
					'(11.1)再分配订单 - 客服手动修改商品'		
					)";
			}else{
					$sql=
					"SELECT		
					         sum(order_goods.goods_number),		
					         sum(		
					                   order_goods.goods_number * order_goods.share_price		
					         )		
					FROM		
					         dbtest.order_goods,		
					         shymt.temp_o2op3_stat_".$date."		
					WHERE		
					 shymt.temp_o2op3_stat_".$date.".original_order_sn in(
												SELECT
													order_sn
												FROM
													etam_ims.di_order2sa_info
												WHERE
													(
														FROM_UNIXTIME(pay_time) >= '".$start_time."'
														AND FROM_UNIXTIME(pay_time) < '".$end_time."'
													)
											)

					AND         temp_o2op3_stat_".$date.".order_sn = order_goods.order_sn		
					and temp_o2op3_stat_".$date.".type in 		
					(		
					'(3.1)再分配订单 - 分不出去,不含复制订单（原单和新单）',		
					'(3.2)再分配订单 - 分不出去,复制新单',		
					'(4.1)再分配订单 - 顾客退款 - 客服截停,不含复制订单（原单和新单）',		
					'(4.2)再分配订单 - 顾客退款 - 客服截停,复制订单',		
					'(5.1)再分配订单 - 超过最大次数 - EC自动停止再分配',		
					'(5.2)再分配订单 - 超过付款时间72小时，停止自动再分配',		
					'(5.5)再分配订单 - 原单超过付款时间72小时，客服手动分配后未分到SA，仍无效',		
					'(5.6)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA后无效',		
					'(5.7)再分配订单 - 原单超过付款时间72小时，源单直接无效',		
					'(6.1)再分配订单 - 已上传SA，客服误操作取消订单,再分配订单误操作',		
					'(6.2)源订单 - 客服误操作',		
					'(7.1)再分配订单 - 9100库位缺货 - EC自动停止再分配',		
					'(8.1)源订单 - 取消未再分配',		
					'(9.1)源订单 买家退款，EC自动停止再分配',		
					'(9.2)源订单 买家部分退款，EC自动停止再分配',		
					'(9.3)再分配订单 - 顾客退款 - EC自动停止再分配',		
					'(9.4)再分配订单 - 顾客部分退款退款 - EC自动停止再分配',			
					'(11.1)再分配订单 - 客服手动修改商品'		
					)";
			}		
			
			$res=$this->getResult($sql);
			return $res;
		}


		public function Miss_All($date,$start_time, $end_time,$status)
		{
		
			if($status == 0){
				$sql=
						"SELECT		
						         shymt.temp_o2op3_stat_".$date.".original_order_sn,shymt.temp_o2op3_stat_".$date.".order_sn,		
						         	
						                   (order_goods.goods_number * order_goods.share_price)as amount,
															cangku.tel
						        		
						FROM		
						         dbtest.order_goods,		
						         shymt.temp_o2op3_stat_".$date.",
										 dbtest.cangku		,
										 etam_ims.di_order2sa_info
						WHERE		
						 shymt.temp_o2op3_stat_".$date.".original_order_sn in(
													SELECT
														order_sn
													FROM
														etam_ims.di_order2sa_info
													WHERE
														(
															FROM_UNIXTIME(pay_time) >= '".$start_time."'
															AND FROM_UNIXTIME(pay_time) < '".$end_time."'
														)
												)

						AND         temp_o2op3_stat_".$date.".order_sn = order_goods.order_sn
						AND	 temp_o2op3_stat_".$date.".original_order_sn=di_order2sa_info.order_sn					
						and di_order2sa_info.sd_code=cangku.ckdm					
											
								
						and temp_o2op3_stat_".$date.".type in 		
						(		
						'(3.1)再分配订单 - 分不出去,不含复制订单（原单和新单）',		
						'(3.2)再分配订单 - 分不出去,复制新单',		
						'(4.1)再分配订单 - 顾客退款 - 客服截停,不含复制订单（原单和新单）',		
						'(4.2)再分配订单 - 顾客退款 - 客服截停,复制订单',		
						'(5.1)再分配订单 - 超过最大次数 - EC自动停止再分配',		
						'(5.2)再分配订单 - 超过付款时间72小时，停止自动再分配',		
						'(5.5)再分配订单 - 原单超过付款时间72小时，客服手动分配后未分到SA，仍无效',		
						'(5.6)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA后无效',		
						'(5.7)再分配订单 - 原单超过付款时间72小时，源单直接无效',		
						'(6.1)再分配订单 - 已上传SA，客服误操作取消订单,再分配订单误操作',		
						'(6.2)源订单 - 客服误操作',		
						'(7.1)再分配订单 - 9100库位缺货 - EC自动停止再分配',		
						'(8.1)源订单 - 取消未再分配',		
						'(9.1)源订单 买家退款，EC自动停止再分配',		
						'(9.2)源订单 买家部分退款，EC自动停止再分配',		
						'(9.3)再分配订单 - 顾客退款 - EC自动停止再分配',		
						'(9.4)再分配订单 - 顾客部分退款退款 - EC自动停止再分配',		
						'(10.1)再分配订单，在E3接口表中尚未转单',		
						'(10.2)再分配订单，尚未发送至POS中间表',		
						'(11.1)再分配订单 - 客服手动修改商品'		
						) ";
		
		
			}else{
					$sql=
						"SELECT		
						         shymt.temp_o2op3_stat_".$date.".original_order_sn,shymt.temp_o2op3_stat_".$date.".order_sn,		
						         	
						                   (order_goods.goods_number * order_goods.share_price)as amount,
															cangku.tel
						        		
						FROM		
						         dbtest.order_goods,		
						         shymt.temp_o2op3_stat_".$date.",
										 dbtest.cangku		,
										 etam_ims.di_order2sa_info
						WHERE		
						 shymt.temp_o2op3_stat_".$date.".original_order_sn in(
													SELECT
														order_sn
													FROM
														etam_ims.di_order2sa_info
													WHERE
														(
															FROM_UNIXTIME(pay_time) >= '".$start_time."'
															AND FROM_UNIXTIME(pay_time) < '".$end_time."'
														)
												)

						AND         temp_o2op3_stat_".$date.".order_sn = order_goods.order_sn
						AND	 temp_o2op3_stat_".$date.".original_order_sn=di_order2sa_info.order_sn					
						and di_order2sa_info.sd_code=cangku.ckdm					
											
								
						and temp_o2op3_stat_".$date.".type in 		
						(		
						'(3.1)再分配订单 - 分不出去,不含复制订单（原单和新单）',		
						'(3.2)再分配订单 - 分不出去,复制新单',		
						'(4.1)再分配订单 - 顾客退款 - 客服截停,不含复制订单（原单和新单）',		
						'(4.2)再分配订单 - 顾客退款 - 客服截停,复制订单',		
						'(5.1)再分配订单 - 超过最大次数 - EC自动停止再分配',		
						'(5.2)再分配订单 - 超过付款时间72小时，停止自动再分配',		
						'(5.5)再分配订单 - 原单超过付款时间72小时，客服手动分配后未分到SA，仍无效',		
						'(5.6)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA后无效',		
						'(5.7)再分配订单 - 原单超过付款时间72小时，源单直接无效',		
						'(6.1)再分配订单 - 已上传SA，客服误操作取消订单,再分配订单误操作',		
						'(6.2)源订单 - 客服误操作',		
						'(7.1)再分配订单 - 9100库位缺货 - EC自动停止再分配',		
						'(8.1)源订单 - 取消未再分配',		
						'(9.1)源订单 买家退款，EC自动停止再分配',		
						'(9.2)源订单 买家部分退款，EC自动停止再分配',		
						'(9.3)再分配订单 - 顾客退款 - EC自动停止再分配',		
						'(9.4)再分配订单 - 顾客部分退款退款 - EC自动停止再分配',		
						'(10.1)再分配订单，在E3接口表中尚未转单',		
						'(10.2)再分配订单，尚未发送至POS中间表',		
						'(11.1)再分配订单 - 客服手动修改商品'		
						) ";
			}		
			
			$res=$this->getResult($sql);
			return $res;
		}


		public function hasShip($date,$start_time, $end_time,$status)
		{
		
			if($status == 0){
				$sql=
						"select
						  di_order2sa_info.order_sn,di_order2sa_info.deal_code,from_unixtime(di_order2sa_info.pay_time), di_order2sa_goods.sku , di_order2sa_goods.goods_number,di_order2sa_goods.share_price, (goods_number*share_price)as amount
						from 
							etam_ims.di_order2sa_info ,etam_ims.di_order2sa_goods 
						where 
							di_order2sa_info.order_sn = di_order2sa_goods.order_sn 

							and from_unixtime(pay_time) >= '".$start_time."'
							and from_unixtime(pay_time) < '".$end_time."'
							and order_status = 5";
		
		
			}else{
					$sql=
						"SELECT
						  di_order2sa_info.order_sn,di_order2sa_info.deal_code,from_unixtime(di_order2sa_info.pay_time), di_order2sa_goods.sku , di_order2sa_goods.goods_number,di_order2sa_goods.share_price, (goods_number*share_price)as amount
						FROM 
							etam_ims.di_order2sa_info ,etam_ims.di_order2sa_goods 
						WHERE 
							di_order2sa_info.order_sn = di_order2sa_goods.order_sn 

							AND from_unixtime(pay_time) >= '".$start_time."'
							AND from_unixtime(pay_time) < '".$end_time."'
							AND order_status = 5";
		
			}
		}	

// public function hasShip($date,$start_time, $end_time,$status)
// 		{
		
// 			if($status == 0){
// 				$sql=
// 						"select
// 						  count(di_order2sa_info.order_sn) , sum(di_order2sa_goods.goods_number*di_order2sa_goods.share_price)as amount
// 						from 
// 							etam_ims.di_order2sa_info ,etam_ims.di_order2sa_goods 
// 						where 
// 							di_order2sa_info.order_sn = di_order2sa_goods.order_sn 

// 							and from_unixtime(pay_time) >= '".$start_time."'
// 							and from_unixtime(pay_time) < '".$end_time."'
// 							and order_status = 5";
		
		
// 			}else{
// 					$sql=
// 						"SELECT
// 						  di_order2sa_info.order_sn,di_order2sa_info.deal_code,from_unixtime(di_order2sa_info.pay_time), di_order2sa_goods.sku , di_order2sa_goods.goods_number,di_order2sa_goods.share_price, (goods_number*share_price)as amount
// 						FROM 
// 							etam_ims.di_order2sa_info ,etam_ims.di_order2sa_goods 
// 						WHERE 
// 							di_order2sa_info.order_sn = di_order2sa_goods.order_sn 

// 							AND from_unixtime(pay_time) >= '".$start_time."'
// 							AND from_unixtime(pay_time) < '".$end_time."'
// 							AND order_status = 5";
		
// 			}

			
// 			$res=$this->getResult($sql);
// 			return $res;
// 		}



// 		public function hasRejectMiss($date, $start_time, $end_time, $status){

// 			if($status == 0){
// 				$sql = 
// 				"SELECT		
// 				      DISTINCT shymt.temp_o2op3_stat_".$date.".order_sn,		
// 							shymt.temp_o2op3_stat_".$date.".original_order_sn,
				         	
				                   
				        		
// 				FROM		
// 				         shymt.temp_o2op3_stat_".$date.",
// 								 etam_ims.di_order2sa_info,
// 								 shymt.temp_o2op3_stat_missdelete
// 				WHERE		
// 				 shymt.temp_o2op3_stat_".$date.".original_order_sn in(
// 											SELECT
// 												order_sn
// 											FROM
// 												etam_ims.di_order2sa_info
// 											WHERE
// 												(
// 													FROM_UNIXTIME(pay_time) >= '".$start_time."'
// 													AND FROM_UNIXTIME(pay_time) < '".$end_time."'
// 												)
// 										)

// 				AND     temp_o2op3_stat_".$date.".order_sn not in(
// 							SELECT order_sn from shymt.temp_o2op3_stat_missdelete WHERE status <>''

// 				)
// 				AND	 temp_o2op3_stat_".$date.".original_order_sn=di_order2sa_info.order_sn					
// 				 AND  		temp_o2op3_stat_".$date.".original_order_sn in (
// 				    						SELECT * from temp_o2op3_stat_original
// 				   			)
				    
// 				    AND  		temp_o2op3_stat_".$date.".order_sn in (
// 				    						SELECT * from temp_o2op3_stat_original
// 				   				)					
				 		
// 				and temp_o2op3_stat_".$date.".type in 		
// 				(		
// 				'(3.1)再分配订单 - 分不出去,不含复制订单（原单和新单）',		
// 				'(3.2)再分配订单 - 分不出去,复制新单',		
// 				'(4.1)再分配订单 - 顾客退款 - 客服截停,不含复制订单（原单和新单）',		
// 				'(4.2)再分配订单 - 顾客退款 - 客服截停,复制订单',		
// 				'(5.1)再分配订单 - 超过最大次数 - EC自动停止再分配',		
// 				'(5.2)再分配订单 - 超过付款时间72小时，停止自动再分配',		
// 				'(5.5)再分配订单 - 原单超过付款时间72小时，客服手动分配后未分到SA，仍无效',		
// 				'(5.6)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA后无效',		
// 				'(5.7)再分配订单 - 原单超过付款时间72小时，源单直接无效',		
// 				'(6.1)再分配订单 - 已上传SA，客服误操作取消订单,再分配订单误操作',		
// 				'(6.2)源订单 - 客服误操作',		
// 				'(7.1)再分配订单 - 9100库位缺货 - EC自动停止再分配',		
// 				'(8.1)源订单 - 取消未再分配',		
// 				'(9.1)源订单 买家退款，EC自动停止再分配',		
// 				'(9.2)源订单 买家部分退款，EC自动停止再分配',		
// 				'(9.3)再分配订单 - 顾客退款 - EC自动停止再分配',		
// 				'(9.4)再分配订单 - 顾客部分退款退款 - EC自动停止再分配',		
						
// 				'(11.1)再分配订单 - 客服手动修改商品'		
// 				) 
// 				";
		
		
		

// 			}else{
// 				$sql = 
// 				"SELECT		
// 				      DISTINCT shymt.temp_o2op3_stat_".$date.".order_sn,		
// 							shymt.temp_o2op3_stat_".$date.".original_order_sn,
				         	
				                   
				        		
// 				FROM		
// 				         shymt.temp_o2op3_stat_".$date.",
// 								 etam_ims.di_order2sa_info,
// 								 shymt.temp_o2op3_stat_missdelete
// 				WHERE		
// 				 shymt.temp_o2op3_stat_".$date.".original_order_sn in(
// 											SELECT
// 												order_sn
// 											FROM
// 												etam_ims.di_order2sa_info
// 											WHERE
// 												(
// 													FROM_UNIXTIME(pay_time) >= '".$start_time."'
// 													AND FROM_UNIXTIME(pay_time) < '".$end_time."'
// 												)
// 										)

// 				AND     temp_o2op3_stat_".$date.".order_sn not in(
// 							SELECT order_sn from shymt.temp_o2op3_stat_missdelete WHERE status <>''

// 				)
// 				AND	 temp_o2op3_stat_".$date.".original_order_sn=di_order2sa_info.order_sn					
// 				 AND  		temp_o2op3_stat_".$date.".original_order_sn in (
// 				    						SELECT * from temp_o2op3_stat_original
// 				   			)
				    
// 				    AND  		temp_o2op3_stat_".$date.".order_sn in (
// 				    						SELECT * from temp_o2op3_stat_original
// 				   				)					
				 		
// 				and temp_o2op3_stat_".$date.".type in 		
// 				(		
// 				'(3.1)再分配订单 - 分不出去,不含复制订单（原单和新单）',		
// 				'(3.2)再分配订单 - 分不出去,复制新单',		
// 				'(4.1)再分配订单 - 顾客退款 - 客服截停,不含复制订单（原单和新单）',		
// 				'(4.2)再分配订单 - 顾客退款 - 客服截停,复制订单',		
// 				'(5.1)再分配订单 - 超过最大次数 - EC自动停止再分配',		
// 				'(5.2)再分配订单 - 超过付款时间72小时，停止自动再分配',		
// 				'(5.5)再分配订单 - 原单超过付款时间72小时，客服手动分配后未分到SA，仍无效',		
// 				'(5.6)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA后无效',		
// 				'(5.7)再分配订单 - 原单超过付款时间72小时，源单直接无效',		
// 				'(6.1)再分配订单 - 已上传SA，客服误操作取消订单,再分配订单误操作',		
// 				'(6.2)源订单 - 客服误操作',		
// 				'(7.1)再分配订单 - 9100库位缺货 - EC自动停止再分配',		
// 				'(8.1)源订单 - 取消未再分配',		
// 				'(9.1)源订单 买家退款，EC自动停止再分配',		
// 				'(9.2)源订单 买家部分退款，EC自动停止再分配',		
// 				'(9.3)再分配订单 - 顾客退款 - EC自动停止再分配',		
// 				'(9.4)再分配订单 - 顾客部分退款退款 - EC自动停止再分配',		
						
// 				'(11.1)再分配订单 - 客服手动修改商品'		
// 				) 
// 				";
// 			}

// 			$res = $this->getResult($sql);
// 			return $res ;

// 		}



// 		public function warningEarly(){
// 			$sql = 'select order_sn , batch_id ,timer1 from etam_ims.di_order2sa_info';
// 			$res = $this->getResult($sql);

// 			return $res;
// 		}


		public function showTables(){
			$sql = "SHOW TABLES LIKE 'temp_o2op3_stat_%' ";
			$res=$this->getResult($sql);
			
			// print_r($res);
			// die();
			return $res;

		}




}
	
?>