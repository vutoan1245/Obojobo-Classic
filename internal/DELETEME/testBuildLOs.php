<pre>
<?php
require_once(dirname(__FILE__)."/../app.php");



$qm = nm_los_QuestionManager::getInstance();
if(!method_exists($qm, 'getQuestionNew'))
{
	exit("The php classes appear to be the old version still, update them to use the new data structures.");
}

$DBM = core_db_DBManager::getConnection(new core_db_dbConnectData(AppCfg::DB_HOST, AppCfg::DB_USER, AppCfg::DB_PASS, AppCfg::DB_NAME, AppCfg::DB_TYPE));
$DBM->startTransaction();

$API = nm_los_API::getInstance();


$q = $DBM->query("SELECT ".cfg_obo_LO::ID." FROM ".cfg_obo_LO::TABLE);
while($r = $DBM->fetch_obj($q))
{
	$lo = getNormalLO($r->{cfg_obo_LO::ID}, $DBM);
	echo $lo->loID . ' pages: ' . count($lo->pages) . ' practice: ' . count($lo->pGroup->kids) . ' assessment ' . count($lo->aGroup->kids) . "\n";
//	print out 10% of them
	if(rand(1,100) > 90)
	{
		echo strip_tags(serialize($lo));
		
	}
}



function getNormalLO($loID, $DBM)
{
	$lo = new nm_los_LO();
	$lo->dbGetFull($DBM, $loID);
	return $lo;
}

?>