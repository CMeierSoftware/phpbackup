<?php

declare(strict_types=1);

namespace CMS\PhpBackup\App;
use CMS\PhpBackup\Helper\FileHelper;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Backup\DatabaseBackupCreator;
use CMS\PhpBackup\Backup\FileBackupCreator;
use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\FileBundleCreator;
use CMS\PhpBackup\Core\FileCrypt;
use CMS\PhpBackup\Core\Step;
use CMS\PhpBackup\Core\StepResult;
use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Remote\Local;

class BackupRunner extends AbstractRunner
{
    private const MISC_FILE = 'BackupRunner_misc';

    private array $misc;

    public function __construct(AppConfig $config)
    {
        parent::__construct($config);

        $miscDefault = [
            'backup_folder' => '',
            'bundles' => [],
            'archives' => [],
        ];

        try {
            $misc = $this->config->readTempData(self::MISC_FILE);
            if(null === $misc || empty($misc)) {
                $this->misc = $miscDefault;
            } else {
                $this->misc = $misc;
                $this->misc['bundles'] = json_decode($this->misc['bundles'], true);
                $this->misc['archives'] = json_decode($this->misc['archives'], true);
            }
        } catch (FileNotFoundException $th) {
            $this->misc = $miscDefault;
        }
    }

    public function __destruct()
    {
        $this->misc['bundles'] = json_encode($this->misc['bundles']);
        $this->misc['archives'] = json_encode($this->misc['archives']);

        $this->config->saveTempData(self::MISC_FILE, $this->misc);
        parent::__destruct();
    }

    public function setupStep(): StepResult
    {
        $srcDir = $this->config->getBackupDirectory()['src'];
        $limit = (int) ($this->config->getBackupSettings()['maxArchiveSize']);
        $this->misc['bundles'] = FileBundleCreator::createFileBundles($srcDir, $limit);

        $this->misc['backup_folder'] = TEMP_DIR . 'backup_' . date('Y-m-d_H-i-s') . DIRECTORY_SEPARATOR;
        FileHelper::makeDir($this->misc['backup_folder']);

        return new StepResult('Setup done.', false);
    }

    public function makeDirectoryBackupStep(): StepResult
    {
        $idx = null !== $this->misc['archives'] ? count($this->misc['archives']) : 0;
        $f = new FileBackupCreator();

        $result = $f->backupOnly($this->config->getBackupDirectory()['src'], $this->misc['bundles'][$idx]);
        $this->logger->Info("Archive files to '{$result}'");

        FileCrypt::encryptFile($result, $this->config->getBackupSettings()['encryptionKey']);

        $result = $this->copyToTempDir($result, "archive_part_{$idx}.zip");

        $this->misc['archives'][$result] = $this->misc['bundles'][$idx];

        $cntBundles = count($this->misc['bundles']);
        $cntArchives = count($this->misc['archives']);

        $this->logger->Info("Archived {$cntArchives} of {$cntBundles} bundles.");

        return new StepResult($result, $cntArchives < $cntBundles);
    }

    public function makeSqlBackupStep(): StepResult
    {
        $result = '';
        if ($cfg = $this->config->getBackupDatabase()) {
            $this->logger->Info("start database dump of ({$cfg['host']})");
            $db = new DatabaseBackupCreator($cfg['host'], $cfg['username'], $cfg['password'], $cfg['dbname']);

            $result = $db->backupMySql();

            if (!$result) {
                $this->logger->Warning('Database dump could not be created.');
            } else {
                $this->logger->Info("Database dump to '{$result}'");
            }

            FileCrypt::encryptFile($result, $this->config->getBackupSettings()['encryptionKey']);

            $result = $this->copyToTempDir($result, basename($result));

            $this->misc['archives'][$result] = 'Database dump';
        } else {
            $this->logger->Info('No database defined in config.');
        }

        return new StepResult($result, false);
    }

    public function sendLocal(): StepResult
    {
        $localConfig = $this->config->getRemoteSettings()['local'];

        $local = new Local($localConfig['rootDir']);
        $local->connect();
        $backupDirName = basename($this->misc['backup_folder']);
        $uploadedFiles = [];

        if (!$local->fileExists($backupDirName)) {
            $local->createDirectory($backupDirName);
        }
        foreach ($this->misc['archives'] as $archivePath => $content) {
            $local->fileUpload($archivePath, $backupDirName . '/' . basename($archivePath));
            $uploadedFiles[basename($archivePath)] = $content;
        }

        // Specify the file path
        $fileMapping = $this->misc['backup_folder'] . 'file_mapping.json';
        file_put_contents($fileMapping, json_encode($uploadedFiles, JSON_PRETTY_PRINT));

        $local->fileUpload($fileMapping, $backupDirName . '/' . basename($fileMapping));

        return new StepResult('', false);
    }

    public function cleanUp(): StepResult
    {
        FileHelper::deleteDirectory($this->misc['backup_folder']);
        $this->misc['bundles'] = [];
        $this->misc['archives'] = [];
        return new StepResult('', false);
    }

    protected function setupSteps(): void
    {
        $this->steps = [
            new Step([$this, 'setupStep']),
            new Step([$this, 'makeDirectoryBackupStep']),
            new Step([$this, 'makeSqlBackupStep']),
            new Step([$this, 'sendLocal']),
            new Step([$this, 'cleanUp']),
        ];
    }

    private function copyToTempDir(string $file, string $newName): string
    {
        $newFile = $this->misc['backup_folder'] . $newName;
        rename($file, $newFile);
        $this->logger->Info("Move file from '{$file}' to '{$newFile}'");

        return $newFile;
    }
}
