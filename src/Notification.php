<?php

/**
 * 通知操作
 */

namespace App;

class Notification
{
    private $_repoPath;
    private $_target;

    public function __construct($repoPath, $target)
    {
        $this->_repoPath = $repoPath;
        $this->_target = $target;
        if (empty($this->_repoPath) || !is_dir($this->_repoPath)) {
            throw new \InvalidArgumentException("repoPath Error");
        }
        if (empty($this->_target) || !is_dir($this->_target)) {
            throw new \InvalidArgumentException("target Error");
        }
    }

    /**
     * 获取修改的API信息
     * @return array
     */
    protected function getChangeApiInfo()
    {
        $git = new Git($this->_repoPath);
        $changeFileList = $git->getChangFiles();
        if ($changeFileList) {
            $diffInfoList = [];
            foreach ($changeFileList as $file => $logs) {
                $diffInfoList[$file] = [];
                // 获取修改的接口
                $diffLine = $git->getLastDiffLine($logs);
                // 获取新增的接口
                $addMethod = $git->getAddMethodInfo($logs);
                // 获取删除的接口
                $deleteMethod = $git->getDeleteMethodInfo($logs);
                if ($diffLine || $addMethod || $deleteMethod) {
                    // 修改的接口信息
                    if ($diffLine) {
                        $goodsArr = json_decode(file_get_contents($this->_repoPath . "/api-json/" . $file));
                        $api = new Node($goodsArr, $diffLine);
                        foreach ($api->getDiffMethodInfo() as $methodInfo) {
                            array_push($diffInfoList[$file], $methodInfo);
                        }
                    }

                    // 新增的接口信息
                    foreach ($addMethod as $method) {
                        array_push($diffInfoList[$file], $method);
                    }

                    // 删除的接口信息
                    foreach ($deleteMethod as $method) {
                        array_push($diffInfoList[$file], $method);
                    }
                } else {
                    throw new \LogicException("Get Diff Failed");
                }
            }
            return $diffInfoList;
        } else {
            throw new \LogicException("Get change file Failed");
        }
    }

    /**
     * 通知通知
     * @return bool|int
     */
    public function execute()
    {
        $diffInfoList = $this->getChangeApiInfo();
        if ($diffInfoList) {
            // 生成修改后的Swagger JSON数据
            $swaggerJson = new SwaggerJson($diffInfoList, $this->_target);
            $swaggerJson->generate();
            Logger::write("generate success");
            return true;
        } else {
            throw new \LogicException("Get Diff List Failed");
        }
    }
}