<?php

/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014 Romain "Creak" Failliot.
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

namespace Mesamatrix\Console\Command;

use Mesamatrix\Git\Process;
use Mesamatrix\Mesamatrix;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Fetch extends Command
{
    protected function configure(): void
    {
        $this->setName('fetch')
             ->setDescription('Perform update of Mesa git repository')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $branch = Mesamatrix::$config->getValue("git", "branch");
        $fetch = new Process(array('fetch', '-f', 'origin', "$branch:$branch"));
        $processHelper = $this->getHelper('process');
        if ($processHelper instanceof ProcessHelper) {
            $processHelper->mustRun($output, $fetch);
        }

        return Command::SUCCESS;
    }
}
