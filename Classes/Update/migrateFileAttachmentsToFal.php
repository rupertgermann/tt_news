<?php

namespace RG\TtNews\Update;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2024 Rupert Germann <rupi@gmx.li>
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
 * Migrates tt_news files from uploads/pics to FAL
 */
class migrateFileAttachmentsToFal implements UpgradeWizardInterface
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
        return 'migrateFileAttachmentsToFal';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return '[tt_news files to FAL] Migrates tt_news files from "uploads/media" to FAL';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Moves tt_news files from "uploads/media" to "fileadmin/migratedNewsAssets/Files" and registers them in FAL.
                         ******    CAUTION!!!    ******
Make sure you have made a backup of your database and of the "uploads/media" folder!
The database and filesystem operations are destructive, there is no rollback!
You have been warned ;-)';
    }

    /**
     * Checks whether updates are required.
     *
     * @return bool Whether an update is required (TRUE) or not (FALSE)
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
     */
    public function executeUpdate(): bool
    {
        $this->migrateNewsFilesToFal();

        return true;
    }

    /**
     */
    protected function migrateNewsFilesToFal()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        /** @var $conn Connection */
        $conn = $this->connectionPool->getConnectionForTable($this->table);

        $notMigratedNewsRecords = $conn->executeQuery(
            '
                SELECT n.uid, n.pid, n.news_files, r.uid as relId
                FROM tt_news n
                LEFT JOIN sys_file_reference r
                    ON r.uid_foreign = n.uid AND r.tablenames = \'tt_news\' AND r.fieldname = \'news_files\'

                WHERE n.news_files != \'\'
                  AND n.deleted = 0
                  AND r.uid IS NULL
                 '
        )->fetchAllAssociative();

        if (!empty($notMigratedNewsRecords)) {
            $sourceFolder = 'uploads/media/';
            $targetFolder = 'fileadmin/migratedNewsAssets/Files';
            $pathSite = Environment::getPublicPath() . '/';
            if (!is_dir($pathSite . $targetFolder)) {
                mkdir($pathSite . $targetFolder, 0777, true);
            }
            /** @var ResourceFactory $resourceFactory */
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            /** @var Folder $folder */
            $folder = $resourceFactory->retrieveFileOrFolderObject($pathSite . $targetFolder);

            foreach ($notMigratedNewsRecords as $newsRecord) {
                $files = GeneralUtility::trimExplode(',', $newsRecord['news_files']);
                if (!empty($files)) {
                    $existingFilesCount = 0;
                    foreach ($files as $file) {
                        if (file_exists($pathSite . $sourceFolder . $file)) {
                            $file = $folder->addFile($pathSite . $sourceFolder . $file, null, DuplicationBehavior::REPLACE);
                            if ($file instanceof File) {
                                $this->insertFileReference($file, $newsRecord);
                            }
                            $existingFilesCount++;
                        }
                    }
                    if ($existingFilesCount > 0) {
                        $conn->update(
                            $this->table,
                            ['news_files' => $existingFilesCount],
                            ['uid' => $newsRecord['uid']]
                        );
                    }
                }
            }
        }
    }

    /**
     * @param File  $file
     * @param array $newsRecord
     */
    protected function insertFileReference($file, $newsRecord)
    {
        $refTable = 'sys_file_reference';
        if (!empty($file)) {
            $insertFileRelFields = [
                'pid' => $newsRecord['pid'],
                'tablenames' => $this->table,
                'fieldname' => 'news_files',
                'table_local' => 'sys_file',
                'uid_local' => $file->getUid(),
                'uid_foreign' => $newsRecord['uid'],
            ];

            /** @var $conn Connection */
            $conn = $this->connectionPool->getConnectionForTable($refTable);
            $conn->insert($refTable, $insertFileRelFields);
            $lastInsertId = $conn->lastInsertId($refTable);

            /** @var ReferenceIndex $refIndexObj */
            $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
            $refIndexObj->updateRefIndexTable($refTable, $lastInsertId);
        }
    }

    /**
     * Check if there are news records with files that have not been migrated to fal
     *
     * @return bool
     * @throws InvalidArgumentException
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
                    ON r.uid_foreign = n.uid AND r.tablenames = \'tt_news\' AND r.fieldname = \'news_files\'

                WHERE n.news_files != \'\'
                  AND n.deleted = 0
                  AND r.uid IS NULL
                 '
        )->fetchAssociative();

        return $numberOfEntries['c'] > 0;
    }
}
