<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/ConfigTestReset.php';

final class ConfigCacheTest extends TestCase
{
    use ConfigTestReset;

    protected function setUp(): void
    {
        $this->resetConfig();
    }

    public function testSetOnAKeyInvalidatesThatKey(): void
    {
        Config::set('admin', ['logging' => ['target' => 'aws']]);
        $this->assertSame('aws', Config::get('admin.logging.target'));

        Config::set('admin.logging.target', 'file');
        $this->assertSame('file', Config::get('admin.logging.target'));
    }

    public function testSetOnAnAncestorInvalidatesCachedDescendants(): void
    {
        Config::set('task', ['Group' => ['title' => 'Old', 'priorities' => ['A', 'B']]]);
        $this->assertSame('Old', Config::get('task.Group.title'));
        $this->assertSame(['A', 'B'], Config::get('task.Group.priorities'));

        Config::set('task.Group', ['title' => 'New', 'priorities' => ['C']]);

        $this->assertSame('New', Config::get('task.Group.title'));
        $this->assertSame(['C'], Config::get('task.Group.priorities'));
    }

    public function testSetOnAnUnrelatedKeyKeepsOtherCacheEntries(): void
    {
        Config::set('task', ['Group' => ['title' => 'Keep']]);
        Config::set('admin', ['logging' => ['target' => 'aws']]);
        $this->assertSame('Keep', Config::get('task.Group.title'));

        Config::set('admin.logging.target', 'file');

        $this->assertSame('Keep', Config::get('task.Group.title'));
    }

    public function testSetOnASiblingPrefixDoesNotInvalidate(): void
    {
        Config::set('task', ['Group' => 'kept', 'GroupOther' => 'x']);
        $this->assertSame('kept', Config::get('task.Group'));

        Config::set('task.GroupOther', 'y');

        $this->assertSame('kept', Config::get('task.Group'));
    }

    public function testKeysIsCorrectAfterANewModuleIsSet(): void
    {
        Config::set('admin', ['topmenu' => true, 'active' => true, 'path' => '/a']);
        $this->assertSame(['admin'], Config::keys());

        Config::set('task', ['topmenu' => true, 'active' => true, 'path' => '/t']);

        $this->assertSame(['admin', 'task'], Config::keys());
    }

    public function testKeysIsCorrectAfterAModuleGainsItsRequiredKeys(): void
    {
        Config::set('admin', ['topmenu' => true, 'active' => true, 'path' => '/a']);
        Config::set('task', ['topmenu' => true, 'active' => true]);
        $this->assertSame(['admin'], Config::keys());

        Config::set('task.path', '/t');

        $this->assertSame(['admin', 'task'], Config::keys());
    }

    public function testKeysIsCorrectAfterADeepWriteCreatesARequiredKey(): void
    {
        Config::set('admin', ['topmenu' => true, 'active' => true, 'path' => '/a']);
        Config::set('task.active', true);
        Config::set('task.path', '/t');
        $this->assertSame(['admin'], Config::keys());

        Config::set('task.topmenu.enabled', true);

        $this->assertSame(['admin', 'task'], Config::keys());
    }

    public function testFromJsonClearsTheCache(): void
    {
        Config::set('admin', ['logging' => ['target' => 'aws']]);
        $this->assertSame('aws', Config::get('admin.logging.target'));

        Config::fromJson('{"admin":{"logging":{"target":"file"}}}');

        $this->assertSame('file', Config::get('admin.logging.target'));
    }

    public function testSandboxInvalidationLeavesTheRealCacheAlone(): void
    {
        Config::set('admin', ['logging' => ['target' => 'aws']]);
        $this->assertSame('aws', Config::get('admin.logging.target'));

        Config::enableSandbox();
        Config::setSandbox(['admin' => ['logging' => ['target' => 'sandbox']]]);
        Config::set('admin.logging.target', 'sandbox-set');
        $this->assertSame('sandbox-set', Config::get('admin.logging.target'));
        Config::disableSandbox();

        $this->assertSame('aws', Config::get('admin.logging.target'));
    }

    public function testMergeSandboxLeavesNoStaleCacheEntry(): void
    {
        Config::set('admin', ['logging' => ['target' => 'aws']]);
        $this->assertSame('aws', Config::get('admin.logging.target'));

        Config::enableSandbox();
        Config::setSandbox(['admin' => ['logging' => ['target' => 'file']]]);
        Config::mergeSandbox();
        Config::disableSandbox();

        $this->assertSame('file', Config::get('admin.logging.target'));
    }

    public function testPromoteSandboxLeavesNoStaleSandboxCacheEntry(): void
    {
        Config::set('admin', ['logging' => ['target' => 'real']]);

        Config::enableSandbox();
        Config::setSandbox(['admin' => ['logging' => ['target' => 'sandbox']]]);
        $this->assertSame('sandbox', Config::get('admin.logging.target'));

        Config::promoteSandbox();

        $this->assertSame('real', Config::get('admin.logging.target'));
        Config::disableSandbox();
    }

    public function testSetSandboxLeavesNoStaleSandboxCacheEntry(): void
    {
        Config::enableSandbox();
        Config::setSandbox(['admin' => ['logging' => ['target' => 'first']]]);
        $this->assertSame('first', Config::get('admin.logging.target'));

        Config::setSandbox(['admin' => ['logging' => ['target' => 'second']]]);

        $this->assertSame('second', Config::get('admin.logging.target'));
        Config::disableSandbox();
    }
}
