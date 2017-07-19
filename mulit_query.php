<?php 
	set_time_limit(0);
	date_default_timezone_set("PRC");
	$start_time = $_POST["start_time"];
	$end_time = $_POST["end_time"];
	$dbname = date("md",strtotime($start_time));



	$types = array();

	$types [] = '(1.1)再分配订单 - SA已发货';
$sql =
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
			original_order_sn,etam_ims.di_order2sa_info.order_sn,
			SUM(
				etam_ims.di_order2sa_goods.goods_number / etam_ims.di_order2sa_goods.original_goods_count
			),
			'(1.1)再分配订单 - SA已发货'
		FROM
			etam_ims.di_order2sa_info,
			etam_ims.di_order2sa_goods
		WHERE
			order_status = 5
		AND etam_ims.di_order2sa_info.order_sn = etam_ims.di_order2sa_goods.order_sn
		AND original_order_sn IN (
			SELECT
				order_sn
			FROM
				etam_ims.di_order2sa_info
			WHERE
				FROM_UNIXTIME(pay_time) >= '".$start_time."'
			AND FROM_UNIXTIME(pay_time) < '".$end_time."'
			AND order_status = 3
			AND is_realloc = 1
			AND IFNULL(realloc_from, '') = ''
		)
		GROUP BY
			original_order_sn;";



	$types [] = '(1.2)再分配订单 - ILC已发货';
 $sql .= 
	"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
		SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
		sum(
			dbtest.order_goods.goods_number / RIGHT (
				dbtest.order_goods.outer_goods_name,
				1
			)
		),
		'(1.2)再分配订单 - ILC已发货'
	FROM
		dbtest.order_info,
		dbtest.order_goods
	WHERE
		dbtest.order_info.order_sn = dbtest.order_goods.order_sn
	AND dbtest.order_info.order_status = 5
	AND fhck_id < 100
	AND copy_from = 0
	AND outer_goods_name LIKE 'realloc%'
	AND SUBSTR(outer_goods_name, 25, 13) IN (
		SELECT
			order_sn
		FROM
			etam_ims.di_order2sa_info
		WHERE
			(
				FROM_UNIXTIME(pay_time) >= '".$start_time."'
				AND FROM_UNIXTIME(pay_time) < '".$end_time."'
			)
		AND order_status = 3
		AND is_realloc = 1
		AND is_realloc_new = 0
		AND IFNULL(realloc_from, '') = ''
	)
	GROUP BY
		SUBSTR(outer_goods_name, 25, 13);";
	$types [] = '(1.3)客服复制再分配订单 - ILC已发货';
$sql .=

	"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
			SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
			sum(
				dbtest.order_goods.goods_number / RIGHT (
					dbtest.order_goods.outer_goods_name,
					1
				)
			),
			'(1.3)客服复制再分配订单 - ILC已发货'
		FROM
			dbtest.order_info,
			dbtest.order_goods
		WHERE
			order_goods.order_id = order_info.order_id
		AND order_goods.outer_goods_name LIKE 'realloc%'
		AND order_info.pay_status = 2
		AND order_info.order_status = 5
		AND order_info.shipping_status = 7
		AND order_info.fhck_id < 100
		AND order_info.copy_from != 0
		AND NOT EXISTS (
			SELECT
				1
			FROM
				etam_ims.di_order2sa_info
			WHERE
				etam_ims.di_order2sa_info.order_sn = order_info.order_sn
		)
		AND EXISTS (
			SELECT
				1
			FROM
				etam_ims.di_order2sa_info
			WHERE
				etam_ims.di_order2sa_info.order_status = 3
			AND etam_ims.di_order2sa_info.is_realloc = 1
			AND etam_ims.di_order2sa_info.is_realloc_new = 0
			AND IFNULL(
				etam_ims.di_order2sa_info.realloc_from,
				''
			) = ''
			AND etam_ims.di_order2sa_info.order_sn = SUBSTR(outer_goods_name, 25, 13)
			AND FROM_UNIXTIME(pay_time) >= '".$start_time."'
			AND FROM_UNIXTIME(pay_time) < '".$end_time."'
		)
		GROUP BY
			SUBSTR(outer_goods_name, 25, 13);";



	$types [] = '(2.1)再分配订单 - ILC未发货(大仓调拨)';
	$sql .=
	"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
			SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
			sum(
				dbtest.order_goods.goods_number / RIGHT (
					dbtest.order_goods.outer_goods_name,
					1
				)
			),
			'(2.1)再分配订单 - ILC未发货(大仓调拨)'
		FROM
			dbtest.order_info,
			dbtest.order_goods
		WHERE
			dbtest.order_info.order_sn = dbtest.order_goods.order_sn
		AND dbtest.order_info.order_status NOT IN (3, 5)
		AND fhck_id < 100
		AND dbtest.order_info.status_yfp_qh != 2
		AND outer_goods_name LIKE 'realloc%'
		AND SUBSTR(outer_goods_name, 25, 13) IN (
			SELECT
				order_sn
			FROM
				etam_ims.di_order2sa_info
			WHERE
				(
					FROM_UNIXTIME(pay_time) >= '".$start_time."'
					AND FROM_UNIXTIME(pay_time) < '".$end_time."'
				)
			AND order_status = 3
			AND is_realloc = 1
			AND is_realloc_new = 0
			AND IFNULL(realloc_from, '') = ''
		)
		GROUP BY
			SUBSTR(outer_goods_name, 25, 13);";



	$types [] = '(2.2)再分配订单 - SA未发货';
	$sql .=
				"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					original_order_sn,etam_ims.di_order2sa_info.order_sn,
					SUM(
						etam_ims.di_order2sa_goods.goods_number / etam_ims.di_order2sa_goods.original_goods_count
					),
					'(2.2)再分配订单 - SA未发货'
				FROM
					etam_ims.di_order2sa_info,
					etam_ims.di_order2sa_goods
				WHERE
					order_status = 1
				AND etam_ims.di_order2sa_info.order_sn = etam_ims.di_order2sa_goods.order_sn
				AND original_order_sn IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND IFNULL(realloc_from, '') = ''
				)
				GROUP BY
					original_order_sn;";

	$types [] = '(3.1)再分配订单 - 分不出去,不含复制订单（原单和新单）';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
					sum(
						dbtest.order_goods.goods_number / RIGHT (
							dbtest.order_goods.outer_goods_name,
							1
						)
					),
					'(3.1)再分配订单 - 分不出去,不含复制订单（原单和新单）'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND fhck_id < 100
				AND dbtest.order_info.status_yfp_qh = 2
				AND dbtest.order_info.is_split = 0
				AND outer_goods_name LIKE 'realloc%'
				AND SUBSTR(outer_goods_name, 25, 13) IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND NOT EXISTS (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						order_sn = dbtest.order_info.order_sn
				)
				AND NOT EXISTS (
					SELECT
						order_id
					FROM
						dbtest.order_action
					WHERE
						order_id = dbtest.order_info.order_id
					AND action_name = '复制订单'
				)
				GROUP BY
					SUBSTR(outer_goods_name, 25, 13);";


	$types [] = '(3.2)再分配订单 - 分不出去,复制新单';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
					sum(
						dbtest.order_goods.goods_number / RIGHT (
							dbtest.order_goods.outer_goods_name,
							1
						)
					),
					'(3.2)再分配订单 - 分不出去,复制新单'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND fhck_id < 100
				AND dbtest.order_info.status_yfp_qh = 2
				AND dbtest.order_info.is_split = 0
				AND outer_goods_name LIKE 'realloc%'
				AND SUBSTR(outer_goods_name, 25, 13) IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND NOT EXISTS (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						order_sn = dbtest.order_info.order_sn
				)
				AND dbtest.order_info.is_copy = 1
				GROUP BY
					SUBSTR(outer_goods_name, 25, 13);";


