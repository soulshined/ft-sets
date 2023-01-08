<?php

namespace FT\Sets\Tests;

use FT\Sets\Set;
use FT\Sets\SortedSet;
use FT\Sets\Tests\Model\Foo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SortedSetTest extends TestCase {

    /**
    * @test
    */
    public function test_uniqueness_test() {
        $set = new SortedSet;

        $set->add("abc");
        $set->add("123");
        $set->add("def");
        $set->add("abc");
        $set->add("123 ");
        $set->add("def");
        $set->add("abc");
        $set->add("ABC");
        $set->add("ABC");

        $this->assertEquals(5, $set->size());
        $this->assertEquals([
            "123",
            "123 ",
            "abc",
            "ABC",
            "def"
        ], $set->toArray());
    }

    /**
    * @test
    */
    public function should_not_allow_multiple_types_test() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FT\Sets\SortedSet managed type string does not expect integer');

        $set = new SortedSet;

        $set->add("abc");
        $set->add(123);
    }

    /**
    * @test
    * @dataProvider reverse_test_args
    */
    public function reverse_test(SortedSet $aset, array $expected) {
        $this->assertEquals($expected, $aset->reverse()->toArray());
    }

    private function reverse_test_args() {
        return [
            [new SortedSet(["abc", "123", "def", "abc", "123 ", "def", "ABC"]), [
                "def",
                "abc",
                "ABC",
                "123 ",
                "123"
            ]],
            [new SortedSet([50, 1,3,2,6,5,7,4,9,8,10,15,20]),[
                50, 20, 15, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1
            ]],
            [
                new SortedSet([new Foo("bar"), new Foo("abc"), new Foo("123")]),
                [
                    new Foo("bar"),
                    new Foo("abc"),
                    new Foo("123")
                ]
            ]
        ];
    }

    /**
     * @test
     */
    public function indexOf_test()
    {
        $set = new SortedSet([
            'a',
            'z',
            'x',
            'y'
        ]);

        $this->assertEquals(0, $set->indexOf('a'));
        $this->assertEquals(1, $set->indexOf('x'));
        $this->assertEquals(2, $set->indexOf('y'));
        $this->assertEquals(3, $set->indexOf('z'));
    }

    /**
     * @test
     */
    public function partition_test()
    {
        $set = new SortedSet([
            'a',
            'z',
            'x',
            'y'
        ]);

        $sub = $set->partition(99, 99);
        $this->assertEmpty($sub);

        $sub = $set->partition(0, 1);
        $this->assertEquals(['a'], $sub->toArray());

        $sub = $set->partition(1, 1);
        $this->assertEquals(['x'], $sub->toArray());

        $sub = $set->partition(1, 3);
        $this->assertEquals(['x', 'y', 'z'], $sub->toArray());

        $sub = $set->partition(1);
        $this->assertEquals(['x', 'y', 'z'], $sub->toArray());

        $sub = $set->partition(1, -1);
        $this->assertEquals(['x', 'y'], $sub->toArray());

        $sub = $set->partition(-3, -1);
        $this->assertEquals(['x', 'y'], $sub->toArray());
    }

    /**
    * @test
    */
    public function head_tail_set_test() {
        $set = new SortedSet([1,2,3,4,5,6,7,8,9,10]);

        $tail = $set->tailSet(6);
        $this->assertEquals([6,7,8,9,10], $tail->toArray());

        $tail = $set->tailSet(1);
        $this->assertEquals([1,2,3,4,5,6,7,8,9,10], $tail->toArray());

        $head = $set->headSet(6);
        $this->assertEquals([1,2,3,4,5], $head->toArray());

        $head = $set->headSet(10);
        $this->assertEquals([1,2,3,4,5,6,7,8,9], $head->toArray());
    }

    /**
     * @test
     * @dataProvider number_ceiling_test_args
     */
    public function number_ceiling_test(mixed $query, $expected)
    {
        $number_set = new SortedSet([0, 9, 7, 5, 10, 970]);

        $ceiling = $number_set->ceiling($query);
        $this->assertTrue($ceiling === $expected);
    }

    private function number_ceiling_test_args()
    {
        return [
            [5, 5],
            [9.4, 10],
            [1000, null],
            [-1, 0]
        ];
    }

    /**
     * @test
     * @dataProvider string_ceiling_test_args
     */
    public function string_ceiling_test(mixed $query, $expected)
    {
        $set = new SortedSet(["0", "9", "7", "5", "10", "970"]);

        $ceiling = $set->ceiling($query);
        $this->assertTrue($ceiling === $expected);
    }

    private function string_ceiling_test_args()
    {
        return [
            ["5","5"],
            ["9.4", "10"],
            ["1000", null],
            ["-1", "0"]
        ];
    }

    /**
     * @test
     * @dataProvider number_floor_test_args
     */
    public function number_floor_test(mixed $query, $expected)
    {
        $number_set = new SortedSet([0, 9, 7, 5, 10, 970]);

        $floor = $number_set->floor($query);
        $this->assertTrue($floor === $expected);
    }

    private function number_floor_test_args()
    {
        return [
            [5, 5],
            [9.4, 9],
            [1000, 970],
            [-1, null]
        ];
    }

    /**
     * @test
     * @dataProvider string_floor_test_args
     */
    public function string_floor_test(mixed $query, $expected)
    {
        $set = new SortedSet(["0", "9", "7", "5", "10", "970"]);

        $floor = $set->floor($query);
        $this->assertTrue($floor === $expected);
    }

    private function string_floor_test_args()
    {
        return [
            ["5", "5"],
            ["9.4", "9"],
            ["1000", "970"],
            ["-1", null]
        ];
    }

    /**
    * @test
    */
    public function levenshtein_test() {
        $set = new Set([ 'apple', 'pineapple', 'banana', 'orange',
            'radish', 'carrot', 'pea', 'bean', 'potato']);

        $this->assertNull($set->levenshtein("zzzzzzzzzzzzzz"));
        $this->assertEquals('carrot', $set->levenshtein("carrrot"));
    }

}

?>