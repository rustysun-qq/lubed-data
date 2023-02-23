<?php

namespace Lubed\Data;

/**
 * MySQL Data Source
 */
class MySQLDataSource implements DataSource
{

    /**
     * @var \Lubed\Utils\Config $config
     */
    private $config;

    /**
     * 绑定数据源
     *

     * @param \Lubed\Utils\Config $config *
     *
     * @return array
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 绑定数据源
     *
     * @return array
     */
    public function getTablesData() : array
    {
        $all_tables = $this->initSchemaTables();
        if (!$all_tables) {
            return [];
        }
        $result = [];
        foreach ($all_tables as $table => $tableInfo) {
            $columns = $this->getColumnsByTable($tableInfo);
            $result[$table] = [
                'comment' => $tableInfo->Comment,
                'primaryKey' => $columns['primaryKey'],
                'columns' => $columns['allFields'],
            ];
        }

        return $result;
    }

    /**
     * @param $tableInfo
     *
     * @return array
     */
    private function getColumnsByTable($tableInfo) : array
    {
        $result = [];
        if (!$tableInfo) {
            return $result;
        }
        $columns = $tableInfo->Columns ?? null;
        if (!$columns || !is_array($columns)) {
            return $result;
        }
        $primary_key = [];
        foreach ($columns as $column) {
            $is_primary = $column->Key == 'PRI';
            if ($is_primary) {
                $primary_key[$column->Field] = $column->Field;
            }
            $typeInfo = explode('(', $column->Type);
            $type = $typeInfo[0] ?? $column->Type;
            $result[strtolower($column->Field)] = [
                'name' => $column->Field,
                'type' => $type,
                'propertyType' => $this->getPropertyType($type),
                'comment' => $column->Comment,
            ];
        }

        return ['primaryKey' => $primary_key, 'allFields' => $result];
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function getPropertyType(string $type) : string
    {
        $typeMap = [
            'tinyint' => 'int',
            'smallint' => 'int',
            'mediumint' => 'int',
            'int' => 'int',
            'integer' => 'int',
            'bigint' => 'int',
            'float' => 'float',
            'double' => 'double',
            'real' => 'double',
            'decimal' => 'double',
            'numeric' => 'double',
        ];
        $type = $typeMap[$type] ?? 'string';

        return $type;
    }

    /**
     * get all tables and files by connection name
     *
     * @return array
     */
    private function initSchemaTables() : array
    {
        $db = DB::getInstance($this->config);
        $st = $db->execute('SHOW TABLE STATUS');
        $result = [];
        while ($row = $st->fetchObject()) {
            $sql = sprintf('SHOW FULL COLUMNS FROM `%s`', $row->Name);
            $st_columns = $db->execute($sql);
            $columns = [];
            if ($st_columns) {
                while ($column = $st_columns->fetchObject()) {
                    $columns[$column->Field] = $column;
                }
            }
            $row->Columns = $columns;
            $result[strtolower($row->Name)] = $row;
        }

        return $result;
    }
}