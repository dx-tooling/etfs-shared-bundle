<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Storage\Service;

use Aws\S3\S3Client;
use RuntimeException;
use Throwable;

readonly class AwsS3Service
{
    private S3Client $s3Client;

    public function __construct()
    {
        if (!array_key_exists('AWS_S3_REGION', $_ENV)) {
            throw new RuntimeException('AWS_S3_REGION environment variable is not set');
        }

        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => $_ENV['AWS_S3_REGION']
        ]);
    }

    public function uploadLocalFile(
        string $localFilePath,
        string $s3BucketName,
        string $s3FilePath
    ): void {
        if (!file_exists($localFilePath)) {
            throw new RuntimeException(sprintf('Local file not found: %s', $localFilePath));
        }

        try {
            $this->s3Client->putObject([
                'Bucket' => $s3BucketName,
                'Key'    => $s3FilePath,
                'Body'   => fopen($localFilePath, 'rb'),
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException(
                sprintf(
                    'Failed to upload file to S3. Bucket: %s, Key: %s, Error: %s',
                    $s3BucketName,
                    $s3FilePath,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }
    }
}
