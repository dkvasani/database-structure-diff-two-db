include("mysql_compare.php");

$comp = new DatabaseCompare();
$comp->SelectDatabaseOne("database_name_1");
$comp->SelectDatabaseTwo("database_name_2");
$comp->ConnectDatabaseOne("localhost","root","", 3306);
$comp->ConnectDatabaseTwo("localhost","root","password", 3306);
$comp->displayMatches = false; 
$comp->DoComparison();
