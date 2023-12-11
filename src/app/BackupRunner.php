<?php

declare(strict_types=1);

namespace CMS\PhpBackup\App;

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\App\AbstractRunner;
use CMS\PhpBackup\Backup\DatabaseBackupCreator;
use CMS\PhpBackup\Core\FileBundleCreator;
use CMS\PhpBackup\Core\Step;

class BackupRunner extends AbstractRunner
{
    private const FILE_BUNDLES_FILE = 'bundles.json';
    public function calculateFileBundles(): void
    {
        $srcDir = $this->config->getBackupDirectory()['src'];
        $bundles = FileBundleCreator::createFileBundles($srcDir, $this->config->getBackupSettings()['maxArchiveSize']);
        $cntBundles = count($bundles);

        $this->logger->Info("Split {$srcDir} into {$cntBundles} parts.");

        file_put_contents($this->config->getTmp() . self::FILE_BUNDLES_FILE, json_encode($bundles));
    }

    public function makeSqlBackup(): string
    {
        if ($cfg = $this->config->getBackupDatabase()) {
            $this->logger->Info("start zipping database ({$cfg['host']})");
            $db = new DatabaseBackupCreator($cfg['host'], $cfg['username'], $cfg['password'], $cfg['dbname']);

            $result = $db->backupMySql();

            if (!$result) {
                $this->logger->Warning('Database dump could not be created.');
            } else {
                $this->logger->Info("Database dump to '$result'");
            }

            return $result;
        } else {
            $this->logger->Info("No database defined in config.");
        }
        return '';
    }


    protected function setupSteps()
    {
        $fileBundleStep = new Step();
        $fileBundleStep->setCallback([$this, 'makeSqlBackup']);
        $dbStep = new Step();
        $dbStep->setCallback([$this, 'makeSqlBackup']);

        $this->steps = [
            $fileBundleStep,
            $dbStep,
        ];
    }
}
