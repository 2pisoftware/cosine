<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/ConfigTestReset.php';

final class ConfigDocumentTest extends TestCase
{
    use ConfigTestReset;

    protected function setUp(): void
    {
        $this->resetConfig();
    }

    public function testPlainSectionsStillMerge(): void
    {
        Config::set('task', ['Group' => ['title' => 'Old', 'extra' => 'kept']]);

        Config::extendFromJson('{"task":{"Group":{"title":"Merged"}}}');

        $this->assertSame('Merged', Config::get('task.Group.title'));
        $this->assertSame('kept', Config::get('task.Group.extra'));
    }

    public function testSetReplacesAScalar(): void
    {
        Config::set('task', ['Group' => ['title' => 'Old']]);

        Config::extendFromJson('{"@set":{"task.Group.title":"New"}}');

        $this->assertSame('New', Config::get('task.Group.title'));
    }

    public function testSetReplacesAListRatherThanAppending(): void
    {
        Config::set('task', ['Group' => ['priorities' => ['A', 'B']]]);

        Config::extendFromJson('{"@set":{"task.Group.priorities":["C"]}}');

        $this->assertSame(['C'], Config::get('task.Group.priorities'));
    }

    public function testSetReplacesAWholeSubtree(): void
    {
        Config::set('task', ['Group' => ['title' => 'Old', 'dropped' => 'gone']]);

        Config::extendFromJson('{"@set":{"task.Group":{"title":"New"}}}');

        $this->assertSame(['title' => 'New'], Config::get('task.Group'));
        $this->assertNull(Config::get('task.Group.dropped'));
    }

    public function testAppendExtendsAList(): void
    {
        Config::set('system', ['allow_action' => ['a', 'b']]);

        Config::extendFromJson('{"@append":{"system.allow_action":["c"]}}');

        $this->assertSame(['a', 'b', 'c'], Config::get('system.allow_action'));
    }

    public function testAppendMergesAnAssociativeArray(): void
    {
        Config::set('task', ['Group' => ['tasktypes' => ['Existing' => 'One']]]);

        Config::extendFromJson('{"@append":{"task.Group.tasktypes":{"Added":"Two"}}}');

        $this->assertSame(['Existing' => 'One', 'Added' => 'Two'], Config::get('task.Group.tasktypes'));
    }

    public function testAppendCreatesAKeyThatDoesNotExist(): void
    {
        Config::extendFromJson('{"@append":{"system.allow_action":["a"]}}');

        $this->assertSame(['a'], Config::get('system.allow_action'));
    }

    public function testPhasesRunMergeThenSetThenAppend(): void
    {
        Config::set('system', ['allow_action' => ['original']]);

        Config::extendFromJson('{
            "system": {"allow_action": ["merged"]},
            "@append": {"system.allow_action": ["appended"]},
            "@set": {"system.allow_action": ["reset"]}
        }');

        // merge appends "merged", @set discards both, @append then adds to it
        $this->assertSame(['reset', 'appended'], Config::get('system.allow_action'));
    }

    public function testSectionsAreRecognisedOnlyAtTheDocumentRoot(): void
    {
        Config::extendFromJson('{"email":{"transports":{"@set":{"host":"smtp"}}}}');

        $this->assertSame(['host' => 'smtp'], Config::get('email.transports.@set'));
    }

    public function testADocumentWithOnlySectionsIsApplied(): void
    {
        Config::set('task', ['Group' => ['title' => 'Old']]);

        Config::extendFromJson('{"@set":{"task.Group.title":"Only"}}');

        $this->assertSame('Only', Config::get('task.Group.title'));
    }

    public function testEmptyAndMalformedDocumentsAreIgnored(): void
    {
        Config::set('task', ['Group' => ['title' => 'Old']]);

        Config::extendFromJson('');
        Config::extendFromJson('not json');

        $this->assertSame('Old', Config::get('task.Group.title'));
    }

    public function testACachedDescendantIsFreshAfterSetReplacesItsAncestor(): void
    {
        Config::set('task', ['Group' => ['title' => 'Old', 'priorities' => ['A']]]);
        $this->assertSame('Old', Config::get('task.Group.title'));

        Config::extendFromJson('{"@set":{"task.Group":{"title":"New","priorities":["C"]}}}');

        $this->assertSame('New', Config::get('task.Group.title'));
        $this->assertSame(['C'], Config::get('task.Group.priorities'));
    }

    public function testACachedAncestorIsFreshAfterAppendTouchesADescendant(): void
    {
        Config::set('system', ['allow_action' => ['a']]);
        $this->assertSame(['allow_action' => ['a']], Config::get('system'));

        Config::extendFromJson('{"@append":{"system.allow_action":["b"]}}');

        $this->assertSame(['allow_action' => ['a', 'b']], Config::get('system'));
    }

    public function testAListShapedSectionIsIgnored(): void
    {
        Config::set('admin', ['topmenu' => true, 'active' => true, 'path' => '/a']);

        Config::extendFromJson('{"@set":["system.timeout"]}');

        $this->assertSame(['admin'], Config::keys());
        $this->assertNull(Config::get('0'));
    }

    public function testANonArrayDocumentIsIgnored(): void
    {
        Config::set('task', ['Group' => ['title' => 'Old']]);

        Config::extendFromJson('"just a string"');
        Config::extendFromJson('42');

        $this->assertSame('Old', Config::get('task.Group.title'));
    }

    public function testALaterDocumentPlainSectionAppliesAfterAnEarlierSet(): void
    {
        Config::set('system', ['allow_action' => ['original']]);

        Config::extendFromJson('{"@set":{"system.allow_action":["only"]}}');
        $this->assertSame(['only'], Config::get('system.allow_action'));

        Config::extendFromJson('{"system":{"allow_action":["b"]}}');

        $this->assertSame(['only', 'b'], Config::get('system.allow_action'));
    }
}
