<?php

namespace Spotlab\Sentinel\Command;

use Spotlab\Sentinel\Tools\Guardian;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

        $this->addArgument(
            'config',
            InputArgument::REQUIRED,
            'The path to the config file (.yml)'
        );
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
        // First step : Analysing config
        $config_path = $input->getArgument('config');
        $guardian = new Guardian($config_path);
        $output->writeln(sprintf('Analysing config file : <info>%s</info>', $config_path));
        $output->write("\n");

        // Actions for every projects in config
        $requests = $guardian->getRequests();
        $data = $guardian->getData();

        // Register time
        $now = time();

        foreach ($requests as $line => $config) {

            $output->writeln(sprintf('> Start : <info>%s</info>', $line));
            $output->writeln('------------------------------');

            // Création d'un gestionnaire curl
            $ch = curl_init();

            // Configuration de l'URL et d'autres options
            curl_setopt($ch, CURLOPT_URL, $config['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            if(!empty($config['method']) && $config['method'] == 'POST') curl_setopt($ch, CURLOPT_POST, true);
            if(!empty($config['header'])) curl_setopt($ch, CURLOPT_HTTPHEADER, $config['header']);
            if(!empty($config['content'])) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($config['content']));

            // // Exécution
            curl_exec($ch);

            // Vérification si une erreur est survenue
            if(!curl_errno($ch)) {
                $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $output->write('La requête a mis ' . $total_time . ' secondes (' . $http_code . ')');

                // if http_code error
                if ($http_code >= 400) {
                    $total_time = 0;
                }

                // Add Data
                $data[$line][] = array(
                    'date' => $now,
                    'total_time' => $total_time,
                    'http_code' => $http_code,
                );
            } else {
                $output->write(curl_error($ch));
                $total_time = 60;
                $http_code = 999;
            }

            // Add Data
            $data[$line][] = array(
                'date' => $now,
                'total_time' => $total_time,
                'http_code' => $http_code,
            );

            // Fermeture du gestionnaire
            curl_close($ch);

            $output->write("\n\n");
        }

        // Save date into file
        $output->writeln('> Save data');
        $output->writeln('------------------------------');
        $guardian->setData($data);
        $output->writeln('Finished : <info>Done</info>');
    }
}
