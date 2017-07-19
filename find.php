<?php
	$dir=dirname(__FILE__);//查找当前脚本所在路径
	require $dir."/db2.php";//引入mysql操作类文件
	$db = new db2($phpexcel);
	$results = $db->show_Tables();	
	print_r($results);

?>
<!DOCTYPE html>
<html>
<head>
	<title>创建临时表</title>
</head>
<body>
	<h1>插入临时表数据</h1>
	<form action="demo4.php" method="post">

			选择数据表:<select name="dbname" id="">
				<?php 
					foreach ($results as $result) {
						echo '<option value="'.$result['Tables_in_shymt'].'">'.$result['Tables_in_shymt'].'</option>';
					}
				 ?>
				
			</select><br>

			起始日期:<input type="date" name="start_time"><br/>
    		截止日期:<input type="date" name="end_time"><br/>
    		<input type="submit" value="插入临时表数据">
		
	</form>
</body>
</html>