<?php

namespace Spotlab\Sentinel\Command;

use Spotlab\Sentinel\Services\Tools;
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
        $guardian = new Tools($input->getArgument('config'), $output);
        $guardian->execute();
    }
}
