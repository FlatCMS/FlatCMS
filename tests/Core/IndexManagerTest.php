<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\IndexManager;
use PHPUnit\Framework\TestCase;

class IndexManagerTest extends TestCase
{
    private string $testEntity;
    private string $testDir;

    protected function setUp(): void
    {
        $this->testEntity = 'test_idx_' . bin2hex(random_bytes(4));
        $this->testDir = BASE_PATH . '/data/' . $this->testEntity;
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
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
        $indexPath = BASE_PATH . '/data/.index/' . $this->testEntity . '.json';
        if (file_exists($indexPath)) {
            @unlink($indexPath);
        }
    }

    public function testGetIndexEmpty(): void
    {
        $manager = IndexManager::instance();
        $index = $manager->getIndex($this->testEntity);
        $this->assertIsArray($index);
        $this->assertEmpty($index);
    }

    public function testOnEntitySaved(): void
    {
        $manager = IndexManager::instance();
        $data = [
            'id' => 'item_1',
            'title' => 'Test Item',
            'slug' => 'test-item',
            'status' => 'published',
            'created_at' => '2026-01-01 12:00:00',
            'updated_at' => '2026-01-01 12:00:00',
        ];

        $manager->onEntitySaved($this->testEntity, 'item_1', $data);
        $index = $manager->getIndex($this->testEntity);

        $this->assertArrayHasKey('item_1', $index);
        $this->assertEquals('Test Item', $index['item_1']['title']);
        $this->assertEquals('test-item', $index['item_1']['slug']);
    }

    public function testOnEntityDeleted(): void
    {
        $manager = IndexManager::instance();
        $data = [
            'id' => 'item_1',
            'title' => 'To Delete',
            'created_at' => '2026-01-01 12:00:00',
            'updated_at' => '2026-01-01 12:00:00',
        ];

        $manager->onEntitySaved($this->testEntity, 'item_1', $data);
        $manager->onEntityDeleted($this->testEntity, 'item_1');
        $index = $manager->getIndex($this->testEntity);

        $this->assertArrayNotHasKey('item_1', $index);
    }

    public function testCount(): void
    {
        $manager = IndexManager::instance();
        $this->assertEquals(0, $manager->count($this->testEntity));

        $manager->onEntitySaved($this->testEntity, 'a', ['id' => 'a', 'title' => 'A', 'created_at' => '', 'updated_at' => '']);
        $this->assertEquals(1, $manager->count($this->testEntity));
    }

    public function testRebuildIndex(): void
    {
        $file1 = $this->testDir . '/item_1.json';
        $file2 = $this->testDir . '/item_2.json';

        file_put_contents($file1, json_encode([
            'id' => 'item_1', 'title' => 'File A',
            'created_at' => '2026-01-01', 'updated_at' => '2026-01-01',
        ]));
        file_put_contents($file2, json_encode([
            'id' => 'item_2', 'title' => 'File B',
            'created_at' => '2026-01-02', 'updated_at' => '2026-01-02',
        ]));

        $manager = IndexManager::instance();
        $manager->rebuildIndex($this->testEntity);
        $index = $manager->getIndex($this->testEntity);

        $this->assertCount(2, $index);
        $this->assertArrayHasKey('item_1', $index);
        $this->assertArrayHasKey('item_2', $index);
    }

    public function testIndexPersistsToFile(): void
    {
        $manager = IndexManager::instance();
        $manager->onEntitySaved($this->testEntity, 'persist_1', [
            'id' => 'persist_1', 'title' => 'Persist', 'created_at' => '', 'updated_at' => '',
        ]);

        $indexPath = BASE_PATH . '/data/.index/' . $this->testEntity . '.json';
        $this->assertFileExists($indexPath);

        $content = json_decode(file_get_contents($indexPath), true);
        $this->assertArrayHasKey('persist_1', $content);
    }
}