$types [] = '(4.1)再分配订单 - 顾客退款 - 客服截停,不含复制订单（原单和新单）';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
					sum(
						dbtest.order_goods.goods_number / RIGHT (
							dbtest.order_goods.outer_goods_name,
							1
						)
					),
					'(4.1)再分配订单 - 顾客退款 - 客服截停,不含复制订单（原单和新单）'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND dbtest.order_info.status_yfp_qh != 2
				AND dbtest.order_info.is_split = 0
				AND dbtest.order_info.order_status = 3
				AND outer_goods_name LIKE 'realloc%'
				AND SUBSTR(outer_goods_name, 25, 13) IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND NOT EXISTS (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						order_sn = dbtest.order_info.order_sn
				)
				AND NOT EXISTS (
					SELECT
						order_id
					FROM
						dbtest.order_action
					WHERE
						order_id = dbtest.order_info.order_id
					AND action_name = '复制订单'
				)
				GROUP BY
					SUBSTR(outer_goods_name, 25, 13);";

$types [] = '(4.2)再分配订单 - 顾客退款 - 客服截停,复制订单';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
			SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
			sum(
				dbtest.order_goods.goods_number / RIGHT (
					dbtest.order_goods.outer_goods_name,
					1
				)
			),
			'(4.2)再分配订单 - 顾客退款 - 客服截停,复制订单'
		FROM
			dbtest.order_info,
			dbtest.order_goods
		WHERE
			dbtest.order_info.order_sn = dbtest.order_goods.order_sn
		AND dbtest.order_info.status_yfp_qh != 2
		AND dbtest.order_info.is_split = 0
		AND dbtest.order_info.order_status = 3
		AND outer_goods_name LIKE 'realloc%'
		AND SUBSTR(outer_goods_name, 25, 13) IN (
			SELECT
				order_sn
			FROM
				etam_ims.di_order2sa_info
			WHERE
				(
					FROM_UNIXTIME(pay_time) >= '".$start_time."'
					AND FROM_UNIXTIME(pay_time) < '".$end_time."'
				)
			AND order_status = 3
			AND is_realloc = 1
			AND is_realloc_new = 0
			AND IFNULL(realloc_from, '') = ''
		)
		AND NOT EXISTS (
			SELECT
				order_sn
			FROM
				etam_ims.di_order2sa_info
			WHERE
				order_sn = dbtest.order_info.order_sn
		)
		AND dbtest.order_info.is_copy = 1
		GROUP BY
			SUBSTR(outer_goods_name, 25, 13);";

	$types [] = '(5.1)再分配订单 - 超过最大次数 - EC自动停止再分配@@';	

	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
					sum(
						dbtest.order_goods.goods_number / RIGHT (
							dbtest.order_goods.outer_goods_name,
							1
						)
					),
					'(5.1)再分配订单 - 超过最大次数 - EC自动停止再分配@@'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND dbtest.order_info.status_yfp_qh != 2
				AND dbtest.order_info.is_split = 0
				AND dbtest.order_info.order_status = 3
				AND outer_goods_name LIKE 'realloc%'
				AND SUBSTR(outer_goods_name, 25, 13) IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND EXISTS (
					SELECT
						order_id
					FROM
						dbtest.order_action
					WHERE
						order_id = dbtest.order_info.order_id
					AND action_name LIKE '%超过最大次数'
				)
				GROUP BY
					SUBSTR(outer_goods_name, 25, 13);";

	$types [] = '(5.2)再分配订单 - 超过付款时间72小时，停止自动再分配';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
					sum(
						dbtest.order_goods.goods_number / RIGHT (
							dbtest.order_goods.outer_goods_name,
							1
						)
					),
					'(5.2)再分配订单 - 超过付款时间72小时，停止自动再分配'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND dbtest.order_info.status_yfp_qh != 2
				AND dbtest.order_info.is_split = 0
				AND dbtest.order_info.order_status = 3
				AND outer_goods_name LIKE 'realloc%'
				AND SUBSTR(outer_goods_name, 25, 13) IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND NOT EXISTS (
						SELECT
						1
					FROM
						(
							SELECT
								copy_from AS cf
							FROM
								dbtest.order_info
							WHERE
								copy_from <> 0
						) AS lin
					WHERE
						cf = dbtest.order_info.order_id
				)
				AND EXISTS (
					SELECT
						order_id
					FROM
						dbtest.order_action
					WHERE
						order_id = dbtest.order_info.order_id
					AND action_name = 'O2OP3发货超时'
				)
				GROUP BY
					SUBSTR(outer_goods_name, 25, 13);";

	$types [] = '(5.3)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
					lin.os,dbtest.order_info.order_sn,
					1,
					'(5.3)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA'
				FROM
					etam_ims.di_order2sa_info,
					dbtest.order_info,
					dbtest.order_goods,
					(
						SELECT
							dbtest.order_info.order_sn AS os,
							dbtest.order_info.deal_code AS od,
							dbtest.order_info.sku_count AS osc,
							dbtest.order_goods.sku AS osku,
							'(5.3)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA'
						FROM
							dbtest.order_info,
							dbtest.order_goods
						WHERE
							dbtest.order_info.order_sn = dbtest.order_goods.order_sn
						AND dbtest.order_info.status_yfp_qh != 2
						AND dbtest.order_info.is_split = 0
						AND dbtest.order_info.order_status = 3
						AND dbtest.order_info.is_copy = 0
						AND outer_goods_name NOT LIKE 'realloc%'
						AND dbtest.order_info.order_sn IN (
							SELECT
								order_sn
							FROM
								etam_ims.di_order2sa_info
							WHERE
								(
									FROM_UNIXTIME(pay_time) >= '".$start_time."'
									AND FROM_UNIXTIME(pay_time) < '".$end_time."'
								)
							AND order_status = 3
							AND is_realloc = 1
							AND is_realloc_new = 0
							AND IFNULL(realloc_from, '') = ''
						)
						AND EXISTS (
							SELECT
								order_id
							FROM
								dbtest.order_action
							WHERE
								order_id = dbtest.order_info.order_id
							AND action_name = 'O2OP3发货超时'
						)
					) AS lin
				WHERE
					etam_ims.di_order2sa_info.order_sn = dbtest.order_info.order_sn
				AND dbtest.order_goods.order_sn = dbtest.order_info.order_sn
				AND dbtest.order_info.order_sn <> lin.os
				AND dbtest.order_info.deal_code = lin.od
				AND dbtest.order_info.sku_count = lin.osc
				AND dbtest.order_goods.sku = lin.osku
				AND dbtest.order_info.order_status = 5
				AND IFNULL(
					etam_ims.di_order2sa_info.realloc_from,
					''
				) = '';";

