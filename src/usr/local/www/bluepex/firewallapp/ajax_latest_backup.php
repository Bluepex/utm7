<?php
	header('Access-Control-Allow-Origin', '*');
        header('Access-Control-Allow-Methods', 'GET, PUT, POST, DELETE, OPTIONS');
        header('Access-Control-Max-Age', 1000);
        header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
	
	$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImI2ZTM3MmY5ODUxZjU4NmRmM2YyMzU5NjM5YzgwMmQwNTQ4ODNmM2JhMDczZDRjN2JmY2UxM2RjZDAxNzc2ZWFkNzJlMjUxY2VlZWNmM2M3In0.eyJhdWQiOiIxIiwianRpIjoiYjZlMzcyZjk4NTFmNTg2ZGYzZjIzNTk2MzljODAyZDA1NDg4M2YzYmEwNzNkNGM3YmZjZTEzZGNkMDE3NzZlYWQ3MmUyNTFjZWVlY2YzYzciLCJpYXQiOjE1NDAyMjgxMjYsIm5iZiI6MTU0MDIyODEyNiwiZXhwIjoxNTcxNzY0MTI2LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.jlxC9KoFlk_vXut2zz3_YBxvbeN6hfmyOxUJagSGUstR5a61R80U84QN4We01rrKw7SxhYqyLtiHJEEq_hqKBsw6sstVal1X0k4NYNKmzb7izYcyI-1jlnGgaLnEstl-BaJKOdZhhVMm5spdjt0Q15SPbTX3NzGQ_tc5bAQkAIc8cnOvPVJkAzOXOf1ZTaRIYUggQhNqpxEK3fCaw3RZFWfHGG5BtXbXGvvLYWGLfKAeXAuj9jOmQkeksdWMZkYMZE94s2CrCI8U8DSSoXKh_O9r4Nd9gxgz2Vv80NdVnT7sst-9Vv2p3ebyXooikLYVHRSkosCfkv_z57ccheG7nFAHl1xV0HO_Gz3IDIbQi6tJdc9UAMLozZfolMCbF1XBVQ4yMHXhEuwJGFu5gHewonH6pfZctNPGdt8cN5AYR3q2moJbHdxk5wut3Wy7xp_pRzDUvfnTsmRPGm-b0a0cb3HMxxoGVo7t8rdsoqnR1IoYbuN11yPNriTnxlatKoCu5_FNxUgfSE3rj_z0oU1rF-ehQao36KSTrWPcbzW1pL0LYS3NTLBY1lhAAgRmQewPCnub304xZXC7Jhv6QS3ULqiTkZVGNu2FfKAQ_8X5x33l7pkSpUjCsjIPRjz9_lgijcS1lHTyY6qnW9E5Yl8c48neMsVlyAMfGahAn103iro";

	$ch = curl_init('https://APPURL.com/api/json.php'); // INITIALISE CURL

       	header('Content-Type: application/json');
       	
	$post = json_encode(array('serial' => '88888', 'deviceid' => 'undefined')); 
       	
	$authorization = "Authorization: Bearer ".$token; // **Prepare Autorisation Token**
       	
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
       	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
       	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       	curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
       	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
       	
	$result = curl_exec($ch);
       	
	curl_close($ch);
       	
	echo $result;

?>
