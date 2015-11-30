<?php

namespace Spotlab\Sentinel;

use Symfony\Component\Yaml\Yaml;

class Guardian
{
    protected $config;
    protected $data_file;

    public function __construct($config_path)
    {
        if (file_exists($config_path)) {
            $this->config = Yaml::parse($config_path);
        } else {
            throw new \Exception('Config file does not exists', 0);
        }

        $this->data_file = __DIR__ . '/../../../web/data/data.json';
        if (!file_exists($this->data_file)) {
            file_put_contents($this->data_file, json_encode(array()));
        }
    }

    /**
     * @return array $return
     */
    public function getWebsites()
    {
        // Return array
        $return = $this->config['websites'];

        // Exception if not defined
        if (empty($return)) {
            throw new \Exception('No "project" to backup find on config file', 0);
        }

        return $return;
    }

    /**
     * @return array $return
     */
    public function getData()
    {
        // Return array
        $return = array();

        if (file_exists($this->data_file)) {
            $return = json_decode(file_get_contents($this->data_file), true);
        }

        return $return['config'];
    }

    private function getDataFromDuration($data, $hours = 1)
    {
        $pingByHour = 60;
        return array_slice($data, -$pingByHour * $hours, $pingByHour * $hours, true);
    }

    /**
     * @return array $return
     */
    public function setData($data = array())
    {
        $clean_data = $alarm_data = $duration = array();
        foreach ($data as $website => $val) {
            $duration[1][$website]  = $this->getDataFromDuration($val);
            $duration[3][$website]  = $this->getDataFromDuration($val, 3);
            $duration[24][$website] = $this->getDataFromDuration($val, 24);
            $clean_data[$website]   = $this->getDataFromDuration($val, 24 * 7); // 1 week
            $alarm_data[$website]   = array_slice($val, -3, 3);
        }
        $duration[0] = $clean_data; // Unlimited duration

        // General average calcul
        $generalAverage = array();
        foreach ($duration as $period => $websites) {
            $count = $generalAverage[$period] = 0;
            foreach ($websites as $website => $entry) {
                foreach ($entry as $val) {
                    $count++;
                    $generalAverage[$period] += $val['total_time'];
                }
            }
            $count = ($count!==0) ? $count : 1;
            $generalAverage[$period] = round($generalAverage[$period] / $count * 100) / 100;
        }

        // Last average calcul
        $calc = $count = 0;
        $status = true;
        foreach ($alarm_data as $website => $entry) {
            foreach ($entry as $key => $val) {
                $count++;
                $calc  += $val['total_time'];
                $status = ($val['http_code'] < 400 && $status);
            }
        }
        $count   = ($count!==0) ? $count : 1;
        $average = round($calc / $count * 100) / 100;

        // Format data
        $format_data = array(
            'status'         => $status,
            'average'        => $average,
            'generalAverage' => $generalAverage,
            'config'         => $clean_data
        );

        file_put_contents($this->data_file, json_encode($format_data));
    }
}
