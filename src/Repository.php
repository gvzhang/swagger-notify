<?php

/**
 * 数据仓储操作
 */

namespace App;

class Repository
{
    private $_managerModuleData;
    private $_managerData;
    private $_moduleData;

    public function __construct()
    {
        $this->_managerModuleData = json_decode(file_get_contents(rootPath() . "/database/manager_module.json"));
        $this->_managerData = json_decode(file_get_contents(rootPath() . "/database/manager.json"));
        $this->_moduleData = json_decode(file_get_contents(rootPath() . "/database/module.json"));
        if (empty($this->_managerModuleData)) {
            throw new \InvalidArgumentException("managerModuleData Error");
        }
        if (empty($this->_managerData)) {
            throw new \InvalidArgumentException("managerData Error");
        }
        if (empty($this->_moduleData)) {
            throw new \InvalidArgumentException("moduleData Error");
        }
    }

    /**
     * 根据模块获取管理员
     * @param $module
     * @return array
     */
    public function getManagerByModule($module)
    {
        $module = explode("_", $module);
        if (count($module) != 2) {
            throw new \InvalidArgumentException("module Error");
        }
        foreach ($this->_managerModuleData as $key => $row) {
            if ($key == $module[0]) {
                foreach ($row as $key2 => $row2) {
                    if ($key2 == $module[1]) {
                        return $row2;
                    }
                }
            }
        }
        return [];
    }

    /**
     * 获取管理员邮箱
     * @param $managerList
     * @return array
     */
    public function getEmailByManager($managerList)
    {
        $emailList = [];
        foreach ($managerList as $manager) {
            foreach ($this->_managerData as $key => $val) {
                if ($key == $manager) {
                    array_push($emailList, $val);
                }
            }
        }
        return $emailList;
    }
}