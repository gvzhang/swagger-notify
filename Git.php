<?php
require "Logger.php";

class Git{
	const ADD_METHOD_TIPS = "<span style='color:red;'>*****【新增接口】*****</span><br />";
	const DELETE_METHOD_TIPS = "<span style='color:878B95;'>*****【删除接口】*****</span><br />";

	private $_command;
	private $_status;
	private $_log;
	
	/**
	 * 获取修改的接口
	 */
	public function getLastDiffLine(){
		$diffLog = $this->_getGitDiff();
		$diffLog = implode(PHP_EOL, $diffLog);
		preg_match_all('/@{2}\s\-[0-9]+,[0-9]+\s\+([0-9]+),[0-9]+\s@{2}/', $diffLog, $matchesModify, PREG_PATTERN_ORDER);
		foreach($matchesModify[1] as &$val){
			$val++;
		}
		return $matchesModify[1];
	}

	/**
	* 获取新增的接口
	*/
	public function getAddMethodInfo(){
		$diffLog = $this->_getGitDiff();
		$matcheKey = NULL;
		foreach($diffLog as $key=>$log){
			if(preg_match('/@{2}\s\-[0-9]+,2\s\+([0-9]+),[1-9]([0-9]+)*\s@{2}/', $log)){
				$matcheKey = $key;
			}
		}
		if($matcheKey){
			$matcheArr = array_slice($diffLog, $matcheKey + 2, count($diffLog) - $matcheKey - 3);
			$matcheStr = implode(PHP_EOL, $matcheArr);
			$clearStr = rtrim(preg_replace('/\+\s/', '', $matcheStr), ",");
			$addTips = preg_replace('/"description":\s+"(.*?)"/', '"description":"'.self::ADD_METHOD_TIPS.'$1"', $clearStr, 1);
			return json_decode("{".$addTips."}");
		}else{
			return [];
		}
	}

	/**
	* 获取删除的接口
	*/
	public function getDeleteMethodInfo(){
		$diffLog = $this->_getGitDiff();
		$matcheKey = NULL;
		foreach($diffLog as $key=>$log){
			if(preg_match('/@{2}\s\-([0-9]+),[1-9]([0-9]+)*\s\+[0-9]+,2\s@{2}/', $log)){
				$matcheKey = $key;
			}
		}
		if($matcheKey){
			$matcheArr = array_slice($diffLog, $matcheKey + 2, count($diffLog) - $matcheKey - 3);
			$matcheStr = implode(PHP_EOL, $matcheArr);
			$clearStr = rtrim(preg_replace('/\-\s/', '', $matcheStr), ",");
			$addTips = preg_replace('/"description":\s+"(.*?)"/', '"description":"'.self::DELETE_METHOD_TIPS.'$1","deprecated":true', $clearStr, 1);
			return json_decode("{".$addTips."}");
		}else{
			return [];
		}
	}

	private function _getGitDiff(){
		if($this->_log){
			return $this->_log;
		}
		$cmd[] = sprintf('cd /home/vagrant/Code/sample');
		$cmd[] = sprintf('git diff HEAD^ HEAD --unified=1');
		$command = join(' && ', $cmd);
		$this->_runLocalCommand($command);
		return $this->_log;
	}
	
	private function _runLocalCommand($command)
    {
        $command = trim($command);
        Logger::write('---------------------------------');
        Logger::write('---- Executing: $ ' . $command);

        $status = 1;
        $log = '';

        exec($command . ' 2>&1', $log, $status);
        // 执行过的命令
        $this->_command = $command;
        // 执行的状态
        $this->_status = !$status;
        // 操作日志
        $this->_log = $log;

        Logger::write(implode(PHP_EOL, $log));
        Logger::write('---------------------------------');

        return $this->_status;
    }
	
	public function getCommand(){
		return $this->_command;
	}
	
	public function getStatus(){
		return $this->_status;
	}
	
	public function getLog(){
		return $this->_log;
	}
}