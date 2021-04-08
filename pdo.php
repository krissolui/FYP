<?php
// $pdo = new PDO('mysql:host=sql308.epizy.com;port=3306;dbname=epiz_27750907_tracker',
// 'epiz_27750907', 'tJi41geApa');
$pdo = new PDO('mysql:host=localhost;port=8889;dbname=tracker', 
   'kris', '106123');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);