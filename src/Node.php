<?php

/**
 * Swagger的JSON文档处理
 */

namespace App;

class Node
{
    private $_data;
    private $_changeRow;
    private $_row = 1;
    private $_idx = 0;
    private $_parent = [];
    private $_diffParent = [];

    public function __construct($data, $changeRow = null)
    {
        $this->_data = $data;
        $this->_changeRow = $changeRow;
        if (empty($this->_data)) {
            throw new \Exception("data error");
        }
    }

    /**
     * 获取差异层级
     * 注意：差异在相同的方法中会分开返回
     */
    public function getParentList()
    {
        $this->_getParentList($this->_data);
        return $this->_diffParent;
    }

    private function _getParentList($data)
    {
        foreach ($data as $key => $obj) {
            $this->_row++;

            $this->_parent[$this->_idx] = $key;
            if ($this->_isChangeRow($this->_row)) {
                array_push($this->_diffParent, $this->_parent);
            }

            if (is_object($obj) || is_array($obj)) {
                $this->_idx++;
                $this->_getParentList($obj);
            }
        }
        $this->_row++;
        if ($this->_isChangeRow($this->_row)) {
            array_push($this->_diffParent, $this->_parent);
        }
        $this->_idx--;
    }

    /**
     * 获取差异API
     */
    public function getMethod()
    {
        $changeMethod = [];
        $parentList = $this->getParentList();
        foreach ($parentList as $parent) {
            $path = false;
            foreach ($parent as $key => $val) {
                if ($path) {
                    $changeMethod[$val] = $parent[$key + 1];
                    break;
                }
                if (strtolower($val) == "paths") {
                    $path = true;
                }
            }
        }
        return $changeMethod;
    }

    private function _isChangeRow($row)
    {
        return in_array($row, $this->_changeRow);
    }

    public function getParent()
    {
        return $this->_parent;
    }

    public function setChangeRow($changeRow)
    {
        $this->_changeRow = $changeRow;
    }

    public function getRow()
    {
        return $this->_row;
    }

    public function getData()
    {
        return $this->_data;
    }
}