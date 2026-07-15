<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\FlatFile;
use PHPUnit\Framework\TestCase;

class FlatFileTest extends TestCase
{
    private string $testEntity;
    private string $testDir;

    protected function setUp(): void
    {
        $this->testEntity = 'test_' . bin2hex(random_bytes(4));
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

    public function testCreate(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $result = $ff->create(['title' => 'Test', 'content' => 'Hello']);

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Test', $result['title']);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('updated_at', $result);
    }

    public function testFind(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $created = $ff->create(['title' => 'Find Me']);
        $found = $ff->find($created['id']);

        $this->assertNotNull($found);
        $this->assertEquals('Find Me', $found['title']);
    }

    public function testFindNotFound(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $this->assertNull($ff->find('nonexistent'));
    }

    public function testUpdate(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $created = $ff->create(['title' => 'Original']);
        $updated = $ff->update($created['id'], ['title' => 'Updated']);

        $this->assertNotNull($updated);
        $this->assertEquals('Updated', $updated['title']);
        $this->assertEquals($created['id'], $updated['id']);
    }

    public function testUpdateNotFound(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $this->assertNull($ff->update('nonexistent', ['title' => 'X']));
    }

    public function testDelete(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $created = $ff->create(['title' => 'Delete Me']);
        $this->assertTrue($ff->delete($created['id']));
        $this->assertNull($ff->find($created['id']));
    }

    public function testDeleteNotFound(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $this->assertFalse($ff->delete('nonexistent'));
    }

    public function testExists(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $created = $ff->create(['title' => 'Exists']);
        $this->assertTrue($ff->exists($created['id']));
        $this->assertFalse($ff->exists('nonexistent'));
    }

    public function testCount(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $this->assertEquals(0, $ff->count());

        $ff->create(['title' => 'A']);
        $this->assertEquals(1, $ff->count());

        $ff->create(['title' => 'B']);
        $this->assertEquals(2, $ff->count());
    }

    public function testAll(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $ff->create(['title' => 'A']);
        $ff->create(['title' => 'B']);

        $all = $ff->all();
        $this->assertCount(2, $all);
    }

    public function testFindBy(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $ff->create(['title' => 'Unique', 'status' => 'draft']);
        $ff->create(['title' => 'Other', 'status' => 'published']);

        $found = $ff->findBy('status', 'draft');
        $this->assertNotNull($found);
        $this->assertEquals('Unique', $found['title']);
    }

    public function testWhere(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $ff->create(['title' => 'A', 'category' => 'tech']);
        $ff->create(['title' => 'B', 'category' => 'tech']);
        $ff->create(['title' => 'C', 'category' => 'life']);

        $results = $ff->where('category', 'tech');
        $this->assertCount(2, $results);
    }

    public function testPaginate(): void
    {
        $ff = FlatFile::for($this->testEntity);
        for ($i = 0; $i < 25; $i++) {
            $ff->create(['title' => "Item $i"]);
        }

        $page1 = $ff->paginate(1, 10);
        $this->assertCount(10, $page1['data']);
        $this->assertEquals(25, $page1['total']);
        $this->assertEquals(3, $page1['total_pages']);
        $this->assertTrue($page1['has_more']);

        $page3 = $ff->paginate(3, 10);
        $this->assertCount(5, $page3['data']);
        $this->assertFalse($page3['has_more']);
    }

    public function testSearch(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $ff->create(['title' => 'PHP Tutorial']);
        $ff->create(['title' => 'JavaScript Guide']);
        $ff->create(['title' => 'Python Basics']);

        $results = $ff->search('php', ['title']);
        $this->assertCount(1, $results);
    }

    public function testEntity(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $this->assertEquals($this->testEntity, $ff->entity());
    }

    public function testAtomicWrite(): void
    {
        $ff = FlatFile::for($this->testEntity);
        $created = $ff->create(['title' => 'Atomic']);

        $path = $this->testDir . '/' . $created['id'] . '.json';
        $this->assertFileExists($path);
        $this->assertFileDoesNotExist($path . '.tmp');
    }
}