$types [] = '(5.3.1)再分配订单(拆单) - 原单超过付款时间72小时，客服手动分配到SA';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
			lin.os,dbtest.order_info.order_sn,
			dbtest.order_info.sku_count/lin.osc,
			'(5.3.1)再分配订单(拆单) - 原单超过付款时间72小时，客服手动分配到SA'
		FROM
			etam_ims.di_order2sa_info,
			dbtest.order_info,
			dbtest.order_goods,
			(
				SELECT
					dbtest.order_info.order_sn AS os,
					dbtest.order_info.deal_code AS od,
					dbtest.order_info.sku_count AS osc,
					dbtest.order_goods.sku AS osku,
					'(5.3.1)再分配订单(拆单) - 原单超过付款时间72小时，客服手动分配到SA'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND dbtest.order_info.status_yfp_qh != 2
				AND dbtest.order_info.is_split = 0
				AND dbtest.order_info.order_status = 3
				AND dbtest.order_info.is_copy = 0
				AND outer_goods_name NOT LIKE 'realloc%'
				AND dbtest.order_info.order_sn IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND EXISTS (
					SELECT
						order_id
					FROM
						dbtest.order_action
					WHERE
						order_id = dbtest.order_info.order_id
					AND action_name = 'O2OP3发货超时'
				)
			) AS lin
		WHERE
			etam_ims.di_order2sa_info.order_sn = dbtest.order_info.order_sn
		AND dbtest.order_goods.order_sn = dbtest.order_info.order_sn
		AND dbtest.order_info.order_sn <> lin.os
		AND dbtest.order_info.deal_code = lin.od
		AND dbtest.order_info.sku_count != lin.osc
		AND dbtest.order_goods.sku = lin.osku
		AND dbtest.order_info.order_status = 5
		AND IFNULL(
			etam_ims.di_order2sa_info.realloc_from,
			''
		) = '';";

	$types [] = '(5.4)再分配订单 - 原单超过付款时间72小时，客服手动分配未分配到SA';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
					lin.os,dbtest.order_info.order_sn,
					1,
					'(5.4)再分配订单 - 原单超过付款时间72小时，客服手动分配未分配到SA'
				FROM
					dbtest.order_info,
					dbtest.order_goods,
					(
						SELECT
							dbtest.order_info.order_sn AS os,
							dbtest.order_info.deal_code AS od,
							dbtest.order_info.sku_count AS osc,
							dbtest.order_goods.sku AS osku,
							'(5.4)再分配订单 - 原单超过付款时间72小时，客服手动分配未分配到SA'
						FROM
							dbtest.order_info,
							dbtest.order_goods
						WHERE
							dbtest.order_info.order_sn = dbtest.order_goods.order_sn
						AND dbtest.order_info.status_yfp_qh != 2
						AND dbtest.order_info.is_split = 0
						AND dbtest.order_info.order_status = 3
						AND dbtest.order_info.is_copy = 0
						AND outer_goods_name NOT LIKE 'realloc%'
						AND dbtest.order_info.order_sn IN (
							SELECT
								order_sn
							FROM
								etam_ims.di_order2sa_info
							WHERE
								(
									FROM_UNIXTIME(pay_time) >= '".$start_time."'
									AND FROM_UNIXTIME(pay_time) < '".$end_time."'
								)
							AND order_status = 3
							AND is_realloc = 1
							AND is_realloc_new = 0
							AND IFNULL(realloc_from, '') = ''
						)
						AND NOT EXISTS (
							SELECT
								1
							FROM
								dbtest.order_info
							WHERE
								copy_from = dbtest.order_info.order_id
						)
						AND EXISTS (
							SELECT
								order_id
							FROM
								dbtest.order_action
							WHERE
								order_id = dbtest.order_info.order_id
							AND action_name = 'O2OP3发货超时'
						)
					) AS lin
				WHERE
					dbtest.order_goods.order_sn = dbtest.order_info.order_sn
				AND dbtest.order_info.order_sn <> lin.os
				AND dbtest.order_info.deal_code = lin.od
				AND dbtest.order_info.sku_count = lin.osc
				AND dbtest.order_info.order_status = 5
				AND dbtest.order_goods.sku = lin.osku
				AND NOT EXISTS (
					SELECT
						1
					FROM
						etam_ims.di_order2sa_info
					WHERE
						order_sn = dbtest.order_info.order_sn
				);";

	$types [] = '(5.4.1)再分配订单(拆单) - 原单超过付款时间72小时，客服手动分配未分配到SA';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
					lin.os,dbtest.order_info.order_sn,
					dbtest.order_info.sku_count/lin.osc,
					'(5.4.1)再分配订单(拆单) - 原单超过付款时间72小时，客服手动分配未分配到SA'
				FROM
					dbtest.order_info,
					dbtest.order_goods,
					(
						SELECT
							dbtest.order_info.order_sn AS os,
							dbtest.order_info.deal_code AS od,
							dbtest.order_info.sku_count AS osc,
							dbtest.order_goods.sku AS osku,
							'(5.4.1)再分配订单(拆单) - 原单超过付款时间72小时，客服手动分配未分配到SA'
						FROM
							dbtest.order_info,
							dbtest.order_goods
						WHERE
							dbtest.order_info.order_sn = dbtest.order_goods.order_sn
						AND dbtest.order_info.status_yfp_qh != 2
						AND dbtest.order_info.is_split = 0
						AND dbtest.order_info.order_status = 3
						AND dbtest.order_info.is_copy = 0
						AND outer_goods_name NOT LIKE 'realloc%'
						AND dbtest.order_info.order_sn IN (
							SELECT
								order_sn
							FROM
								etam_ims.di_order2sa_info
							WHERE
								(
									FROM_UNIXTIME(pay_time) >= '".$start_time."'
									AND FROM_UNIXTIME(pay_time) < '".$end_time."'
								)
							AND order_status = 3
							AND is_realloc = 1
							AND is_realloc_new = 0
							AND IFNULL(realloc_from, '') = ''
						)
						AND NOT EXISTS (
							SELECT
								1
							FROM
								dbtest.order_info
							WHERE
								copy_from = dbtest.order_info.order_id
						)
						AND EXISTS (
							SELECT
								order_id
							FROM
								dbtest.order_action
							WHERE
								order_id = dbtest.order_info.order_id
							AND action_name = 'O2OP3发货超时'
						)
					) AS lin
				WHERE
					dbtest.order_goods.order_sn = dbtest.order_info.order_sn
				AND dbtest.order_info.order_sn <> lin.os
				AND dbtest.order_info.deal_code = lin.od
				AND dbtest.order_info.sku_count != lin.osc
				AND dbtest.order_info.order_status = 5
				AND dbtest.order_goods.sku = lin.osku
				AND NOT EXISTS (
					SELECT
						1
					FROM
						etam_ims.di_order2sa_info
					WHERE
						order_sn = dbtest.order_info.order_sn
				);";

