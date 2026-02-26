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
 * Foundation, Inc., 31 Milk St # 960789 Boston, MA 02196 USA.
 *
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoProctoring\model\helper;

use InvalidArgumentException;

/**
 * Trait to normalize SQL named parameters and optionally validate or fill missing ones.
 */
trait SqlParameterBindingTrait
{
    /**
     * Ensure every named parameter in the SQL has a bound value and keys match
     * DBAL expectation (name without colon). Avoids "Named parameter X does not have a bound value".
     *
     * @param string $sql
     * @param array $params
     * @param bool $fillMissingWithNull If true, missing parameters are set to null; if false, throws on missing.
     * @return array<string, mixed>
     * @throws InvalidArgumentException When a named parameter is missing and $fillMissingWithNull is false.
     */
    protected function bindMissingNamedParameters(string $sql, array $params, bool $fillMissingWithNull = false): array
    {
        // Matches named placeholders :name (not :: cast). Note: does not skip quoted strings or
        // SQL comments—e.g. ':x' inside a string literal would still be treated as a placeholder.
        if (preg_match_all('/(?<!:):(\w+)/', $sql, $matches)) {
            $missing = [];
            foreach (array_unique($matches[1]) as $name) {
                $keyWithColon = ':' . $name;
                if (!array_key_exists($keyWithColon, $params) && !array_key_exists($name, $params)) {
                    if ($fillMissingWithNull) {
                        $params[$name] = null;
                    } else {
                        $missing[] = $name;
                    }
                }
            }
            if ($missing !== []) {
                $snippet = strlen($sql) > 200 ? substr($sql, 0, 200) . '...' : $sql;
                throw new InvalidArgumentException(
                    'Missing named parameter(s) for SQL: ' . implode(', ', $missing) . '. SQL snippet: ' . $snippet
                );
            }
        }
        $normalized = [];
        foreach ($params as $key => $value) {
            $keyStr = (string) $key;
            $normalized[strpos($keyStr, ':') === 0 ? substr($keyStr, 1) : $key] = $value;
        }
        return $normalized;
    }
}
