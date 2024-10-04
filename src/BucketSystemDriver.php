<?php

namespace kyagie\Auditing\Drivers;

use DateTime;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use OwenIt\Auditing\Contracts\Audit;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\AuditDriver;


class BucketSystemDriver implements AuditDriver
{
    protected $disk = null;
    protected $dir = null;
    protected $filename = null;
    protected $auditFilepath = null;
    protected $fileLoggingType = null;


    public function __construct()
    {
        $this->disk = Storage::disk(Config::get('audit.drivers.filesystem.disk', 's3'));
        $this->dir = Config::get('audit.drivers.filesystem.dir', '');
        $this->filename = Config::get('audit.drivers.filesystem.filename', 'audit.csv');
        $this->fileLoggingType = Config::get('audit.drivers.filesystem.logging_type', 'single');
        $this->auditFilepath = $this->auditFilepath();
    }

    public function audit(Auditable $model): Audit
    {
        if (!$this->disk->exists($this->auditFilepath)) {
            $this->disk->put($this->auditFilepath, $this->auditModelToCsv($model, true));
        } else {
            $existingContent = $this->disk->get($this->auditFilepath);
            $newContent = $existingContent . "\n" . $this->auditModelToCsv($model);
            $this->disk->put($this->auditFilepath, $newContent);
        }

        $implementation = Config::get('audit.implementation', \OwenIt\Auditing\Models\Audit::class);

        return new $implementation;
    }

    public function prune(Auditable $model): bool
    {
        return false;
    }

    protected function auditModelToCsv(Auditable $model, bool $includeHeader = false)
    {
        $writer = Writer::createFromFileObject(new \SplTempFileObject());

        $auditArray = $this->sanitize($this->getAuditFromModel($model));
        if ($includeHeader) {
            $writer->insertOne($this->headerRow($auditArray));
        }
        $writer->insertOne($auditArray);

        return trim($writer->toString());
    }

    protected function sanitize(array $audit)
    {
        $audit['old_values'] = json_encode($audit['old_values']);
        $audit['new_values'] = json_encode($audit['new_values']);

        return $audit;
    }

    protected function auditFilepath()
    {
        switch ($this->fileLoggingType) {
            case 'single':
                return $this->dir . $this->filename;
            case 'daily':
                $date = (new \DateTime('now'))->format('Y-m-d');
                return $this->dir . "audit-$date.csv";
            case 'hourly':
                $dateTime = (new \DateTime('now'))->format('Y-m-d-H');
                return $this->dir . "audit-$dateTime-00-00.csv";
            default:
                throw new \InvalidArgumentException("File logging type {$this->fileLoggingType} unknown. Please use one of 'single', 'daily' or 'hourly'.");
        }
    }

    protected function getAuditFromModel(Auditable $model)
    {
        return $this->appendCreatedAt($model->toAudit());
    }

    protected function appendCreatedAt(array $audit)
    {
        return array_merge($audit, ['created_at' => (new DateTime('now'))->format('Y-m-d H:i:s')]);
    }

    protected function headerRow(array $audit)
    {
        return array_map(function ($key) {
            return ucwords(str_replace('_', ' ', $key));
        }, array_keys($audit));
    }
}
