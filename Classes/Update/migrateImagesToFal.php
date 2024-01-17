<?php

namespace RG\TtNews\Update;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2020 Rupert Germann <rupi@gmx.li>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Doctrine\DBAL\DBALException;
use InvalidArgumentException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Migrates tt_news images from uploads/pics to FAL
 */
class migrateImagesToFal implements UpgradeWizardInterface
{
    /**
     * @var string
     */
    protected $table = 'tt_news';
    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'migrateImagesToFal';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return '[tt_news images to FAL] Migrates tt_news images from "uploads/pics" to FAL';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Moves tt_news images from "uploads/pics" to "fileadmin/migratedNewsAssets/Images" and registers them in FAL. 
                         ******    CAUTION!!!    ******
Make sure you have made a backup of your database and of the "uploads/pics" folder! 
The database and filesystem operations are destructive, there is no rollback! 
You have been warned ;-)';
    }

    /**
     * Checks whether updates are required.
     *
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     * @throws DBALException
     */
    public function updateNecessary(): bool
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $updateNeeded = false;
        // Check if the database table even exists
        if ($this->checkIfWizardIsRequired()) {
            $updateNeeded = true;
        }

        return $updateNeeded;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Performs the accordant updates.
     *
     * @return bool Whether everything went smoothly or not
     * @throws DBALException
     */
    public function executeUpdate(): bool
    {
        $this->migrateNewsImagesToFal();

        return true;
    }

    /**
     * @throws DBALException
     */
    protected function migrateNewsImagesToFal()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        /** @var $conn Connection */
        $conn = $this->connectionPool->getConnectionForTable($this->table);

        $notMigratedNewsRecords = $conn->executeQuery(
            '
                SELECT n.uid, n.pid, n.image, r.uid as relId, n.imagecaption, n.imagealttext, n.imagetitletext
                FROM tt_news n 
                LEFT JOIN sys_file_reference r 
                    ON r.uid_foreign = n.uid AND r.tablenames = \'tt_news\' AND r.fieldname = \'image\' AND r.deleted = 0
                
                WHERE n.image != \'\'
                  AND n.deleted = 0
                  AND r.uid IS NULL
                 '
        )->fetchAll();

        if (!empty($notMigratedNewsRecords)) {
            $sourceFolder = 'uploads/pics/';
            $targetFolder = 'fileadmin/migratedNewsAssets/Images';
            $pathSite = Environment::getPublicPath() . '/';
            if (!is_dir($pathSite . $targetFolder)) {
                mkdir($pathSite . $targetFolder, 0777, true);
            }
            /** @var ResourceFactory $resourceFactory */
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            /** @var Folder $folder */
            $folder = $resourceFactory->retrieveFileOrFolderObject($pathSite . $targetFolder);

            foreach ($notMigratedNewsRecords as $newsRecord) {
                $images = GeneralUtility::trimExplode(',', $newsRecord['image']);
                $imagecaptions = GeneralUtility::trimExplode(chr(10), $newsRecord['imagecaption']);
                $imagealttexts = GeneralUtility::trimExplode(chr(10), $newsRecord['imagealttext']);
                $imagetitletexts = GeneralUtility::trimExplode(chr(10), $newsRecord['imagetitletext']);
                if (!empty($images)) {
                    $existingImagesCount = 0;
                    foreach ($images as $k => $image) {
                        if (file_exists($pathSite . $sourceFolder . $image)) {
                            $file = $folder->addFile($pathSite . $sourceFolder . $image, null, DuplicationBehavior::REPLACE);
                            if ($file instanceof File) {
                                $this->insertFileReference($file, $newsRecord, $imagecaptions[$k], $imagealttexts[$k], $imagetitletexts[$k]);
                            }
                            $existingImagesCount++;
                        }
                    }
                    if ($existingImagesCount > 0) {
                        $conn->update(
                            $this->table,
                            ['image' => $existingImagesCount],
                            ['uid' => $newsRecord['uid']]
                        );
                    }
                }
            }
        }
    }

    /**
     * @param File   $file
     * @param array  $newsRecord
     * @param string $imagecaption
     * @param string $imagealttext
     * @param string $imagetitletext
     */
    protected function insertFileReference($file, $newsRecord, $imagecaption, $imagealttext, $imagetitletext)
    {
        $refTable = 'sys_file_reference';
        if (!empty($file)) {
            $insertFileRelFields = [
                'pid' => $newsRecord['pid'],
                'tablenames' => $this->table,
                'fieldname' => 'image',
                'table_local' => 'sys_file',
                'uid_local' => $file->getUid(),
                'uid_foreign' => $newsRecord['uid'],
                'title' => $imagetitletext,
                'description' => $imagecaption,
                'alternative' => $imagealttext,
            ];

            /** @var $conn Connection */
            $conn = $this->connectionPool->getConnectionForTable($refTable);
            $conn->insert($refTable, $insertFileRelFields);
            $lastInsertId = $conn->lastInsertId($refTable);

            /** @var ReferenceIndex $refIndexObj */
            $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
            $refIndexObj->enableRuntimeCache();
            $refIndexObj->updateRefIndexTable($refTable, $lastInsertId);
        }
    }

    /**
     * Check if there are news records with images that have not been migrated to fal
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws DBALException
     */
    protected function checkIfWizardIsRequired(): bool
    {
        /** @var $conn Connection */
        $conn = $this->connectionPool->getConnectionForTable($this->table);

        $numberOfEntries = $conn->executeQuery(
            '
                SELECT count(*) as c
                FROM tt_news n 
                LEFT JOIN sys_file_reference r 
                    ON r.uid_foreign = n.uid AND r.tablenames = \'tt_news\' AND r.fieldname = \'image\' AND r.deleted = 0
                
                WHERE n.image != \'\'
                  AND n.deleted = 0
                  AND r.uid IS NULL
                 '
        )->fetch();

        return $numberOfEntries['c'] > 0;
    }
}
