<?php
	require_once("db.php");
	$db = new db($phpexcel);
	$results = $db->showTables();	
	// print_r($results);
?>
<!DOCTYPE html>
<html>
<head>
	<title>创建临时表</title>
</head>
<body>
	<h1>插入临时表数据</h1>
	<form action="mulit_query.php" method="post">

			选择数据表:<select name="dbname" id="">
				<?php 
					foreach ($results as $result) {
						echo '<option value="'.$result['Tables_in_etam_ecbi (temp_o2op3_stat_%)'].'">'.$result['Tables_in_etam_ecbi (temp_o2op3_stat_%)'].'</option>';
					}
				 ?>
				
			</select><br>

			起始日期:<input type="date" name="start_time"><br/>
    		截止日期:<input type="date" name="end_time"><br/>
    		<input type="submit" value="插入临时表数据">
		
	</form>
</body>
</html>