<?php
	require_once("db.php");
	$db = new db($phpexcel);
	$results = $db->showTables();	
	print_r($results);
?>
<!DOCTYPE html>
<html>
<head>
	<title>生成报表</title>
</head>
<body>
	<h1>生成报表</h1>
	<form action="export.php" method="post">

			选择数据表:<select name="dbname" id="">
				<?php 
					foreach ($results as $result) {
						echo '<option value="'.$result['Tables_in_shymt (temp_o2op3_stat_%)'].'">'.$result['Tables_in_shymt (temp_o2op3_stat_%)'].'</option>';
					}
				 ?>
				
			</select><br>
			选择查询方式:<select name="status">
					<option value="0">按batch_id</option>
					<option value="1">按pay_time</option>
			</select><br>

			起始日期:<input type="date" name="start_time"><br/>
    		截止日期:<input type="date" name="end_time"><br/>
    			<input type="submit" value="生成报表">
		
	</form>
</body>
</html>