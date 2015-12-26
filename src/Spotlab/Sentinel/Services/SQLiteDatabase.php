<?php

namespace Spotlab\Sentinel\Services;

/**
 * SQLiteDatabase
 */
class SQLiteDatabase extends \SQLite3
{
    private $table;

    function __construct()
    {
        // Database File
        $database = __DIR__ . '/../../../../database/SQLiteSentinel.db';
        $this->open($database);

        // Table structure
        $table = array();
        $table['id'] = 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $table['project'] = 'TEXT NOT NULL';
        $table['serie'] = 'TEXT NOT NULL';
        $table['ping_date'] = 'DATETIME NOT NULL';
        $table['ping_time'] = 'INTEGER';
        $table['http_status'] = 'INTEGER';
        $table['error'] = 'BOOLEAN';
        $table['error_log'] = 'TEXT';

        $this->table = $table;

        // Create Database if not exist
        $this->create();
    }

    /**
     * [init description]
     * @return [type] [description]
     */
    private function create()
    {
        if(!$this){
            throw new \Exception($db->lastErrorMsg(), 0);
        } else {
            $fields = array();
            foreach ($this->table as $name => $type) {
                $fields[] = $name . ' ' . $type;
            }

            $query = 'CREATE TABLE IF NOT EXISTS sentinel (' . implode(',', $fields) . ')';
            $this->exec($query);
        }
    }

    /**
     * [savePing description]
     * @param  [type] $ping [description]
     * @return [type]       [description]
     */
    public function insert($ping)
    {
        $fields = $values = array();
        foreach ($this->table as $name => $type) {
            if($name == 'id') continue;

            // Set VALUES
            if(strpos($type, 'NOT NULL') !== false && empty($ping[$name])) {
                throw new \Exception('Database Field ' . $name . 'required', 0);
            } elseif (empty($ping[$name])) {
                $values[] = 'NULL';
            } else {
                $values[] = '"' . $ping[$name] . '"';
            }

            // Set INSERT
            $fields[] = $name;
        }

        $query = 'INSERT INTO sentinel (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';
        $this->exec($query);
    }
}
