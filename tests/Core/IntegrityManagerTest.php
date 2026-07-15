<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\IntegrityManager;
use PHPUnit\Framework\TestCase;

class IntegrityManagerTest extends TestCase
{
    private string $testEntity;
    private string $testDir;
    private string $integrityDir;

    protected function setUp(): void
    {
        $this->testEntity = 'test_int_' . bin2hex(random_bytes(4));
        $this->testDir = BASE_PATH . '/data/' . $this->testEntity;
        $this->integrityDir = BASE_PATH . '/data/.integrity';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
        if (!is_dir($this->integrityDir)) {
            mkdir($this->integrityDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($this->testDir);
        }

        $checksumPath = $this->integrityDir . '/checksums.json';
        if (file_exists($checksumPath)) {
            $data = json_decode(file_get_contents($checksumPath), true) ?? [];
            unset($data[$this->testEntity]);
            if (empty($data)) {
                @unlink($checksumPath);
            } else {
                file_put_contents($checksumPath, json_encode($data, JSON_PRETTY_PRINT));
            }
        }
    }

    public function testRecordAndVerify(): void
    {
        $file = $this->testDir . '/verify_1.json';
        $content = ['id' => 'verify_1', 'title' => 'Test'];
        file_put_contents($file, json_encode($content));

        $manager = IntegrityManager::instance();
        $manager->recordEntity($this->testEntity, 'verify_1');

        $this->assertTrue($manager->verifyEntity($this->testEntity, 'verify_1'));
    }

    public function testVerifyCorrupted(): void
    {
        $file = $this->testDir . '/corrupt_1.json';
        file_put_contents($file, json_encode(['id' => 'corrupt_1', 'title' => 'Original']));

        $manager = IntegrityManager::instance();
        $manager->recordEntity($this->testEntity, 'corrupt_1');

        // Corrupt the file
        file_put_contents($file, json_encode(['id' => 'corrupt_1', 'title' => 'Modified']));

        $this->assertFalse($manager->verifyEntity($this->testEntity, 'corrupt_1'));
    }

    public function testVerifyMissingFile(): void
    {
        $manager = IntegrityManager::instance();
        // Record then delete
        $file = $this->testDir . '/gone_1.json';
        file_put_contents($file, json_encode(['id' => 'gone_1']));
        $manager->recordEntity($this->testEntity, 'gone_1');
        @unlink($file);

        // Missing file + recorded = not valid
        $this->assertFalse($manager->verifyEntity($this->testEntity, 'gone_1'));
    }

    public function testRemoveEntity(): void
    {
        $manager = IntegrityManager::instance();
        $file = $this->testDir . '/rem_1.json';
        file_put_contents($file, json_encode(['id' => 'rem_1']));
        $manager->recordEntity($this->testEntity, 'rem_1');
        $manager->removeEntity($this->testEntity, 'rem_1');

        // File still exists but checksum removed → verifyEntity falls through to hash check
        // with no stored hash → returns false. Delete file to test the "missing file" path.
        @unlink($file);
        $this->assertTrue($manager->verifyEntity($this->testEntity, 'rem_1'));
    }

    public function testRecordAll(): void
    {
        file_put_contents($this->testDir . '/a.json', json_encode(['id' => 'a']));
        file_put_contents($this->testDir . '/b.json', json_encode(['id' => 'b']));

        $manager = IntegrityManager::instance();
        $manager->recordAll();

        $this->assertTrue($manager->verifyEntity($this->testEntity, 'a'));
        $this->assertTrue($manager->verifyEntity($this->testEntity, 'b'));
    }

    public function testVerifyAll(): void
    {
        file_put_contents($this->testDir . '/v1.json', json_encode(['id' => 'v1']));
        file_put_contents($this->testDir . '/v2.json', json_encode(['id' => 'v2']));

        $manager = IntegrityManager::instance();
        $manager->recordAll();

        $results = $manager->verifyAll();
        $this->assertGreaterThan(0, $results['total']);
        $this->assertEquals($results['total'], $results['valid']);
        $this->assertEquals(0, $results['corrupted']);
    }
}
