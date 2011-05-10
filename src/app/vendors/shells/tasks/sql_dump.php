<?php

class SqlDumpTask extends Shell {
	function execute() {
		$sources = ConnectionManager::sourceList();
		$logs = array();
		foreach ($sources as $source) {
			$db =& ConnectionManager::getDataSource($source);
			if (!$db->isInterfaceSupported('getLog')) {
				continue;
			}
			$logs[$source] = $db->getLog();
		}
		foreach ($logs as $source => $log) {
			echo "$source:\n";
			foreach ($log['log'] as $row) {
				echo $row['query']." took ".$row['took']."\n";
			}
		}
	}
}