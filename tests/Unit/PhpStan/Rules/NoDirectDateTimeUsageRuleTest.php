<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Tests\Unit\PhpStan\Rules;

use PHPUnit\Framework\TestCase;

use function is_array;
use function is_resource;

final class NoDirectDateTimeUsageRuleTest extends TestCase
{
    public function testReportsErrorWhenUsingDateTimeDirectly(): void
    {
        $root    = realpath(__DIR__ . '/../../../..');
        $fixture = $root . '/tests/Fixtures/PhpStan/NoDirectDateTimeUsage/BadUsage.php';

        [$exitCode, $errors] = $this->runPhpStan([
            'EnterpriseToolingForSymfony\\SharedBundle\\PhpStan\\Rules\\NoDirectDateTimeUsageRule',
        ], $fixture);

        $this->assertSame(1, $exitCode, 'PHPStan should report an error for direct DateTime usage');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Direct usage of DateTimeImmutable is not allowed', (string) ($errors[0]['message'] ?? ''));
    }

    public function testPassesWhenNoDirectDateTimeUsage(): void
    {
        $root    = realpath(__DIR__ . '/../../../..');
        $fixture = $root . '/tests/Fixtures/PhpStan/NoDirectDateTimeUsage/GoodUsage.php';

        [$exitCode, $errors] = $this->runPhpStan([
            'EnterpriseToolingForSymfony\\SharedBundle\\PhpStan\\Rules\\NoDirectDateTimeUsageRule',
        ], $fixture);

        $this->assertSame(0, $exitCode, 'PHPStan should not report errors');
        $this->assertSame([], $errors);
    }

    /**
     * @param list<string> $rules
     *
     * @return array{0:int,1:array<int,array{message:string,filePath:string,line:int}>}
     */
    private function runPhpStan(array $rules, string $pathToAnalyze): array
    {
        $config  = $this->createTempNeon($rules, $pathToAnalyze);
        $root    = realpath(__DIR__ . '/../../../..');
        $phpstan = $root . '/vendor/bin/phpstan';

        $cmd = sprintf(
            '%s analyze --no-progress --error-format=json --autoload-file=%s -c %s %s',
            escapeshellcmd($phpstan),
            escapeshellarg($root . '/vendor/autoload.php'),
            escapeshellarg($config),
            escapeshellarg($pathToAnalyze)
        );

        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $cwd     = $root === false ? null : $root;
        $process = proc_open($cmd, $descriptorSpec, $pipes, $cwd);
        if (!is_resource($process)) {
            $this->fail('Failed to start PHPStan process');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        $exitCode = proc_close($process);

        /** @var array{files?: array<int, array{path?: string, messages?: array<int, array{message?: string, line?: int}>}>}|null $decoded */
        $decoded = json_decode($stdout ?: '[]', true);
        $errors  = [];
        if (is_array($decoded)) {
            $files = $decoded['files'] ?? null;
            if (is_array($files)) {
                foreach ($files as $fileInfo) {
                    $messages = $fileInfo['messages'] ?? null;
                    if (!is_array($messages)) {
                        continue;
                    }
                    foreach ($messages as $msg) {
                        $message  = (string) ($msg['message'] ?? '');
                        $filePath = (string) ($fileInfo['path'] ?? '');
                        $line     = (int) ($msg['line'] ?? 0);
                        $errors[] = [
                            'message'  => $message,
                            'filePath' => $filePath,
                            'line'     => $line,
                        ];
                    }
                }
            }
        }

        // In case of unexpected output, help debugging
        if ($exitCode !== 0 && $errors === []) {
            fwrite(STDERR, "PHPStan stderr:\n" . ($stderr ?: '') . "\n");
            fwrite(STDERR, "PHPStan stdout:\n" . ($stdout ?: '') . "\n");
        }

        return [$exitCode, $errors];
    }

    /**
     * @param list<string> $rules
     */
    private function createTempNeon(array $rules, string $pathToAnalyze): string
    {
        $neon = "parameters:\n" .
            "    level: 0\n" .
            "    paths:\n" .
            '        - ' . $pathToAnalyze . "\n" .
            "rules:\n";

        foreach ($rules as $rule) {
            $neon .= '    - ' . $rule . "\n";
        }

        $tmp = tempnam(sys_get_temp_dir(), 'phpstan-config-');
        if ($tmp === false) {
            $this->fail('Failed to create temp file for PHPStan config');
        }
        $neonPath = $tmp . '.neon';
        rename($tmp, $neonPath);
        file_put_contents($neonPath, $neon);

        return $neonPath;
    }
}
