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
        return $this->exec($query);
    }

    /**
     * [init description]
     * @return [type] [description]
     */
    public function findProjectSeries($project, $group = true)
    {
        $return = array();

        // Set max date (1 week max)
        $max_date = time() - 604800;

        // Send query
        $statement = $this->prepare('
            SELECT * FROM sentinel
            WHERE project = :project AND ping_date >= :max_date
            ORDER BY ping_date
        ');
        $statement->bindValue(':project', $project);
        $statement->bindValue(':max_date', $max_date);
        $results = $statement->execute();

        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            if($group) {
                $return[$row['serie']][] = $row;
            } else {
                $return[] = $row;
            }
        }

        return $return;
    }

    /**
     * [init description]
     * @return [type] [description]
     */
    public function findSerie($project, $serie)
    {
        $return = array();

        // Send query
        $statement = $this->prepare('
            SELECT * FROM sentinel
            WHERE project = :project AND serie = :serie
            ORDER BY ping_date
        ');
        $statement->bindValue(':project', $project);
        $statement->bindValue(':serie', $serie);
        $results = $statement->execute();

        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $return[] = $row;
        }

        return $return;
    }

    /**
     * [init description]
     * @return [type] [description]
     */
    public function findProjectAverage($project)
    {
        $return = array();

        // Get Data
        $data = $this->findProjectSeries($project, $group = false);

        // Set duration
        $durations = array();
        $durations['two_minutes'] = time() - (2 * 60);
        $durations['half_hour'] = time() - (30 * 60);
        $durations['one_hour'] = time() - 3600;
        $durations['half_day'] = time() - (12 * 3600);
        $durations['one_day'] = time() - (24 * 3600);
        $durations['half_week'] = time() - (3 * 24 * 3600);
        $durations['one_week'] = time() - (7 * 24 * 3600);

        $calc = array();
        foreach ($data as $val) {
            foreach ($durations as $key => $time) {
                if(!isset($calc[$key])) {
                    $calc[$key]['average'] = 0;
                    $calc[$key]['success_count'] = 0;
                    $calc[$key]['failed_count'] = 0;
                }

                if($val['ping_date'] >= $time) {
                    $calc[$key]['average'] += $val['ping_time'];
                    if($val['error']) {
                        $calc[$key]['failed_count']++;
                    } else {
                        $calc[$key]['success_count']++;
                    }
                }
            }
        }

        // Division to get average
        foreach ($calc as $key => $val) {
            if($val['average'] != 0 && $val['success_count'] != 0) {
                $return['average'][$key] = $val['average'] / $val['success_count'];
            }

            $total_count = $val['success_count'] + $val['failed_count'];
            if($total_count != 0) {
                $return['quality_of_service'][$key] = round(($val['success_count'] / $total_count) * 100);
            }
        }

        return $return;
    }
}
