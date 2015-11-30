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

    /**
     * @return array $return
     */
    public function setData($data = array())
    {
        // If ping every minute
        // I want to save 1 week
        // So 60 * 24 * 7 = 10080
        $clean_data = array();
        $alarm_data = array();
        foreach ($data as $website => $val) {
            $clean_data[$website] = array_slice($val, -10080, 10080, true);
            $alarm_data[$website] = array_slice($val, -3, 3);
        }

        // Average calcul
        $calc = array();
        $status = 200;
        foreach ($alarm_data as $website => $entry) {
            foreach ($entry as $key => $val) {
                $calc[] = $val['total_time'];
                $status = ($val['http_code'] > 400 || $status != 200) ? $val['http_code'] : 200;
            }
        }
        $average = round(array_sum($calc) / count($calc) * 100) / 100;

        // Formated date
        $format_data = array();
        $format_data['status'] = $status;
        $format_data['average'] = $average;
        $format_data['config'] = $clean_data;

        file_put_contents($this->data_file, json_encode($format_data));
    }
}
