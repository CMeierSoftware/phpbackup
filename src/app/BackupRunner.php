<?php

declare(strict_types=1);

namespace CMS\PhpBackup\App;

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
    private const BUNDLES_FILE = self::class . '_bundles';
    private const ARCHIVES_FILE = self::class . '_files';
    private const MISC_FILE = self::class . '_misc';

    private array $misc;

    public function __construct(AppConfig $config)
    {
        parent::__construct($config);

        try {
            $this->misc = $this->config->readTempData(self::MISC_FILE);
        } catch (FileNotFoundException $th) {
            $this->misc = [];
        }
    }

    public function __destruct()
    {
        $this->config->saveTempData(self::MISC_FILE, $this->misc);
    }

    public function setup(): StepResult
    {
        $srcDir = $this->config->getBackupDirectory()['src'];
        $limit = $this->config->getBackupSettings()['maxArchiveSize'];
        $bundles = FileBundleCreator::createFileBundles($srcDir, $limit);
        $cntBundles = count($bundles);
        $this->config->saveTempData(self::BUNDLES_FILE, $bundles);
        $this->logger->Info("Split {$srcDir} into {$cntBundles} parts (size limit: {$limit}).");

        $this->misc['backup_folder'] = TEMP_DIR . DIRECTORY_SEPARATOR . 'backup_' . date('_Y-m-d_H-i-s') . DIRECTORY_SEPARATOR;
        mkdir($this->misc['backup_folder'], 0o644, true);
        $this->logger->Info('Create backup folder: ' . $this->misc['backup_folder']);

        return new StepResult('', false);
    }

    public function makeDirectoryBackup(): StepResult
    {
        $bundles = $this->config->readTempData(self::BUNDLES_FILE);

        try {
            $archives = $this->config->readTempData(self::ARCHIVES_FILE);
        } catch (FileNotFoundException $th) {
            $archives = [];
        }
        $idx = count($archives);
        $f = new FileBackupCreator();

        $result = $f->backupOnly($this->config->getBackupDirectory()['src'], $bundles[$idx]);
        $this->logger->Info("Archive files to '{$result}'");

        FileCrypt::encryptFile($result, $this->config->getBackupSettings()['encryptionKey']);

        $result = $this->copyToTempDir($result, "archive_part_{$idx}.zip");

        $archives[$result] = $bundles[$idx];

        return new StepResult($result, count($bundles) == count($archives));
    }

    public function makeSqlBackup(): StepResult
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

            $files = $this->config->readTempData(self::ARCHIVES_FILE);
            $files[$result] = 'Database dump';
            $this->config->saveTempData(self::ARCHIVES_FILE, $files);
        } else {
            $this->logger->Info('No database defined in config.');
        }

        return new StepResult($result, false);
    }

    public function sendLocal(): StepResult
    {
        $localConfig = $this->config->getRemoteSettings()['local'];

        $local = new Local($localConfig['rootDir']);
        $backupDirName = basename($this->misc['backup_folder']);
        $archives = $this->config->readTempData(self::ARCHIVES_FILE);
        $uploadedFiles = [];

        $local->createDirectory($backupDirName);
        foreach ($archives as $archivePath => $content) {
            $local->fileUpload($archivePath, $backupDirName);
            $uploadedFiles[basename($archivePath)] = $content;
        }

        // todo: save uploadedFiles in remote storage

        return new StepResult('', false);
    }

    public function cleanUp(): StepResult
    {
        return new StepResult('', false);
    }

    protected function setupSteps(): void
    {
        $this->steps = [
            new Step([$this, 'setup']),
            new Step([$this, 'makeDirectoryBackup']),
            new Step([$this, 'makeSqlBackup']),
            new Step([$this, 'sendLocal']),
            new Step([$this, 'cleanUp']),
        ];
    }

    private function copyToTempDir(string $file, string $newName)
    {
        $newFile = $this->misc['backup_folder'] . $newName;
        rename($file, $newFile);
        $this->logger->Info("Move file from '{$file}' to '{$newFile}'");

        return $newFile;
    }
}
