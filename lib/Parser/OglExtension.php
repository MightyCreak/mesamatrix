<?php
/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2014 Romain "Creak" Failliot.
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

namespace Mesamatrix\Parser;

class OglExtension
{
    /**
     * OglExtension constructor.
     *
     * @param string $name The extension name.
     * @param string $status The extension status (@see Constants::STATUS_...).
     * @param string $hint The extension hint.
     * @param \Mesamatrix\Parser\Hints $hints The hint manager.
     * @param string[] $supportedDrivers The drivers supporting the extension.
     * @param string[] $apiDrivers All the possible drivers for the API.
     */
    public function __construct($name, $status, $hint, \Mesamatrix\Parser\Hints $hints,
            array $supportedDrivers = array(), array $apiDrivers = array()) {
        $this->hints = $hints;
        $this->subextensions = array();
        $this->setName($name);
        $this->setStatus($status);
        $this->setHint($hint);
        $this->setModifiedAt(null);

        $this->supportedDrivers = array();
        foreach ($supportedDrivers as $driverNameAndHint) {
            list($driverName, $driverHint) = self::splitDriverNameAndHint($driverNameAndHint, $apiDrivers);
            if ($driverName === null) {
                \Mesamatrix::$logger->error('Unrecognized driver: '.$driverName);
            }

            $supportedDriver = new OglSupportedDriver($driverName, $this->hints);
            $supportedDriver->setHint($driverHint);
            $this->addSupportedDriver($supportedDriver, null);
        }
    }

    /**
     * Split the given string between an official API driver and its hint.
     *
     * @param string $driverNameAndHint The whole string containing the driver and its hint.
     * @param string[] $apiDrivers All the possible drivers for the API.
     *
     * @return string[] An array of two string; 0: the driver name, 1: its hint.
     */
    private static function splitDriverNameAndHint($driverNameAndHint, array $apiDrivers) {
        $driverName = null;
        $driverHint = "";
        $i = 0;
        $numApiDrivers = count($apiDrivers);
        while ($i < $numApiDrivers && $driverName === null) {
            $apiDriverName = $apiDrivers[$i];
            $apiDriverLen = strlen($apiDriverName);
            if (strncmp($driverNameAndHint, $apiDriverName, $apiDriverLen) === 0) {
                $driverName = $apiDriverName;
                $driverHint = substr($driverNameAndHint, $apiDriverLen + 1);
            }

            ++$i;
        }

        return array($driverName, $driverHint);
    }

    // name
    public function setName($name) {
        $this->name = $name;
    }
    public function getName() {
        return $this->name;
    }

    // status
    public function setStatus($status) {
        $this->status = $status;
    }
    public function getStatus() {
        return $this->status;
    }

    // hint
    public function setHint($hint) {
        $this->hintIdx = $this->hints->addToHints($hint);
    }
    public function getHintIdx() {
        return $this->hintIdx;
    }

    // supported drivers
    public function addSupportedDriver(OglSupportedDriver $driver, \Mesamatrix\Git\Commit $commit = null) {
        if ($existingDriver = $this->getSupportedDriverByName($driver->getName())) {
            $existingDriver->incorporate($driver, $commit);
            return $existingDriver;
        }
        else {
            $driver->setModifiedAt($commit);
            $this->supportedDrivers[] = $driver;
            return $driver;
        }
    }
    public function getSupportedDrivers() {
        return $this->supportedDrivers;
    }
    public function getSupportedDriverByName($driverName) {
        foreach ($this->supportedDrivers as $supportedDriver) {
            $matchName = $supportedDriver->getName();
            if (strncmp($driverName, $matchName, strlen($matchName)) === 0) {
                return $supportedDriver;
            }
        }

        return NULL;
    }

    // modified at
    public function setModifiedAt($commit) {
        $this->modifiedAt = $commit;
    }
    public function getModifiedAt() {
        return $this->modifiedAt;
    }

    // merge
    public function incorporate($other, $commit) {
        if ($this->name !== $other->name) {
            \Mesamatrix::$logger->error('Merging extensions with different names');
        }
        if ($this->status !== $other->status) {
            $this->status = $other->status;
            $this->setModifiedAt($commit);
        }

        if ($this->hintIdx !== $other->hintIdx) {
            $this->hintIdx = $other->hintIdx;
            $this->setModifiedAt($commit);
        }
        foreach ($other->supportedDrivers as $supportedDriver) {
            $this->addSupportedDriver($supportedDriver, $commit);
        }
    }

    /**
     * Add a sub-extension, or merge it if it already exists.
     *
     * @param OglExtension $extension The extension to add/merge.
     * @param \Mesamatrix\Git\Commit $commit The commit used by the parser.
     */
    public function addSubExtension(OglExtension $extension, \Mesamatrix\Git\Commit $commit) {
        $retSubExt = null;
        $existingSubExt = $this->findSubExtensionByName($extension->getName());
        if($existingSubExt !== null) {
            $existingSubExt->incorporate($extension, $commit);
            $retSubExt = $existingSubExt;
        }
        else {
            $extension->setModifiedAt($commit);
            $this->subextensions[] = $extension;
            $retSubExt = $extension;
        }

        return $retSubExt;
    }

