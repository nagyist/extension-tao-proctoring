<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoProctoring\test\unit\model\helper;

use InvalidArgumentException;
use oat\generis\test\TestCase;
use oat\taoProctoring\model\helper\SqlParameterBindingTrait;

/**
 * Unit tests for SqlParameterBindingTrait (parameter-checking flow and regex behaviour).
 */
class SqlParameterBindingTraitTest extends TestCase
{
    /** @var SqlParameterBindingTraitTestDouble */
    private $subject;

    /** @var \ReflectionMethod */
    private $bindMethod;

    protected function setUp(): void
    {
        $this->subject = new SqlParameterBindingTraitTestDouble();
        $this->bindMethod = new \ReflectionMethod($this->subject, 'bindMissingNamedParameters');
        $this->bindMethod->setAccessible(true);
    }

    private function bind(string $sql, array $params, bool $fillMissingWithNull = false): array
    {
        return $this->bindMethod->invoke($this->subject, $sql, $params, $fillMissingWithNull);
    }

    public function testNamedParamWithColonKeyReturnsNormalized(): void
    {
        $sql = 'SELECT * FROM t WHERE id = :id';
        $params = [':id' => 1];
        $result = $this->bind($sql, $params, false);
        $this->assertSame(['id' => 1], $result);
    }

    public function testNamedParamWithNormalizedKeyReturnsNormalized(): void
    {
        $sql = 'SELECT * FROM t WHERE id = :id';
        $params = ['id' => 1];
        $result = $this->bind($sql, $params, false);
        $this->assertSame(['id' => 1], $result);
    }

    public function testDuplicateParamNamesInSqlOnlyRequireOneBinding(): void
    {
        $sql = 'UPDATE t SET a = :x WHERE b = :x';
        $params = ['x' => 10];
        $result = $this->bind($sql, $params, false);
        $this->assertSame(['x' => 10], $result);
    }

    public function testPostgresqlDoubleColonCastNotTreatedAsParam(): void
    {
        $sql = 'SELECT :payload::jsonb';
        $params = ['payload' => '{}'];
        $result = $this->bind($sql, $params, false);
        $this->assertSame(['payload' => '{}'], $result);
    }

    public function testPostgresqlCastWithNoNamedParamReturnsNormalizedOnly(): void
    {
        $sql = 'SELECT 1::integer';
        $params = [];
        $result = $this->bind($sql, $params, false);
        $this->assertSame([], $result);
    }

    public function testMissingParameterThrowsWhenFillMissingWithNullFalse(): void
    {
        $sql = 'SELECT * FROM t WHERE id = :id';
        $params = [];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing named parameter(s) for SQL: id');
        $this->expectExceptionMessage('SQL snippet:');
        $this->bind($sql, $params, false);
    }

    public function testExceptionMessageContainsSqlSnippet(): void
    {
        $sql = 'SELECT * FROM t WHERE id = :id AND name = :name';
        $params = ['id' => 1];
        try {
            $this->bind($sql, $params, false);
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('name', $e->getMessage());
            $this->assertStringContainsString('SQL snippet:', $e->getMessage());
            $this->assertStringContainsString('SELECT * FROM t WHERE id = :id', $e->getMessage());
        }
    }

    public function testLongSqlSnippetTruncatedInException(): void
    {
        $longSql = 'SELECT ' . str_repeat('x', 300) . ' WHERE id = :id';
        $params = [];
        try {
            $this->bind($longSql, $params, false);
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('SQL snippet:', $e->getMessage());
            $this->assertStringContainsString('...', $e->getMessage());
        }
    }

    public function testMissingParameterFilledWithNullWhenFillMissingWithNullTrue(): void
    {
        $sql = 'SELECT * FROM t WHERE id = :id';
        $params = [];
        $result = $this->bind($sql, $params, true);
        $this->assertSame(['id' => null], $result);
    }

    public function testMultipleMissingFilledWithNullWhenFillMissingWithNullTrue(): void
    {
        $sql = 'SELECT * FROM t WHERE a = :a AND b = :b';
        $params = [];
        $result = $this->bind($sql, $params, true);
        $this->assertSame(['a' => null, 'b' => null], $result);
    }

    public function testNoNamedParamsInSqlReturnsNormalizedParamsOnly(): void
    {
        $sql = 'SELECT 1';
        $params = [':foo' => 'bar'];
        $result = $this->bind($sql, $params, false);
        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function testEmptyParamsAndNoPlaceholdersReturnsEmptyArray(): void
    {
        $sql = 'SELECT 1';
        $params = [];
        $result = $this->bind($sql, $params, false);
        $this->assertSame([], $result);
    }
}

/** Test double that uses the trait so the real parameter-checking flow is exercised. */
class SqlParameterBindingTraitTestDouble
{
    use SqlParameterBindingTrait;
}
