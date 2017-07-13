<?php
require_once "Git.php";
require_once "Api.php";
require_once "SwaggerJson.php";

$repoPath = "/home/vagrant/Code/sample/hg_business_goods_api.json";

$git = new Git();
// 获取修改的接口
$diffLine = $git->getLastDiffLine();
// 获取新增的接口
$addMethod = $git->getAddMethodInfo();
// 获取删除的接口
$deleteMethod = $git->getDeleteMethodInfo();
if($diffLine || $addMethod || $deleteMethod){
	$diffInfoList = [];
	if($diffLine){
		$goodsArr = json_decode(file_get_contents($repoPath));
		$api = new Api($goodsArr, $diffLine);
		$diffInfoList = $api->getDiffMethodInfo();
	}

	if($addMethod){
		array_push($diffInfoList, $addMethod);
	}

	if($deleteMethod){
		array_push($diffInfoList, $deleteMethod);
	}

	if($diffInfoList){
		// 生成修改后的Swagger JSON数据
		$tplPath = "/home/vagrant/Code/API_HG_Business_Doc_Old/HG_Business/diff/diff.json.tpl";
		$target = "/home/vagrant/Code/API_HG_Business_Doc_Old/HG_Business/diff/diff.json";
		$replaceVar = "##PATHS##";
		$swaggerJson = new SwaggerJson($diffInfoList, $tplPath, $target, $replaceVar);
		echo "Generate ".($swaggerJson->generate()?"Success":"Failed");
	}else{
		echo "Get Diff List Failed";
	}
}else{
	echo "Get Diff Failed";
}