    /**
     * Remove a sub-extension.
     *
     * @param OglExtension $extension The extension to remove.
     */
    public function removeSubExtension(OglExtension $extension) {
        $idx = array_search($extension, $this->subextensions);
        if ($idx !== false) {
            array_splice($this->subextensions, $idx, 1);
        }
    }

    /**
     * Detects if the current extension depends on another one and merge the drivers.
     *
     * @param \Mesamatrix\Parser\OglMatrix $glMatrix The entire matrix.
     */
    public function solveExtensionDependencies($glMatrix) {
        if ($this->getHintIdx() === -1) {
            return;
        }

        $hint = $this->hints->allHints[$this->getHintIdx()];
        foreach (Constants::RE_DEP_DRIVERS_HINTS as $reDepDriversHint) {
            if (preg_match($reDepDriversHint[0], $hint, $matches) === 1) {
                switch ($reDepDriversHint[2]) {
                    case DependsOn::Extension:
                    {
                        $glDepExt = $glMatrix->getExtensionBySubstr($matches[$reDepDriversHint[3]]);
                        if ($glDepExt !== NULL) {
                            foreach ($glDepExt->supportedDrivers as $supportedDriver) {
                                $this->addSupportedDriver($supportedDriver, $this->getModifiedAt());
                            }
                        }
                    }
                    break;

                    case DependsOn::GlesVersion:
                    {
                        $supportedDriverNames = $glMatrix->getDriversSupportingGlesVersion($matches[$reDepDriversHint[3]]);
                        if ($supportedDriverNames !== NULL) {
                            foreach ($supportedDriverNames as $supportedDriverName) {
                                $supportedDriver = new OglSupportedDriver($supportedDriverName, $this->hints);
                                if ($reDepDriversHint[2]) {
                                    $supportedDriver->setHint($hint);
                                }

                                $this->addSupportedDriver($supportedDriver, $this->getModifiedAt());
                            }
                        }
                    }
                    break;
                }
            }
        }
    }

    public function merge(OglVersion $glSection, \SimpleXMLElement $xmlExt, \Mesamatrix\Git\Commit $commit) {
        $xmlSubExts = $xmlExt->xpath('./subextensions/subextension');

        // Remove old sub-extensions.
        $numXmlSubExts = count($xmlSubExts);
        foreach ($this->getSubExtensions() as $glSubExt) {
            $glSubExtName = $glSubExt->getName();

            // Find sub-extension in the XML.
            $i = 0;
            while ($i < $numXmlSubExts && (string) $xmlSubExts[$i]['name'] !== $glSubExtName) {
                ++$i;
            }

            if ($i === $numXmlSubExts) {
                // Extension not found in XML, remove it.
                \Mesamatrix::$logger->debug('In '.$glSection->getGlName().' '.$glSection->getGlVersion().
                                            ', extension '.$this->getName().
                                            ', remove GL sub-extension: '.$glSubExtName);
                $this->removeSubExtension($glSubExt);
            }
        }

        // Add and merge new sub-extensions.
        foreach ($xmlSubExts as $xmlSubExt) {
            $subExtName = (string) $xmlSubExt['name'];
            $subExtStatus = (string) $xmlSubExt->mesa['status'];
            $subExtHint = (string) $xmlSubExt->mesa['hint'];

            $newSubExtension = new OglExtension($subExtName, $subExtStatus, $subExtHint, $this->hints, array());
            $xmlSupportedDrivers = $xmlSubExt->xpath("./supported-drivers/driver");
            foreach ($xmlSupportedDrivers as $xmlSupportedDriver) {
                // Create new supported driver.
                $driverName = (string) $xmlSupportedDriver['name'];
                $driverHint = (string) $xmlSupportedDriver['hint'];

                $driver = new OglSupportedDriver($driverName, $this->hints);
                $driver->setHint($driverHint);
                $newSubExtension->addSupportedDriver($driver, $commit);
            }

            // Add the sub-extension.
            $glSubExt = $this->addSubExtension($newSubExtension, $commit);
        }
    }

    /**
     * Get the list of all sub-extensions.
     *
     * @return OglExtension[] All the sub-extensions.
     */
    public function getSubExtensions() {
        return $this->subextensions;
    }

    /**
     * Find the extensions with the given name.
     *
     * @param string $name The name of the extension to find.
     *
     * @return OglExtension The extension or null if not found.
     */
    private function findSubExtensionByName($name) {
        foreach ($this->subextensions as $subext) {
            if($subext->getName() === $name) {
                return $subext;
            }
        }

        return null;
    }

    private $name;
    private $status;
    private $hints;
    private $hintIdx;
    private $supportedDrivers;
    private $modifiedAt;
    private $subextensions;
};
