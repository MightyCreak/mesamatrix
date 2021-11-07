<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Mesamatrix\Console;

use Mesamatrix\Mesamatrix;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

class Application extends \Symfony\Component\Console\Application
{
    public function __construct()
    {
        parent::__construct('Mesamatrix CLI', Mesamatrix::$config->getValue('info', 'version'));

        $dispatcher = new EventDispatcher();

        // Install CLI logger
        $dispatcher->addListener(ConsoleEvents::COMMAND,
            function (ConsoleCommandEvent $e)
            {
                $logger = new \Monolog\Logger(
                    'cli.'.$e->getCommand()->getName(),
                    Mesamatrix::$logger->getHandlers()
                );
                $logger->pushHandler(
                    new \Symfony\Bridge\Monolog\Handler\ConsoleHandler($e->getOutput())
                );
                Mesamatrix::$logger = $logger;
            }
        );

        $this->setDispatcher($dispatcher);
    }

    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        // Set default output verbosity
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);

        return parent::configureIO($input, $output);
    }

    protected function getDefaultCommands()
    {
        return array_merge(parent::getDefaultCommands(), array(
            new Command\Parse(),
            new Command\Setup(),
            new Command\Fetch()
        ));
    }
}
