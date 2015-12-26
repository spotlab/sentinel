<?php

namespace Spotlab\Sentinel\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Spotlab\Sentinel\Services\ConfigProvider;
use Spotlab\Sentinel\Services\SQLiteDatabase;

/**
 * Class Backup
 * @package Spotlab\Sentinel\Command
 */
class Ping extends Command
{
    /**
     * Set us up the command!
     */
    public function configure()
    {
        $this->setName('ping')
             ->setDescription('Ping all websites');
    }

    /**
     * Parses the clover XML file and spits out coverage results to the console.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // Create Guzzle CLient
        $client = new Client();

        // // Create Database
        $this->db = new SQLiteDatabase();

        // Get Projects
        $configProvider = new ConfigProvider();
        $projects = $configProvider->getProjects();
        $output->writeln(sprintf('FIND : <comment>%s projects</comment>', count($projects)));

        // Register time
        $now = time();

        foreach ($projects as $project_name => $project) {
            //$this->output->writeln(sprintf('> Start project <info>%s</info>', $project_name));
            foreach ($project['requests'] as $request_name => $request) {
                $serie_name = $project_name . '_' . $request_name;
                //$this->output->writeln(sprintf('> Start serie <info>%s</info>', $serie_name));

                // Get Options
                $options = array();
                $options['future'] = true;
                $options['allow_redirects'] = true;
                $options['timeout'] = 30;

                // Get Request params
                if(!empty($request['headers'])) $options['headers'] = $request['headers'];
                if(!empty($request['body'])) $options['body'] = $request['body'];

                // Init Ping Object
                $ping = array();
                $ping['project'] = $project_name;
                $ping['serie'] = $request_name;
                $ping['ping_date'] = $now;

                // Start Guzzle Requests
                $time_start = microtime(true);
                $req = $client->createRequest($request['method'], $request['url'], $options);
                $client->send($req)->then(
                    function ($response) use ($output, $ping, $serie_name, $time_start) {
                        $time_end = microtime(true);
                        $time = $time_end - $time_start;

                        // Update Ping Data
                        $ping['ping_time'] = $time;
                        $ping['http_status'] = $response->getStatusCode();
                        $ping['error'] = false;

                        // Insert Ping on Database
                        $this->db->insert($ping);
                        $output->writeln(sprintf('SUCCESS %s <info>%s</info>', $serie_name, $time));
                    },
                    function ($error) use ($output, $ping, $serie_name, $time_start)  {
                        $time_end = microtime(true);
                        $time = $time_end - $time_start;

                        // Update Ping Data
                        $ping['ping_time'] = $time;
                        $ping['error'] = true;
                        $ping['error_log'] = $error->getMessage();

                        if($error->getCode() !== 0) {
                            $ping['http_status'] = $error->getCode();
                        }

                        // Insert Ping on Database
                        $this->db->insert($ping);
                        $output->writeln(sprintf('FAILED %s <error>%s</error>', $serie_name, $error->getMessage()));
                        throw $error;
                    }
                );
            }
        }
    }
}
