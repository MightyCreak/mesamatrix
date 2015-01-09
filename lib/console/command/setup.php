<?php
/*
 * Copyright (C) 2014 Romain "Creak" Failliot.
 * Copyright (C) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * This file is part of mesamatrix.
 *
 * mesamatrix is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * mesamatrix is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mesamatrix. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Mesamatrix\Console\Command;

use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

class Setup extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setName('setup')
             ->setDescription('Initialise Mesamatrix')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $git = new \Mesamatrix\Git\ProcessBuilder(array(
          'clone', '--bare', '--depth', \Mesamatrix::$config->getValue('git', 'depth'),
          \Mesamatrix::$config->getValue('git', 'url'), '@gitDir@'
        ));
        $this->getHelper('process')->mustRun($output, $git->getProcess());
    }
}
