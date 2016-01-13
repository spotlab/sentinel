<?php

namespace Spotlab\Sentinel\Services;

/**
 * MongoDatabase
 */
class MongoDatabase
{
    public $mongo;
    public $mongo_db;
    public $mongo_collection;
    public $timeout;

    public function __construct($uri)
    {
        $this->mongo = new \MongoClient($uri);
        $this->mongo_db = $this->mongo->selectDB('sentinel');
        $this->mongo_collection = new \MongoCollection($this->mongo_db, 'ping');
        //$this->mongo_collection->createIndex(array('key' => 1), array('unique' => 1));
    }

    public function insert($value)
    {
        return $this->mongo_collection->save($value);
    }

    /**
     * [init description]
     * @return [type] [description]
     */
    public function getStatus($projects)
    {
        // Default
        $return = array(
            'status' => array(
                'average' => true,
                'quality_of_service' => true,
            )
        );

        foreach (array_keys($projects) as $project) {
            $data = $this->getProjectAverage($project);

            // Average TEST
            if(!empty($data['average']['two_minutes']) && $data['average']['two_minutes']['raw'] < 2) {
                $return['projects'][$project]['average'] = true;
            } else {
                $return['projects'][$project]['average'] = false;
                $return['status']['average'] = false;
            }

            // Quality of Service TEST
            if(isset($data['quality_of_service']['two_minutes']['failed']) && $data['quality_of_service']['two_minutes']['failed'] == 0) {
                $return['projects'][$project]['quality_of_service'] = true;
            } else {
                $return['projects'][$project]['quality_of_service'] = false;
                $return['status']['quality_of_service'] = false;
            }
        }

        return $return;
    }

    /**
     * [init description]
     * @return [type] [description]
     */
    public function getSerie($project, $serie)
    {
        $return = array();

        // Set max date (1 week max)
        $max_date = time() - 604800;

        // Send query
        $cursor = $this->mongo_collection
                            ->find(array(
                                'project' => $project,
                                'serie' => $serie,
                                'ping_date' => array('$gt' => $max_date)
                            ))
                            ->sort(array('ping_date' => 1));

        foreach ($cursor as $doc) {
            $return[] = array(
                'ping_date' => $doc['ping_date'],
                //'ping_human_date' => date('r', $doc['ping_date']),
                'ping_time' => $doc['ping_time'],
                'http_status' => $doc['http_status'],
                'error' => $doc['error'],
            );
        }

        return $return;
    }

    /**
     * [init description]
     * @return [type] [description]
     */
    public function getProjectSeries($project, $group = true)
    {
        $return = array();

        // Set max date (1 week max)
        $max_date = time() - 604800;

        // Send query
        $cursor = $this->mongo_collection
                            ->find(array(
                                'project' => $project,
                                'ping_date' => array('$gt' => $max_date)
                            ))
                            ->sort(array('ping_date' => 1, 'serie' => 1));

        foreach ($cursor as $doc) {
            $row = array(
                'ping_date' => $doc['ping_date'],
                //'ping_human_date' => date('r', $doc['ping_date']),
                'ping_time' => $doc['ping_time'],
                'http_status' => $doc['http_status'],
                'error' => $doc['error'],
            );

            if($group) {
                $return[$doc['serie']][] = $row;
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
    public function getProjectAverage($project)
    {
        $return = array();

        // Get Data
        $data = $this->getProjectSeries($project, $group = false);

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
                    $calc[$key]['toolong_count'] = 0;
                }

                if($val['ping_date'] >= $time) {
                    $calc[$key]['average'] += $val['ping_time'];
                    if($val['error']) {
                        $calc[$key]['failed_count']++;
                    } else if($val['ping_time'] >= 2) {
                        $calc[$key]['toolong_count']++;
                    } else {
                        $calc[$key]['success_count']++;
                    }
                }
            }
        }

        // Division to get average
        foreach ($calc as $key => $val) {
            if($val['average'] != 0 && $val['success_count'] != 0) {
                $average = $val['average'] / $val['success_count'];
                $return['average'][$key]['raw'] = $average;

                // Human Readable
                if($average < 1) {
                    $average = (round($average * 100) * 10) . 'ms';
                } else {
                    $average = (round($average * 10) / 10) . 's';
                }
                $return['average'][$key]['human'] = $average;
            }

            $total_count = $val['success_count'] + $val['failed_count'] + $val['toolong_count'];
            if($total_count != 0) {
                $return['quality_of_service'][$key] = array(
                    'success' => round(($val['success_count'] / $total_count) * 100),
                    'failed' => round(($val['failed_count'] / $total_count) * 100),
                    'toolong' => round(($val['toolong_count'] / $total_count) * 100),
                );
            }
        }

        return $return;
    }
}
