<?php

/**
 * 通知操作
 */

namespace App;

class Notification
{
    private $_repoPath;
    private $_target;

    private $_emailSrv;
    private $_emailTpl;
    const EMAIL_SUBJECT = "API接口更新通知";
    const NOTIFY_API_URL = "http://192.168.2.23:8010";

    private $_repositorySrv;

    public function __construct($repoPath, $target, \PHPMailer $emailSrv, Repository $repository)
    {
        $this->_repoPath = $repoPath;
        $this->_target = $target;
        if (empty($this->_repoPath) || !is_dir($this->_repoPath)) {
            throw new \InvalidArgumentException("repoPath Error");
        }
        if (empty($this->_target) || !is_dir($this->_target)) {
            throw new \InvalidArgumentException("target Error");
        }
        $this->_emailSrv = $emailSrv;
        $this->_emailTpl = rootPath() . "/template/email.notify.tpl";

        $this->_repositorySrv = $repository;
    }

    /**
     * 获取修改的API信息
     * @return array
     */
    protected function getChangeApiInfo()
    {
        $textProcess = new TextProcess($this->_repoPath);
        $changeFileList = $textProcess->getChangFiles();
        if ($changeFileList) {
            $diffInfoList = [];
            foreach ($changeFileList as $file => $logs) {
                $diffInfoList[$file] = [];
                // 获取修改的接口
                $diffLine = $textProcess->getLastDiffLine($logs);
                // 获取新增的接口
                $addMethod = $textProcess->getAddMethodInfo($logs);
                // 获取删除的接口
                $deleteMethod = $textProcess->getDeleteMethodInfo($logs);
                if ($diffLine || $addMethod || $deleteMethod) {
                    // 修改的接口信息
                    if ($diffLine) {
                        $goodsArr = json_decode(file_get_contents($this->_repoPath . "/api-json/" . $file));
                        $node = new Node($goodsArr, $diffLine);
                        foreach ($textProcess->getDiffMethodInfo($node) as $methodInfo) {
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
            return [];
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
            $htmlFileList = $swaggerJson->generate();
            if ($htmlFileList) {
                foreach ($htmlFileList as $file) {
                    $fileInfo = pathinfo($file);
                    $module = explode("-", $fileInfo["filename"]);
                    $managerList = $this->_repositorySrv->getManagerByModule($module[0]);
                    $emailList = $this->_repositorySrv->getEmailByManager($managerList);
                    $sendResult = $this->_sendMail($emailList, $file);
                    if ($sendResult !== true) {
                        Logger::write(json_encode($sendResult, JSON_UNESCAPED_UNICODE));
                    }
                }
                Logger::write("generate success");
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * 发送邮件通知
     * @param $sendList
     * @param $file
     * @return bool|string
     */
    private function _sendMail($sendList, $file)
    {
        foreach ($sendList as $address) {
            $this->_emailSrv->addAddress($address);
        }
        $this->_emailSrv->isHTML(true);
        $this->_emailSrv->Subject = self::EMAIL_SUBJECT;
        $this->_emailSrv->Body = preg_replace('/##URL##/', self::NOTIFY_API_URL . "/" . $file, $this->_getEmailTpl());
        if (!$this->_emailSrv->send()) {
            return $this->_emailSrv->ErrorInfo;
        } else {
            return true;
        }
    }

    private function _getEmailTpl()
    {
        return file_get_contents($this->_emailTpl);
    }
}