$types [] = '(5.5)再分配订单 - 原单超过付款时间72小时，客服手动分配后未分到SA，仍无效';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
				    lin.os,
				    dbtest.order_info.order_sn,
				    order_goods.goods_number / lin.goods_number,
				    '(5.5)再分配订单 - 原单超过付款时间72小时，客服手动分配后未分到SA，仍无效'
				FROM
				    dbtest.order_info,
				    dbtest.order_goods,
				    (
				        SELECT
				            dbtest.order_info.order_sn AS os,
				            dbtest.order_info.deal_code AS od,
				            dbtest.order_info.sku_count AS osc,
				            dbtest.order_goods.sku AS osku,
				            sum(order_goods.goods_number) AS goods_number,
				            '(5.5)再分配订单 - 原单超过付款时间72小时，客服手动分配后未分到SA，仍无效'
				        FROM
				            dbtest.order_info,
				            dbtest.order_goods
				        WHERE
				            dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				        AND dbtest.order_info.status_yfp_qh != 2
				        AND dbtest.order_info.is_split = 0
				        AND dbtest.order_info.order_status = 3
				        AND dbtest.order_info.is_copy = 0
				        AND outer_goods_name NOT LIKE 'realloc%'
				        AND dbtest.order_info.order_sn IN (
				            SELECT
				                order_sn
				            FROM
				                etam_ims.di_order2sa_info
				            WHERE
				                (
				                    FROM_UNIXTIME(pay_time) >= '".$start_time."'
				                    AND FROM_UNIXTIME(pay_time) < '".$end_time."'
				                )
				            AND order_status = 3
				            AND is_realloc = 1
				            AND is_realloc_new = 0
				            AND IFNULL(realloc_from, '') = ''
				        )
				        AND NOT EXISTS (
				            SELECT
				                1
				            FROM
				                (
				                    SELECT
				                        copy_from AS cf
				                    FROM
				                        dbtest.order_info
				                    WHERE
				                        copy_from <> 0
				                    AND dbtest.order_info.is_split = 1
				                ) AS lin
				            WHERE
				                cf = dbtest.order_info.order_id
				        )
				        AND EXISTS (
				            SELECT
				                order_id
				            FROM
				                dbtest.order_action
				            WHERE
				                order_id = dbtest.order_info.order_id
				            AND action_name = 'O2OP3发货超时'
				        )
				        GROUP BY
				            order_info.order_sn
				    ) AS lin
				WHERE
				    dbtest.order_goods.order_sn = dbtest.order_info.order_sn
				AND dbtest.order_info.order_sn <> lin.os
				AND dbtest.order_info.deal_code = lin.od
				AND dbtest.order_info.sku_count = lin.osc
				AND dbtest.order_goods.sku = lin.osku
				AND dbtest.order_info.is_copy = 1
				AND dbtest.order_info.order_status = 3
				AND NOT EXISTS (
				    SELECT
				        1
				    FROM
				        (
				            SELECT
				                copy_from AS cf
				            FROM
				                dbtest.order_info
				            WHERE
				                copy_from <> 0
				        ) AS lin
				    WHERE
				        cf = dbtest.order_info.order_id
				)
				AND NOT EXISTS (
				    SELECT
				        1
				    FROM
				        etam_ims.di_order2sa_info
				    WHERE
				        order_sn = dbtest.order_info.order_sn
				);";

