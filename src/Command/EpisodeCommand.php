<?php

namespace RMF\Serferalls\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class EpisodeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('complete:me')
            ->setDescription('Greet autocompleted colors input')
            ->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $colors = array('red', 'blue', 'brown', 'yellow', 'yellow-light', 'yellow-dark');
        $validation = function ($color) use ($colors) {
            if (!in_array($color, array_values($colors))) {
                throw new \InvalidArgumentException(sprintf('Color "%s" is invalid.', $color));
            }

            return $color;
        };

        // ask and validate the answer
        $dialog = $this->getHelperSet()->get('dialog');
        $color = $dialog->askAndValidate($output, 'Enter your favorite color (default to red): ', $validation, false, 'red', $colors);

        $output->writeln(sprintf('You have just entered: <info>%s</info>', $color));
    }
}
