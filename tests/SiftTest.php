<?php

namespace Sift\Test;

use Sift\Sift;

class SiftTest extends \PHPUnit\Framework\TestCase
{
    /** @var Sift */
    private $sift;

    private $data;

    protected function setUp()
    {
        $this->data = [
            [
                'name' => 'awesome',
                'age' => 19,
                'groups' => ['foo', 'bar'],
                'gender' => 'male',
            ],
            [
                'name' => 'beauty',
                'age' => 20,
                'groups' => ['foo'],
                'gender' => 'female',
            ],
            [
                'name' => 'awesome',
                'age' => 25,
                'groups' => ['bar'],
                'gender' => 'male',
            ],
            [
                'name' => 'handsome',
                'age' => 17,
                'gender' => 'male',
            ],
            [
                'name' => 'amazing',
                'age' => 29,
                'groups' => ['foobar'],
                'gender' => 'female',
            ],
        ];

        $this->sift = new Sift($this->data);
    }

    public function testTrueQueryShouldReturnAll()
    {
        $result = $this->sift->query([true]);
        $this->assertCount(count($this->data), $result);
    }

    public function testEmptyQueryShouldReturnAll()
    {
        $result = $this->sift->query([]);
        $this->assertCount(count($this->data), $result);
    }

    public function testEqualQuery()
    {
        $result = $this->sift->query([
            ['gender' => ['$eq' => 'male']]
        ]);
        $this->assertCount(3, $result, '[[field: [$eq: value]]]');

        $result = $this->sift->query([
            ['gender' => 'male']
        ]);
        $this->assertCount(3, $result, '[[field: value]]');

        $result = $this->sift->query([
            'gender' => 'male'
        ]);
        $this->assertCount(3, $result, '[field: value]');

        $result = $this->sift->query([
            'gender' => 'false-value'
        ]);
        $this->assertCount(0, $result, '[field: no_match_value]');
    }

    public function testNotEqualQuery()
    {
        $result = $this->sift->query([
            ['gender' => ['$ne' => 'male']]
        ]);
        $this->assertCount(2, $result, '[[field: [$ne: value]]]');
    }

    public function testInArrayQuery()
    {
        $result1 = $this->sift->query([
            ['gender' => ['$in' => ['male']]]
        ]);
        $this->assertCount(3, $result1);

        $result2 = $this->sift->query([
            ['gender' => ['$in' => ['female']]]
        ]);

        $genderCount = [];
        $gender = 'female';
        foreach ($result2 as $item) {
            if ($item['gender'] === $gender) {
                $genderCount[] = true;
            }
        }
        $this->assertCount(2, $genderCount);

        $result3 = $this->sift->query([
            ['gender' => ['$in' => ['']]]
        ]);
        $this->assertCount(0, $result3);
    }

    public function testNotInArrayQuery()
    {
        $result1 = $this->sift->query([
            ['gender' => ['$nin' => ['male']]]
        ]);
        $this->assertCount(2, $result1);

        $result2 = $this->sift->query([
            ['gender' => ['$nin' => ['female']]]
        ]);

        $genderCount = [];
        $gender = 'male';
        foreach ($result2 as $item) {
            if ($item['gender'] === $gender) {
                $genderCount[] = true;
            }
        }
        $this->assertCount(3, $genderCount);

        $result3 = $this->sift->query([
            ['gender' => ['$nin' => ['male', 'female']]]
        ]);
        $this->assertCount(0, $result3);
    }

    public function testRegExQuery()
    {
        $result1 = $this->sift->query([
            ['gender' => ['$regex' => '/male$/']]
        ]);
        $this->assertCount(5, $result1);

        $result2 = $this->sift->query([
            'gender' => ['$regex' => '/^male/']
        ]);
        $this->assertCount(3, $result2);

        $result3 = $this->sift->query([
            'gender' => ['$regex' => '/ma/']
        ]);
        $this->assertCount(5, $result3);
    }

    public function testExistsQuery()
    {
        $result1 = $this->sift->query([
            ['groups' => ['$exists' => true]]
        ]);
        $this->assertCount(4, $result1);

        $result2 = $this->sift->query([
            ['groups' => ['$exists' => false]]
        ]);
        $this->assertCount(1, $result2);
    }

    public function testAndQuery()
    {
        $result = $this->sift->query([
            ['groups' => ['$exists' => true]],
            ['name' => ['$eq' => 'awesome']],
        ]);
        $this->assertCount(2, $result);

        $result = $this->sift->query([
            ['groups' => ['$exists' => false]],
            ['name' => ['$eq' => 'handsome']],
        ]);
        $this->assertCount(1, $result);

        $result = $this->sift->query([
            ['groups' => ['$exists' => true]],
            ['name' => ['$eq' => 'handsome']],
        ]);
        $this->assertCount(0, $result);
    }

