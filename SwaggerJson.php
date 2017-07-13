<?php
class SwaggerJson{
	private $_tplPath;
	private $_target;
	private $_diffData;
	private $_var;
	
	public function __construct($data, $tplPath, $target, $var){
		$this->_tplPath = $tplPath;
		$this->_target = $target;
		$this->_diffData = $data;
		$this->_var = $var;
		if(!file_exists($this->_tplPath)){
			throw new \Exception("tplPath Error");
		}
		if(!file_exists($this->_target)){
			throw new \Exception("target Error");
		}
		if(empty($this->_diffData)){
			throw new \Exception("diffData Error");
		}
		if(empty($this->_var)){
			throw new \Exception("var Error");
		}
	}
	
	/**
	 * 生成Swagger JSON模板
	 */
	public function generate(){
		$paths = $this->_getPaths();
		$tpl = $this->_getTpl();
		return $this->_putJson($paths, $tpl);
	}
	
	/**
	 * 获取替换PATHS数据
	 */
	private function _getPaths(){
		$diffPaths = "";
		foreach($this->_diffData as $info){
			$jsonData = json_encode($info, JSON_UNESCAPED_UNICODE); 
			$diffPaths .= substr($jsonData, 1, strlen($jsonData)-2).",";
		}
		return rtrim($diffPaths, ",");
	}
	
	/**
	 * 获取模板信息
	 */
	private function _getTpl(){
		return file_get_contents($this->_tplPath);
	}
	
	/**
	 * 保存JSON数据
	 */
	private function _putJson($paths, $tpl){
		$putContent = json_decode(str_replace($this->_var, $paths, $tpl));
		return file_put_contents($this->_target, json_encode($putContent, JSON_UNESCAPED_UNICODE));
	}
}