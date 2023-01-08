<?php

namespace FT\Sets\Tests;

use FT\Sets\Set;
use FT\Sets\SetPredicate;
use FT\Sets\Tests\Model\Foo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class SetTest extends TestCase {

    /**
    * @test
    * @dataProvider test_uniqueness_args
    */
    public function test_uniqueness_test(array $expected_array, array $dups, int $expected_size) {
        $merged = array_merge($expected_array, $dups);
        $this->assertGreaterThan(count($expected_array), count($merged));
        $set = new Set($merged);

        $this->assertEquals($expected_size, $set->size());
        $this->assertEquals($expected_array, $set->toArray(), var_export($set->toArray(), true));
    }

    private function test_uniqueness_args() {
        return [
            [
                ["abc", "123", "def", 1, "123 ", false, 2, "ABC"],
                ["abc", "def", "abc", false, 1, 1., "ABC"],
                8
            ],
            [
                ["abc", "123", "def", new Foo("bazz"), new Foo("buzz")],
                ["abc", "def", "123", new Foo("bazz")],
                5
            ],
            [
                [new stdClass, $this->build_stdClass(), $this->build_stdClass("foo")],
                [$this->build_stdClass(), new stdClass],
                3
            ],
            [
                [$this->build_anonymous_class("foobar"), $this->build_anonymous_class("foobazz"), $this->build_anonymous_class("foobuzz") ],
                [$this->build_anonymous_class("foobar"), $this->build_anonymous_class("foobuzz")],
                3
            ]
        ];
    }

    /**
    * @test
    */
    public function should_throw_for_null_test() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Set elements can not be null");

        $set = new Set;

        $set->add(1);
        $set->add(null);
    }

    /**
    * @test
    */
    public function should_not_throw_for_null_test() {
        $set = new Set(silently_ignore_null: true);

        $set->add(1);
        $set->add(null);
        $set->add(1);

        $this->assertEquals(1, $set->size());
    }

    /**
     * @test
     */
    public function iterator_test() {
        $set = new Set(array_merge($this->get_test_set()->toArray(), ['to_be_removed']));
        $expected = ["abc", "123", 1, 23, $this->build_stdClass(), $this->build_anonymous_class("foobar"), ['a', 'b']];

        $this->assertEquals(8, $set->size());
        foreach ($set as $value) {
            if ($value === "to_be_removed") {
                $set->remove($value);
                continue;
            }

            $index = array_search($value, $expected);
            $this->assertEquals(array_splice($expected, $index, 1)[0], $value);
        }

        $this->assertEmpty($expected);
        $this->assertEquals(7, $set->size());
    }

    /**
    * @test
    */
    public function clear_test() {
        $set = $this->get_test_set();

        $this->assertEquals(7, $set->size());
        $set->clear();
        $this->assertEmpty($set);
    }

    /**
     * @test
     * @dataProvider flatten_test_args
     */
    public function flatten_test(int $depth, $expected) {
        $set = new Set([
            "a",
            "b",
            [1,2,3],
            [2,3,4],
            [4,5,6],
            ['assck1' => 5, 'assck2' => 6, 'assck3' => 7,
                'assck4' => [
                    [6,7,8],
                    ['subk1' => 7, 'subk2' => 8, 'subk3' => 9, 'subk4' => [
                        9, 10,11,12,13
                    ]]
                ]
            ]
        ]);

        $result = $set->flatten($depth);
        $this->assertEquals($expected, $result->toArray());
    }

    public function flatten_test_args()
    {
        return [
            [1, ["a", "b", 1, 2, 3, 4, 5, 6, 7, [[6, 7, 8], ['subk1' => 7, 'subk2' => 8, 'subk3' => 9, 'subk4' => [9, 10, 11, 12, 13]]]]],
            [2, ["a", "b", 1, 2, 3, 4, 5, 6, 7, [6, 7, 8], ['subk1' => 7, 'subk2' => 8, 'subk3' =>  9, 'subk4' => [9, 10, 11, 12, 13]]]],
            [3, ["a", "b", 1, 2, 3, 4, 5, 6, 7, 8, 9, [9, 10, 11, 12, 13]]],
            [4, ["a", "b", 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]],
            [-1, ["a", "b", 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]]
        ];
    }

    /**
    * @test
    */
    public function contains_test() {
        $set = new Set([
            'a',
            'z',
            'x',
            'y',
            19235987,
            19235987.,
            new stdClass,
            $this->build_anonymous_class("foobar"),
            [[1],[2],[3],[4]]
        ]);

        $this->assertTrue($set->contains('a'));
        $this->assertFalse($set->contains('A'));
        $this->assertTrue($set->contains(19235987));
        $this->assertTrue($set->contains(19235987.0));
        $this->assertTrue($set->contains(new stdClass));
        $this->assertTrue($set->contains($this->build_anonymous_class("foobar")));
        $this->assertFalse($set->contains($this->build_anonymous_class("fooba")));
        $this->assertTrue($set->contains([[1],[2],[3],[4]]));
        $this->assertFalse($set->contains(null));
    }

    /**
     * @test
     */
    public function indexOf_test()
    {
        $set = new Set([
            'a',
            'z',
            'x',
            'y',
            19235987,
            19235987.,
            new stdClass,
            $this->build_anonymous_class("foobar"),
            [[1], [2], [3], [4]]
        ]);

        $this->assertGreaterThan(-1, $set->indexOf('a'));
        $this->assertGreaterThan(-1, $set->indexOf([[1], [2], [3], [4]]));
        $this->assertGreaterThan(-1, $set->indexOf(19235987));
    }

    /**
    * @test
    * @dataProvider union_test_args
    */
    public function union_test(Set $bset, array $expected) {
        $set = new Set([
            'a', 'c', 'e', 1, 3, 5, [7, 9]
        ]);

        $union = $set->union($bset)->toArray();
        sort($expected);
        sort($union);

        $this->assertSameSize($expected, $union);
        $this->assertEquals($expected, $union);
    }

    private function union_test_args() {
        $std = $this->build_stdClass();
        $anon = $this->build_anonymous_class("foobar");

        return [
            [new Set([]), ['a', 'c', 'e', 1, 3, 5, [7, 9]]],
            [new Set(['b', 'd', 'f', 2, 4, [6,8]]), ['a','b','c','d','e','f',1,2,3,4,5,[7,9], [6,8]]],
            [new Set(['b', 2, $std, $anon]), ['a','b','c','e',1,2,3,5,[7,9], $std, $anon]],
        ];
    }

    /**
    * @test
    * @dataProvider intersection_test_args
    */
    public function intersection_test(Set $bset, array $expected) {
        $set = new Set([
            'a', 'c', 'e', 1, 3, 5, [7, 9], $this->build_stdClass(), $this->build_anonymous_class("foobar")
        ]);

        $intersection = $set->intersection($bset)->toArray();
        sort($expected);
        sort($intersection);

        $this->assertSameSize($expected, $intersection);
        $this->assertEquals($expected, $intersection);
    }

    private function intersection_test_args() {
        return [
            [new Set([]), []],
            [new Set(['a', 'b', 'c', 'd', 'e', 1, 2, 3, 4, 5, [7, 9, 10]]), ['a','c','e', 1, 3, 5]],
            [new Set([[7, 9]]), [[7,9]]],
            [new Set(['a', 'b', 'c', 5, 1, $this->build_stdClass(), $this->build_anonymous_class("foobar")]), [
                'a','c',1,5,$this->build_stdClass(), $this->build_anonymous_class("foobar")
            ]]
        ];
    }

    /**
    * @test
    * @dataProvider difference_test_args
    */
    public function difference_test(Set $bset, array $expected) {
        $set = new Set([
            'a', 'c', 'e', 1, 3, 5, [7, 9], $this->build_stdClass(), $this->build_anonymous_class("foobar")
        ]);

        $difference = $set->difference($bset)->toArray();
        sort($expected);
        sort($difference);

        $this->assertSameSize($expected, $difference);
        $this->assertEquals($expected, $difference);
    }

    private function difference_test_args()
    {
        return [
            [new Set([]), []],
            [new Set(['a', 'b', 'c', 'd', 'e', 1, 2, 3, 4, 5, [7, 9, 10]]), ['b', 'd', 2,4,[7,9,10]]],
            [new Set([[7, 9], 'a','b', $this->build_anonymous_class("foobazz")]), [$this->build_anonymous_class("foobazz"), 'b']]
        ];
    }

    /**
    * @test
    * @dataProvider subset_test_args
    */
    public function subset_test(Set $bset, bool $expected) {
        $set = new Set([
            'a', 'c', 1, 3, [7, 9], $this->build_stdClass(), $this->build_anonymous_class("foobar")
        ]);

        $this->assertEquals($expected, $set->subset($bset));
    }

    private function subset_test_args() {
        return [
            [new Set([]), false],
            [new Set(['a','b','c',1,2,3,[4,5], [7,9], $this->build_anonymous_class("foobar"), $this->build_stdClass()]), true],
            [new Set(['a','b',1,2]), false]
        ];
    }

    /**
    * @test
    * @dataProvider equals_test_args
    */
    public function equals_test(Set $aset, Set $bset, bool $expected) {
        $this->assertEquals($expected, $aset->equals($bset));
    }

    private function equals_test_args() {
        return [
            [new Set([]), new Set([]), true],
            [new Set([1,2,3]), new Set([1,2,3]), true],
            [new Set(['a','b','c', 1,2,3]), new Set([1,2,3]), false],
            [new Set(['a','b','c', 1,2,3]), new Set(['a','b','c',1,2,3]), true],
            [
                new Set(['a','b',1,2,[3,4], $this->build_anonymous_class("foobar")]),
                new Set(['a','b',1,2,[3,4], $this->build_anonymous_class("foobarz")]),
                false
            ],
            [
                new Set(['a','b',1,2,[3,4], $this->build_anonymous_class("foobar")]),
                new Set(['a','b',1,2,[3,4], $this->build_anonymous_class("foobar")]),
                true
            ]
        ];
    }

    /**
     * @test
     */
    public function filter_test()
    {
        $set = $this->get_test_set();

        $set = $set->filter(fn ($i) => !is_object($i));
        $this->assertEquals(5, $set->size(), var_export($set, true));
        $this->assertContains("abc", $set);
        $this->assertContains("123", $set);
        $this->assertContains(1, $set);
        $this->assertContains(23, $set);

        $set = $set->filter('is_string');
        $this->assertEquals(2, $set->size());
        $this->assertContains('abc', $set);
        $this->assertContains('123', $set);
    }

    /**
    * @test
    */
    public function match_test() {
        $is_gt_1 = new class implements SetPredicate {
            public function __invoke(mixed $element): bool
            {
                return is_numeric($element) ? floatval($element) > 1 : false;
            }
        };

        $non_zero = fn ($i) => is_numeric($i) && floatval($i) > 0 || floatval($i) < 0;

        $non_empty = fn ($i) => !empty($i);

        $set = new Set([0,.1,.2,.3]);
        $this->assertFalse($set->anyMatch($is_gt_1));
        $this->assertFalse($set->anyMatch(fn ($i) => (int)$i > 1));
        $this->assertTrue($set->anyMatch($non_zero));

        $set->remove(0);
        $this->assertTrue($set->allMatch($non_zero));
        $this->assertTrue($set->allMatch($non_empty));

        $this->assertTrue($set->noneMatch(fn ($i) => is_object($i)));
        $this->assertTrue($set->noneMatch(fn ($i) => is_numeric($i) && floatval($i) < 0));
    }

    /**
    * @test
    */
    public function pop_test() {
        $set = $this->get_test_set();

        $this->assertEquals(['a', 'b'], $set->pop());
        $this->assertFalse($set->contains(['a', 'b']));

        $this->assertEquals($this->build_anonymous_class("foobar"), $set->pop());
        $this->assertFalse($set->contains($this->build_anonymous_class("foobar")));

        $this->assertEquals($this->build_stdClass(), $set->pop());
        $this->assertFalse($set->contains($this->build_stdClass()));

        $this->assertEquals(23, $set->pop());
        $this->assertFalse($set->contains(23));

        $this->assertEquals(1, $set->pop());
        $this->assertFalse($set->contains(1));

        $this->assertEquals("123", $set->pop());
        $this->assertFalse($set->contains("123"));

        $this->assertEquals("abc", $set->pop());
        $this->assertFalse($set->contains("abc"));

        $this->assertEmpty($set);

        $this->assertNull($set->pop());
    }

    /**
    * @test
    */
    public function shift_test() {
        $set = $this->get_test_set();

        $this->assertEquals("abc", $set->shift());
        $this->assertFalse($set->contains("abc"));

        $this->assertEquals("123", $set->shift());
        $this->assertFalse($set->contains("123"));

        $this->assertEquals(1, $set->shift());
        $this->assertFalse($set->contains(1));

        $this->assertEquals(23, $set->shift());
        $this->assertFalse($set->contains(23));

        $this->assertEquals($this->build_stdClass(), $set->shift());
        $this->assertFalse($set->contains($this->build_stdClass()));

        $this->assertEquals($this->build_anonymous_class("foobar"), $set->shift());
        $this->assertFalse($set->contains($this->build_anonymous_class("foobar")));

        $this->assertEquals(['a','b'], $set->shift());
        $this->assertFalse($set->contains(['a','b']));

        $this->assertEmpty($set);
        $this->assertNull($set->shift());

    }

    /**
    * @test
    */
    public function last_test() {
        $set = $this->get_test_set();

        $this->assertEquals(['a','b'], $set->last());
        $set->pop();
        $this->assertEquals($this->build_anonymous_class("foobar"), $set->last());
    }

    /**
    * @test
    */
    public function first_test() {
        $set = $this->get_test_set();

        $this->assertEquals("abc", $set->first());
        $set->shift();
        $this->assertEquals("123", $set->first());
    }

    /**
    * @test
    */
    public function toArray_test() {
        $set = $this->get_test_set();

        $expected = ['abc', '123', 1, 23, $this->build_stdClass(), $this->build_anonymous_class("foobar"), ['a','b']];

        $this->assertEquals($expected, $set->toArray());

        $keys = ['key1', 'key2', 'key3', 'key4', 'key5', 'key6', 'key7'];
        $this->assertEquals([
            'key1' => $expected[0],
            'key2' => $expected[1],
            'key3' => $expected[2],
            'key4' => $expected[3],
            'key5' => $expected[4],
            'key6' => $expected[5],
            'key7' => $expected[6],
        ], $set->toArray($keys));
    }

    /**
    * @test
    */
    public function choose_test() {
        $set = $this->get_test_set();

        $a = $set->choose();

        $this->assertTrue($set->contains($a));

        [$a, $b, $c] = $set->choose(3);
        $this->assertTrue($set->contains($a));
        $this->assertTrue($set->contains($b));
        $this->assertTrue($set->contains($c));

        $items = $set->choose(99999);
        $this->assertEquals(7, count($items));

        $items = $set->choose(-99);
        $this->assertEmpty($items);
    }

    private function get_test_set() {
        return new Set([
            "abc",
            "123",
            1,
            23,
            $this->build_stdClass(),
            $this->build_anonymous_class("foobar"),
            ['a', 'b'],
            23,
            1,
            "123",
            "abc"
        ]);
    }

    private function build_stdClass(string $foo = "bar")
    {
        $std = new stdClass;
        $std->foo = $foo;
        $std->std = new stdClass;
        $std->std->foo = "$foo buzz";
        $std->set = new Set([1,2,'a','b']);
        return $std;
    }

    private function build_anonymous_class(string $name)
    {
        $c = new class
        {
            public function __construct(public ?string $name = null)
            {
            }
        };
        return new $c($name);
    }

}

?>