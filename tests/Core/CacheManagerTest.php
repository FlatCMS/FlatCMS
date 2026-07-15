<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\CacheManager;
use PHPUnit\Framework\TestCase;

class CacheManagerTest extends TestCase
{
    protected function setUp(): void
    {
        $cachePath = BASE_PATH . '/storage/cache/data';
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        $files = glob($cachePath . '/*.json');
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    protected function tearDown(): void
    {
        $cachePath = BASE_PATH . '/storage/cache/data';
        $files = glob($cachePath . '/*.json');
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    public function testSetAndGet(): void
    {
        $cache = CacheManager::instance();
        $cache->set('test_key', ['hello' => 'world'], 60);

        $result = $cache->get('test_key');
        $this->assertEquals(['hello' => 'world'], $result);
    }

    public function testGetDefault(): void
    {
        $cache = CacheManager::instance();
        $result = $cache->get('nonexistent', 'fallback');
        $this->assertEquals('fallback', $result);
    }

    public function testHas(): void
    {
        $cache = CacheManager::instance();
        $this->assertFalse($cache->has('not_here'));

        $cache->set('exists', true, 60);
        $this->assertTrue($cache->has('exists'));
    }

    public function testForget(): void
    {
        $cache = CacheManager::instance();
        $cache->set('forget_me', 'value', 60);
        $this->assertTrue($cache->has('forget_me'));

        $cache->forget('forget_me');
        $this->assertFalse($cache->has('forget_me'));
    }

    public function testRemember(): void
    {
        $cache = CacheManager::instance();
        $callCount = 0;

        $result1 = $cache->remember('remember_test', function () use (&$callCount) {
            $callCount++;
            return 'computed_value';
        }, 60);

        $this->assertEquals('computed_value', $result1);
        $this->assertEquals(1, $callCount);

        $result2 = $cache->remember('remember_test', function () use (&$callCount) {
            $callCount++;
            return 'should_not_be_called';
        }, 60);

        $this->assertEquals('computed_value', $result2);
        $this->assertEquals(1, $callCount);
    }

    public function testClear(): void
    {
        $cache = CacheManager::instance();
        $cache->set('clear_a', 'a', 60);
        $cache->set('clear_b', 'b', 60);

        $cache->clear();

        $this->assertFalse($cache->has('clear_a'));
        $this->assertFalse($cache->has('clear_b'));
    }

    public function testPersistenceToFile(): void
    {
        $cache = CacheManager::instance();
        $cache->set('persist_test', 'persisted', 60);

        // Simulate new request by clearing memory
        $reflection = new \ReflectionClass($cache);
        $prop = $reflection->getProperty('memory');
        $prop->setValue($cache, []);

        // Should still be available from file
        $result = $cache->get('persist_test');
        $this->assertEquals('persisted', $result);
    }

    public function testExpiration(): void
    {
        $cache = CacheManager::instance();
        $cache->set('expire_test', 'value', 1);

        // Manually expire the file
        $cachePath = BASE_PATH . '/storage/cache/data/' . md5('expire_test') . '.json';
        $data = json_decode(file_get_contents($cachePath), true);
        $data['expires_at'] = time() - 10;
        file_put_contents($cachePath, json_encode($data));

        // Clear memory to force file read
        $reflection = new \ReflectionClass($cache);
        $prop = $reflection->getProperty('memory');
        $prop->setValue($cache, []);

        $result = $cache->get('expire_test', 'expired');
        $this->assertEquals('expired', $result);
    }

    public function testAtomicWrite(): void
    {
        $cache = CacheManager::instance();
        $cache->set('atomic', 'test', 60);

        $path = BASE_PATH . '/storage/cache/data/' . md5('atomic') . '.json';
        $this->assertFileExists($path);
        $this->assertFileDoesNotExist($path . '.tmp');
    }

    public function testCleanupRemovesExpiredFiles(): void
    {
        $cache = CacheManager::instance();
        $cachePath = BASE_PATH . '/storage/cache/data';

        // Create valid cache file
        $cache->set('valid_entry', 'alive', 3600);

        // Manually create an expired file
        $expiredPayload = [
            'value' => 'dead',
            'created_at' => time() - 100,
            'expires_at' => time() - 10,
        ];
        file_put_contents($cachePath . '/expired_manual.json', json_encode($expiredPayload));

        $removed = $cache->cleanup();
        $this->assertEquals(1, $removed);

        $this->assertFileDoesNotExist($cachePath . '/expired_manual.json');
        $this->assertTrue($cache->has('valid_entry'));
    }

    public function testCleanupRemovesCorruptFiles(): void
    {
        $cache = CacheManager::instance();
        $cachePath = BASE_PATH . '/storage/cache/data';

        // Create a corrupt (non-JSON) file
        file_put_contents($cachePath . '/corrupt.json', 'not-json{{{');

        $removed = $cache->cleanup();
        $this->assertEquals(1, $removed);

        $this->assertFileDoesNotExist($cachePath . '/corrupt.json');
    }
}