$types [] = '(5.6)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA后无效';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
					lin.os,dbtest.order_info.order_sn,
					1,
					'(5.6)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA后无效'
				FROM
					etam_ims.di_order2sa_info,
					dbtest.order_info,
					dbtest.order_goods,
					(
						SELECT
							dbtest.order_info.order_sn AS os,
							dbtest.order_info.deal_code AS od,
							dbtest.order_info.sku_count AS osc,
							dbtest.order_goods.sku AS osku,
							'(5.6)再分配订单 - 原单超过付款时间72小时，客服手动分配到SA后无效'
						FROM
							dbtest.order_info,
							dbtest.order_goods
						WHERE
							dbtest.order_info.order_sn = dbtest.order_goods.order_sn
						AND dbtest.order_info.status_yfp_qh != 2
						AND dbtest.order_info.is_split = 0
						AND dbtest.order_info.order_status = 3
						AND dbtest.order_info.is_copy = 0
						AND outer_goods_name NOT LIKE 'realloc%'
						AND dbtest.order_info.order_sn IN (
							SELECT
								order_sn
							FROM
								etam_ims.di_order2sa_info
							WHERE
								(
									FROM_UNIXTIME(pay_time) >= '".$start_time."'
									AND FROM_UNIXTIME(pay_time) < '".$end_time."'
								)
							AND order_status = 3
							AND is_realloc = 1
							AND is_realloc_new = 0
							AND IFNULL(realloc_from, '') = ''
						)
						AND NOT EXISTS (
							SELECT
								1
							FROM
								dbtest.order_info
							WHERE
								copy_from = dbtest.order_info.order_id
						)
						AND EXISTS (
							SELECT
								order_id
							FROM
								dbtest.order_action
							WHERE
								order_id = dbtest.order_info.order_id
							AND action_name = 'O2OP3发货超时'
						)
					) AS lin
				WHERE
					etam_ims.di_order2sa_info.order_sn = dbtest.order_info.order_sn
				AND dbtest.order_goods.order_sn = dbtest.order_info.order_sn
				AND dbtest.order_info.order_sn <> lin.os
				AND dbtest.order_info.deal_code = lin.od
				AND dbtest.order_info.sku_count = lin.osc
				AND dbtest.order_goods.sku = lin.osku
				AND dbtest.order_info.is_copy = 1
				AND dbtest.order_info.order_status = 3
				AND IFNULL(
					etam_ims.di_order2sa_info.realloc_from,
					''
				) = ''
				AND NOT EXISTS (
					SELECT
						1
					FROM
						(
							SELECT
								copy_from AS cf
							FROM
								dbtest.order_info
							WHERE
								copy_from <> 0
							AND dbtest.order_info.is_split = 1
						) AS lin
					WHERE
						cf = dbtest.order_info.order_id
				);";

	$types [] = '(5.7)再分配订单 - 原单超过付款时间72小时，源单直接无效';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
					dbtest.order_info.order_sn,dbtest.order_info.order_sn,
					1,
					'(5.7)再分配订单 - 原单超过付款时间72小时，源单直接无效'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND dbtest.order_info.status_yfp_qh != 2
				AND dbtest.order_info.is_split = 0
				AND dbtest.order_info.order_status = 3
				AND dbtest.order_info.is_copy = 0
				AND outer_goods_name NOT LIKE 'realloc%'
				AND dbtest.order_info.order_sn IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND NOT EXISTS (
					SELECT
						1
					FROM
						(
							SELECT
								copy_from AS cf
							FROM
								dbtest.order_info
							WHERE
								copy_from <> 0
						) AS lin
					WHERE
						cf = dbtest.order_info.order_id
				)
				AND EXISTS (
					SELECT
						order_id
					FROM
						dbtest.order_action
					WHERE
						order_id = dbtest.order_info.order_id
					AND action_name = 'O2OP3发货超时'
				);";
	$types [] = '(5.8)再分配订单 - 需从源订单删除';

	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
					dbtest.order_info.order_sn,dbtest.order_info.order_sn,
					1,
					'(5.8)再分配订单 - 需从源订单删除'
				FROM
					etam_ims.di_order2sa_info,
					dbtest.order_info,
					dbtest.order_goods,
					(
						SELECT
							dbtest.order_info.order_sn AS os,
							dbtest.order_info.deal_code AS od,
							dbtest.order_info.sku_count AS osc,
							dbtest.order_goods.sku AS osku,
							'(5.8)再分配订单 - 需从源订单删除'
						FROM
							dbtest.order_info,
							dbtest.order_goods
						WHERE
							dbtest.order_info.order_sn = dbtest.order_goods.order_sn
						AND dbtest.order_info.status_yfp_qh != 2
						AND dbtest.order_info.is_split = 0
						AND dbtest.order_info.order_status = 3
						AND dbtest.order_info.is_copy = 0
						AND outer_goods_name NOT LIKE 'realloc%'
						AND dbtest.order_info.order_sn IN (
							SELECT
								order_sn
							FROM
								etam_ims.di_order2sa_info
							WHERE
								(
									FROM_UNIXTIME(pay_time) >= '".$start_time."'
									AND FROM_UNIXTIME(pay_time) < '".$end_time."'
								)
							AND order_status = 3
							AND is_realloc = 1
							AND is_realloc_new = 0
							AND IFNULL(realloc_from, '') = ''
						)
						AND NOT EXISTS (
							SELECT
								1
							FROM
								dbtest.order_info
							WHERE
								copy_from = dbtest.order_info.order_id
						)
						AND EXISTS (
							SELECT
								order_id
							FROM
								dbtest.order_action
							WHERE
								order_id = dbtest.order_info.order_id
							AND action_name = 'O2OP3发货超时'
						)
					) AS lin
				WHERE
					etam_ims.di_order2sa_info.order_sn = dbtest.order_info.order_sn
				AND dbtest.order_goods.order_sn = dbtest.order_info.order_sn
				AND dbtest.order_info.order_sn <> lin.os
				AND dbtest.order_info.deal_code = lin.od
				AND dbtest.order_info.sku_count = lin.osc
				AND dbtest.order_info.order_status = 5
				AND dbtest.order_goods.sku = lin.osku
				AND IFNULL(
					etam_ims.di_order2sa_info.realloc_from,
					''
				) = '';";

	$types [] = '(5.9)再分配订单 - 需从源订单和再分配订单中删除';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
					dbtest.order_info.order_sn,dbtest.order_info.order_sn,
					1,
					'(5.9)再分配订单 - 需从源订单和再分配订单中删除'
				FROM
					etam_ims.di_order2sa_info,
					dbtest.order_info,
					dbtest.order_goods,
					(
						SELECT
							dbtest.order_info.order_sn AS os,
							dbtest.order_info.deal_code AS od,
							dbtest.order_info.sku_count AS osc,
							dbtest.order_goods.sku AS osku,
							'(5.9)再分配订单 - 需从源订单和再分配订单中删除'
						FROM
							dbtest.order_info,
							dbtest.order_goods
						WHERE
							dbtest.order_info.order_sn = dbtest.order_goods.order_sn
						AND dbtest.order_info.status_yfp_qh != 2
						AND dbtest.order_info.is_split = 0
						AND dbtest.order_info.order_status = 3
						AND dbtest.order_info.is_copy = 0
						AND outer_goods_name NOT LIKE 'realloc%'
						AND dbtest.order_info.order_sn IN (
							SELECT
								order_sn
							FROM
								etam_ims.di_order2sa_info
							WHERE
								(
									FROM_UNIXTIME(pay_time) >= '".$start_time."'
									AND FROM_UNIXTIME(pay_time) < '".$end_time."'
								)
							AND order_status = 3
							AND is_realloc = 1
							AND is_realloc_new = 0
							AND IFNULL(realloc_from, '') = ''
						)
						AND NOT EXISTS (
							SELECT
								1
							FROM
								dbtest.order_info
							WHERE
								copy_from = dbtest.order_info.order_id
						)
						AND EXISTS (
							SELECT
								order_id
							FROM
								dbtest.order_action
							WHERE
								order_id = dbtest.order_info.order_id
							AND action_name = 'O2OP3发货超时'
						)
					) AS lin
				WHERE
					etam_ims.di_order2sa_info.order_sn = dbtest.order_info.order_sn
				AND dbtest.order_goods.order_sn = dbtest.order_info.order_sn
				AND dbtest.order_info.order_sn <> lin.os
				AND dbtest.order_info.deal_code = lin.od
				AND dbtest.order_info.sku_count = lin.osc
				AND dbtest.order_goods.sku = lin.osku
				AND dbtest.order_info.is_copy = 1
				AND dbtest.order_info.order_status = 3
				AND IFNULL(
					etam_ims.di_order2sa_info.realloc_from,
					''
				) = ''
				AND NOT EXISTS (
					SELECT
						1
					FROM
						(
							SELECT
								copy_from AS cf
							FROM
								dbtest.order_info
							WHERE
								copy_from <> 0
							AND dbtest.order_info.is_split = 1
						) AS lin
					WHERE
						cf = dbtest.order_info.order_id
				);";

	$types [] = '(6.1)再分配订单 - 已上传SA，客服误操作取消订单,再分配订单误操作';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
					sum(
						dbtest.order_goods.goods_number / RIGHT (
							dbtest.order_goods.outer_goods_name,
							1
						)
					),
					'(6.1)再分配订单 - 已上传SA，客服误操作取消订单,再分配订单误操作'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND dbtest.order_info.status_yfp_qh != 2
				AND dbtest.order_info.is_split = 0
				AND dbtest.order_info.order_status = 3
				AND outer_goods_name LIKE 'realloc%'
				AND SUBSTR(outer_goods_name, 25, 13) IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND EXISTS (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						order_sn = dbtest.order_info.order_sn
					AND order_status <> 3
				)
				GROUP BY
					SUBSTR(outer_goods_name, 25, 13);";

	$types [] = '(6.2)源订单 - 客服误操作';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					order_sn,order_sn,
					1,
					'(6.2)源订单 - 客服误操作'
				FROM
					dbtest.order_info
				WHERE
					status_yfp_qh != 2
				AND is_split = 0
				AND order_status = 3
				AND order_sn IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status <> 3
					AND IFNULL(realloc_from, '') = ''
				);";

	$types [] = '(7.1)再分配订单 - 9100库位缺货 - EC自动停止再分配';
	$sql .=
	"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
			SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
			sum(
				dbtest.order_goods.goods_number / RIGHT (
					dbtest.order_goods.outer_goods_name,
					1
				)
			),
			'(7.1)再分配订单 - 9100库位缺货 - EC自动停止再分配'
		FROM
			dbtest.order_info,
			dbtest.order_goods
		WHERE
			dbtest.order_info.order_sn = dbtest.order_goods.order_sn
		AND dbtest.order_info.status_yfp_qh != 2
		AND dbtest.order_info.is_split = 0
		AND dbtest.order_info.order_status = 3
		AND outer_goods_name LIKE 'realloc%'
		AND SUBSTR(outer_goods_name, 25, 13) IN (
			SELECT
				order_sn
			FROM
				etam_ims.di_order2sa_info
			WHERE
				(
					FROM_UNIXTIME(pay_time) >= '".$start_time."'
					AND FROM_UNIXTIME(pay_time) < '".$end_time."'
				)
			AND order_status = 3
			AND is_realloc = 1
			AND is_realloc_new = 0
			AND IFNULL(realloc_from, '') = ''
		)
		AND EXISTS (
			SELECT
				order_id
			FROM
				dbtest.order_action
			WHERE
				order_id = dbtest.order_info.order_id
			AND action_name LIKE '9100库位缺货'
		)
		AND NOT EXISTS (
			SELECT
				order_sn
			FROM
				etam_ims.di_order2sa_info
			WHERE
				order_sn = dbtest.order_info.order_sn
		)
		GROUP BY
			SUBSTR(outer_goods_name, 25, 13);";

	$types [] = '(8.1)源订单 - 取消未再分配';
	$sql .=
	"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
			etam_ims.di_order2sa_info.order_sn,etam_ims.di_order2sa_info.order_sn,
			1,
			'(8.1)源订单 - 取消未再分配'
		FROM
			etam_ims.di_order2sa_info
		WHERE
			FROM_UNIXTIME(pay_time) >= '".$start_time."'
		AND FROM_UNIXTIME(pay_time) < '".$end_time."'
		AND order_status = 3
		AND is_realloc = 0
		AND IFNULL(original_order_sn, '') = '';";
	
		$types [] = '(9.1)源订单 买家退款，EC自动停止再分配';
		$sql .= 
				"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					dbtest.order_info.order_sn,dbtest.order_info.order_sn,
					1,
					'(9.1)源订单 买家退款，EC自动停止再分配'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND dbtest.order_info.status_yfp_qh != 2
				AND dbtest.order_info.is_split = 0
				AND dbtest.order_info.order_status = 3
				AND dbtest.order_info.order_sn IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND EXISTS (
					SELECT
						order_id
					FROM
						dbtest.order_action
					WHERE
						order_id = dbtest.order_info.order_id
					AND action_name LIKE '%关闭'
				);";

	$types [] = '(9.2)源订单 买家部分退款，EC自动停止再分配';
	

	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
					dbtest.order_info.order_sn,dbtest.order_info.order_sn,
					1,
					'(9.2)源订单 买家部分退款，EC自动停止再分配'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND dbtest.order_info.status_yfp_qh != 2
				AND dbtest.order_info.is_split = 0
				AND dbtest.order_info.order_status = 3
				AND dbtest.order_info.order_sn IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND EXISTS (
					SELECT
						order_id
					FROM
						dbtest.order_action
					WHERE
						order_id = dbtest.order_info.order_id
					AND action_name LIKE '%申请退款'
				);";

	$types [] = '(9.3)再分配订单 - 顾客退款 - EC自动停止再分配@@';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
					sum(
						dbtest.order_goods.goods_number / RIGHT (
							dbtest.order_goods.outer_goods_name,
							1
						)
					),
					'(9.3)再分配订单 - 顾客退款 - EC自动停止再分配'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND dbtest.order_info.status_yfp_qh != 2
				AND dbtest.order_info.is_split = 0
				AND dbtest.order_info.order_status = 3
				AND outer_goods_name LIKE 'realloc%'
				AND SUBSTR(outer_goods_name, 25, 13) IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND EXISTS (
					SELECT
						order_id
					FROM
						dbtest.order_action
					WHERE
						order_id = dbtest.order_info.order_id
					AND action_name LIKE '%关闭%'
				)
				GROUP BY
					SUBSTR(outer_goods_name, 25, 13);";
	$types [] = '(9.4)再分配订单 - 顾客部分退款退款 - EC自动停止再分配@@';
	$sql .= 
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
					sum(
						dbtest.order_goods.goods_number / RIGHT (
							dbtest.order_goods.outer_goods_name,
							1
						)
					),
					'(9.4)再分配订单 - 顾客部分退款退款 - EC自动停止再分配@@'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND dbtest.order_info.status_yfp_qh != 2
				AND dbtest.order_info.is_split = 0
				AND dbtest.order_info.order_status = 3
				AND outer_goods_name LIKE 'realloc%'
				AND SUBSTR(outer_goods_name, 25, 13) IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND EXISTS (
					SELECT
						order_id
					FROM
						dbtest.order_action
					WHERE
						order_id = dbtest.order_info.order_id
					AND action_name LIKE '%申请退款'
				)
				GROUP BY
					SUBSTR(outer_goods_name, 25, 13);";

	$types [] = '(10.1)再分配订单，在E3接口表中尚未转单';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					SUBSTR(taobao_items.title, 25, 13),'无',
					sum(
						taobao_items.num / RIGHT (taobao_items.title, 1)
					),
					'(10.1)再分配订单，在E3接口表中尚未转单'
				FROM
					dbtest.taobao_trade,
					dbtest.taobao_items
				WHERE
					taobao_items.ttid = taobao_trade.ttid
				AND taobao_trade.is_tran_success = 0
				AND taobao_trade.is_refund_trade = 0
				AND taobao_trade.order_id = 0
				AND taobao_trade. STATUS = 'WAIT_SELLER_SEND_GOODS'
				AND EXISTS (
					SELECT
						1
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND IFNULL(realloc_from, '') = ''
					AND SUBSTR(taobao_items.title, 25, 13) = etam_ims.di_order2sa_info.order_sn
				)
				GROUP BY
					SUBSTR(taobao_items.title, 25, 13);";

	$types [] = '(10.2)再分配订单，尚未发送至POS中间表';
	$sql .=
		"INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT
					SUBSTR(outer_goods_name, 25, 13),dbtest.order_info.order_sn,
					sum(
						dbtest.order_goods.goods_number / RIGHT (
							dbtest.order_goods.outer_goods_name,
							1
						)
					),
					'(10.2)再分配订单，尚未发送至POS中间表'
				FROM
					dbtest.order_info,
					dbtest.order_goods
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND fhck_id > 100
				AND dbtest.order_info.status_yfp_qh != 2 #AND dbtest.order_info.is_split = 0
				AND order_info.order_status = 1
				AND outer_goods_name LIKE 'realloc%'
				AND SUBSTR(outer_goods_name, 25, 13) IN (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				)
				AND NOT EXISTS (
					SELECT
						order_sn
					FROM
						etam_ims.di_order2sa_info
					WHERE
						order_sn = order_info.order_sn
				)
				GROUP BY
					SUBSTR(outer_goods_name, 25, 13);";
		
				$types [] = '(11.1)再分配订单 - 客服手动修改商品';
				$sql .="
				INSERT INTO etam_ecbi.temp_o2op3_stat_".$dbname." SELECT DISTINCT
					etam_ims.di_order2sa_info.order_sn,dbtest.order_info.order_sn,
					1,
					'(11.1)再分配订单 - 客服手动修改商品'
				FROM
					dbtest.order_info,
					dbtest.order_goods,
					etam_ims.di_order2sa_info
				WHERE
					dbtest.order_info.order_sn = dbtest.order_goods.order_sn
				AND dbtest.order_info.is_split_new = 0
				AND dbtest.order_info.order_status <> 3
				AND outer_goods_name NOT LIKE 'realloc%'
				AND SUBSTR(order_info.deal_code, 1, 16) = etam_ims.di_order2sa_info.deal_code
				AND FROM_UNIXTIME(etam_ims.di_order2sa_info.pay_time) >= '".$start_time."'
				AND FROM_UNIXTIME(etam_ims.di_order2sa_info.pay_time) < '".$end_time."'
				AND etam_ims.di_order2sa_info.order_status = 3
				AND etam_ims.di_order2sa_info.is_realloc = 1
				AND etam_ims.di_order2sa_info.is_realloc_new = 0
				AND IFNULL(
					etam_ims.di_order2sa_info.realloc_from,
					''
				) = ''
				AND dbtest.order_info.deal_code NOT IN (
					SELECT
						deal_code
					FROM
						etam_ims.di_order2sa_info
					WHERE
						(
							FROM_UNIXTIME(pay_time) >= '".$start_time."'
							AND FROM_UNIXTIME(pay_time) < '".$end_time."'
						)
					AND order_status = 3
					AND is_realloc = 1
					AND is_realloc_new = 0
					AND IFNULL(realloc_from, '') = ''
				);";	
		
		echo '<pre>';	
		echo $sql;die;
	$link_dbtest = new mysqli('localhost', 'root', 'root', 'dbtest');
	$link_etam_ecbi = new mysqli('localhost', 'root', 'root', 'etam_ecbi');
	$link_etam_ims = new mysqli('localhost', 'root', 'root', 'etam_ims');



	if (mysqli_connect_error()) {
	    die('Connect Error (' . mysqli_connect_errno() . ') '
	            . mysqli_connect_error());
	}

	if ($link_dbtest->multi_query($sql)) {
    do {
        /* store first result set */
        if ($result = $link_dbtest->store_result()) {
            while ($row = $result->fetch_row()) {
                printf("%s\n", $row[0]);
            }
            $result->free();
        }
        /* print divider */
        if ($link_dbtest->more_results()) {
            printf("-----------------\n");
        }
    } while ($link_dbtest->next_result());
}

	$link_dbtest->close();
	$link_etam_ecbi->close();
	$link_etam_ims->close();