    public function testOrQuery()
    {
        $result = $this->sift->query([
            '$or' => [
                ['groups' => ['$exists' => true]],
                ['name' => ['$eq' => 'awesome']],
            ]
        ]);
        $this->assertCount(4, $result);

        $result = $this->sift->query([
            '$or' => [
                ['groups' => ['$exists' => false]],
                ['name' => ['$eq' => 'handsome']],
            ]
        ]);
        $this->assertCount(1, $result);

        $result = $this->sift->query([
            '$or' => [
                ['groups' => ['$exists' => true]],
                ['name' => ['$eq' => 'handsome']],
            ]
        ]);
        $this->assertCount(5, $result);

        $result = $this->sift->query([
            '$or' => [
                ['groups' => [ '$size' => 2 ]],
                ['groups' => [ '$size' => 0 ]],
            ]
        ]);

        $this->assertCount(2, $result, '$or [$size 0, $size 1]');
    }

    public function testNotOrQuery()
    {
        $result = $this->sift->query([
            '$nor' => [
                ['groups' => ['$exists' => true]],
                ['name' => ['$eq' => 'awesome']],
            ]
        ]);
        $this->assertCount(1, $result);

        $result = $this->sift->query([
            '$nor' => [
                ['groups' => ['$exists' => false]],
                ['name' => ['$eq' => 'handsome']],
            ]
        ]);
        $this->assertCount(4, $result);

        $result = $this->sift->query([
            '$nor' => [
                ['groups' => ['$exists' => true]],
                ['name' => ['$eq' => 'handsome']],
            ]
        ]);
        $this->assertCount(0, $result);
    }

    public function testNotQuery()
    {
        $result = $this->sift->query([
            ['name' => ['$not' => 'awesome']],
        ]);
        $this->assertCount(3, $result);

        $result = $this->sift->query([
            ['name' => ['$not' => 'handsome']],
        ]);
        $this->assertCount(4, $result);
    }

    public function testGreaterThanQuery()
    {
        $result = $this->sift->query([
            ['age' => ['$gt' => 19]],
        ]);
        $this->assertCount(3, $result, '$gt 20');

        $result = $this->sift->query([
            ['age' => ['$gt' => 25]],
        ]);
        $this->assertCount(1, $result, '$gt 25');
    }

    public function testGreaterThanEqualQuery()
    {
        $result = $this->sift->query([
            ['age' => ['$gte' => 19]],
        ]);
        $this->assertCount(4, $result, '$gte 20');

        $result = $this->sift->query([
            ['age' => ['$gte' => 25]],
        ]);
        $this->assertCount(2, $result, '$gte 25');
    }

    public function testLesserThanQuery()
    {
        $result = $this->sift->query([
            ['age' => ['$lt' => 19]],
        ]);
        $this->assertCount(1, $result, '$lt 20');

        $result = $this->sift->query([
            ['age' => ['$lt' => 25]],
        ]);
        $this->assertCount(3, $result, '$lt 25');
    }

    public function testLesserThanEqualQuery()
    {
        $result = $this->sift->query([
            ['age' => ['$lte' => 19]],
        ]);
        $this->assertCount(2, $result, '$lte 20');

        $result = $this->sift->query([
            ['age' => ['$lte' => 25]],
        ]);
        $this->assertCount(4, $result, '$lte 25');
    }

    public function testSizeQuery()
    {
        $result = $this->sift->query([
            ['groups' => ['$size' => 2]],
        ]);
        $this->assertCount(1, $result, '$size 2');

        $result = $this->sift->query([
            ['groups' => ['$size' => 1]],
        ]);
        $this->assertCount(3, $result, '$size 1');

        $result = $this->sift->query([
            ['groups' => ['$size' => 0]],
        ]);
        $this->assertCount(1, $result, '$size 0');
    }

    public function testNestedQuery()
    {
        $result = $this->sift->query([
            '$not' => [
                ['groups' => ['$size' => 2]],
            ]
        ]);
        $this->assertCount(4, $result, '$not $size 2');

        $result = $this->sift->query([
            '$not' => [
                ['groups' => ['$size' => 1]],
            ]
        ]);
        $this->assertCount(2, $result, '$not $size 1');

        $result = $this->sift->query([
            '$not' => [
                ['groups' => ['$size' => 0]],
            ]
        ]);
        $this->assertCount(4, $result, '$not $size 0');
    }
}
