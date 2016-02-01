<?php

namespace Spotlab\Sentinel\Services;

/**
 * MongoDatabase
 */
class MongoDatabase
{
    public $mongo;
    public $db;
    public $collections;
    public $timeout;

    /**
     * [__construct description]
     * @param [type] $uri [description]
     */
    public function __construct($uri)
    {
        $this->mongo = new \MongoClient($uri);
        $this->db = $this->mongo->selectDB('sentinel');

        $this->collections['ping'] = new \MongoCollection($this->db, 'dev_ping');
        $this->collections['ping']->createIndex(array('project' => 1), array('background' => 1));
        $this->collections['ping']->createIndex(array('ping_date' => 1), array('expireAfterSeconds' => 60*60*24*3)); // Expire after 3 days

        $this->collections['average'] = new \MongoCollection($this->db, 'dev_average');
        $this->collections['average']->createIndex(array('project' => 1), array('background' => 1));
    }

    /**
     * [insert description]
     * @param  [type] $collection [description]
     * @param  [type] $value      [description]
     * @return [type]             [description]
     */
    public function insert($collection, $value)
    {
        return $this->collections[$collection]->save($value);
    }

    /**
     * [ping description]
     * @param  [type] $ping [description]
     * @return [type]       [description]
     */
    public function getProjectAverage($project)
    {
        // // Set default average
        // $average = array(
        //     'project' => $project,
        //     'time' => $ping['ping_time'],
        //     'count' => 1,
        //     'last_date' => $ping['ping_date'],
        //     'since_date' => new \MongoDate()
        // );

        // Save average
        $average = $this->collections['average']
                        ->findOne(array('project' => $project));

        return $average;

        // foreach ($cursor as $doc) {
        //     $count = $doc['count'] + 1;
        //     $time = (($doc['time']*$doc['count']) + $ping['ping_time']) / $count;
        //
        //     $average = array(
        //         'project' => $project,
        //         'time' => $time,
        //         'count' => $count,
        //         'last_date' => $ping['ping_date'],
        //         'since_date' => $doc['since_date']
        //     );
        // }
        //
        // return $this->insert('average', $average);
    }

    /**
     * [getStatus description]
     * @param  [type] $projects [description]
     * @return [type]           [description]
     */
    public function getStatus($projects)
    {
        // Default
        $return = array(
            'status' => array(
                'quality_of_service' => true,
            )
        );

        foreach (array_keys($projects) as $project) {

            // Send query
            $cursor = $this->collections['ping']
                                ->find(array(
                                    'project' => $project
                                ))
                                ->sort(array('ping_date' => 1, 'serie' => 1))
                                ->limit(1);

            foreach ($cursor as $doc) {
                if(!empty($doc['error'])) {
                    $return['status']['average'] = false;
                    $return['projects'][$project]['quality_of_service'] = false;
                } else {
                    $return['projects'][$project]['quality_of_service'] = true;
                }
            }
        }

        return $return;
    }

    /**
     * [getSerie description]
     * @param  [type] $project [description]
     * @param  [type] $serie   [description]
     * @return [type]          [description]
     */
    public function getSerie($project, $serie)
    {
        $return = array();

        // Set max date (1 week max)
        $max_date = time() - 604800;

        // Send query
        $cursor = $this->collections['ping']
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
     * [getProjectSeries description]
     * @param  [type] $project [description]
     * @param  [type] $group   [description]
     * @return [type]          [description]
     */
    public function getProjectSeries($project, $group = true)
    {
        $return = array();

        // Send query
        $cursor = $this->collections['ping']
                            ->find(array(
                                'project' => $project,
                                //'ping_date' => array('$gt' => $max_date)
                            ))
                            ->sort(array('ping_date' => 1, 'serie' => 1));

        foreach ($cursor as $doc) {
            $row = array(
                'ping_date' => $doc['ping_date'],
                //'ping_human_date' => date('r', $doc['ping_date']),
                'http_status' => $doc['http_status'],
            );

            // Optionnal fields
            if(!empty($doc['ping_time'])) {
                $row['ping_time'] =  $doc['ping_time'];
            }

            if(!empty($doc['error'])) {
                $row['error'] =  $doc['error'];
            }

            // Return group or not
            if($group) {
                $return[$doc['serie']][] = $row;
            } else {
                $return[] = $row;
            }
        }

        return $return;
    }

    // /**
    //  * [getProjectAverage description]
    //  * @param  [type] $project [description]
    //  * @return [type]          [description]
    //  */
    // public function getProjectAverage($project)
    // {
    //     $return = array();
    //
    //     // Get Data
    //     $data = $this->getProjectSeries($project, $group = false);
    //
    //     // Set duration
    //     $durations = array();
    //     $durations['two_minutes'] = time() - (2 * 60);
    //     $durations['half_hour'] = time() - (30 * 60);
    //     $durations['one_hour'] = time() - 3600;
    //     $durations['half_day'] = time() - (12 * 3600);
    //     $durations['one_day'] = time() - (24 * 3600);
    //     $durations['half_week'] = time() - (3 * 24 * 3600);
    //     $durations['one_week'] = time() - (7 * 24 * 3600);
    //
    //     $calc = array();
    //     foreach ($data as $val) {
    //         foreach ($durations as $key => $time) {
    //             if(!isset($calc[$key])) {
    //                 $calc[$key]['average'] = 0;
    //                 $calc[$key]['success_count'] = 0;
    //                 $calc[$key]['failed_count'] = 0;
    //                 $calc[$key]['toolong_count'] = 0;
    //             }
    //
    //             if($val['ping_date'] >= $time) {
    //                 $calc[$key]['average'] += $val['ping_time'];
    //                 if($val['error']) {
    //                     $calc[$key]['failed_count']++;
    //                 } else if($val['ping_time'] >= 2) {
    //                     $calc[$key]['toolong_count']++;
    //                 } else {
    //                     $calc[$key]['success_count']++;
    //                 }
    //             }
    //         }
    //     }
    //
    //     // Division to get average
    //     foreach ($calc as $key => $val) {
    //         if($val['average'] != 0 && $val['success_count'] != 0) {
    //             $average = $val['average'] / $val['success_count'];
    //             $return['average'][$key]['raw'] = $average;
    //
    //             // Human Readable
    //             if($average < 1) {
    //                 $average = (round($average * 100) * 10) . 'ms';
    //             } else {
    //                 $average = (round($average * 10) / 10) . 's';
    //             }
    //             $return['average'][$key]['human'] = $average;
    //         }
    //
    //         $total_count = $val['success_count'] + $val['failed_count'] + $val['toolong_count'];
    //         if($total_count != 0) {
    //             $return['quality_of_service'][$key] = array(
    //                 'success' => round(($val['success_count'] / $total_count) * 100),
    //                 'failed' => round(($val['failed_count'] / $total_count) * 100),
    //                 'toolong' => round(($val['toolong_count'] / $total_count) * 100),
    //             );
    //         }
    //     }
    //
    //     return $return;
    // }
}
