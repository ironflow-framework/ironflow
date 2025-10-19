<?php

declare(strict_types=1);

namespace IronFlow\Versioning;

class VersionManager
{
    /**
     * Check if version satisfies constraint
     * Supports: ^, ~, >=, <=, >, <, *, ||
     */
    public function satisfies(string $version, string $constraint): bool
    {
        // Handle OR conditions
        if (str_contains($constraint, '||')) {
            $constraints = array_map('trim', explode('||', $constraint));
            foreach ($constraints as $c) {
                if ($this->satisfies($version, $c)) {
                    return true;
                }
            }
            return false;
        }

        // Handle AND conditions (space separated)
        if (preg_match('/\s+/', trim($constraint))) {
            $constraints = array_filter(explode(' ', $constraint));
            foreach ($constraints as $c) {
                if (!$this->satisfies($version, $c)) {
                    return false;
                }
            }
            return true;
        }

        // Wildcard
        if ($constraint === '*') {
            return true;
        }

        // Caret (^) - Allow changes that do not modify left-most non-zero digit
        if (str_starts_with($constraint, '^')) {
            return $this->satisfiesCaret($version, substr($constraint, 1));
        }

        // Tilde (~) - Allow patch-level changes
        if (str_starts_with($constraint, '~')) {
            return $this->satisfiesTilde($version, substr($constraint, 1));
        }

        // Comparison operators
        if (preg_match('/^(>=|<=|>|<|=)(.+)$/', $constraint, $matches)) {
            $operator = $matches[1];
            $constraintVersion = $matches[2];

            return $this->compare($version, $constraintVersion, $operator);
        }

        // Exact match
        return version_compare($version, $constraint, '=');
    }

    /**
     * Caret constraint (^1.2.3 allows >=1.2.3 <2.0.0)
     */
    protected function satisfiesCaret(string $version, string $constraint): bool
    {
        $parts = explode('.', $constraint);
        $major = (int)($parts[0] ?? 0);
        $minor = (int)($parts[1] ?? 0);

        if ($major > 0) {
            // ^1.2.3 := >=1.2.3 <2.0.0
            return $this->satisfies($version, ">={$constraint}")
                && $this->satisfies($version, '<' . ($major + 1) . '.0.0');
        } elseif ($minor > 0) {
            // ^0.2.3 := >=0.2.3 <0.3.0
            return $this->satisfies($version, ">={$constraint}")
                && $this->satisfies($version, "<0." . ($minor + 1) . '.0');
        } else {
            // ^0.0.3 := >=0.0.3 <0.0.4
            $patch = (int)($parts[2] ?? 0);
            return $this->satisfies($version, ">={$constraint}")
                && $this->satisfies($version, "<0.0." . ($patch + 1));
        }
    }

    /**
     * Tilde constraint (~1.2.3 allows >=1.2.3 <1.3.0)
     */
    protected function satisfiesTilde(string $version, string $constraint): bool
    {
        $parts = explode('.', $constraint);
        $major = (int)($parts[0] ?? 0);
        $minor = (int)($parts[1] ?? 0);

        // ~1.2.3 := >=1.2.3 <1.3.0
        return $this->satisfies($version, ">={$constraint}")
            && $this->satisfies($version, '<' . $major . '.' . ($minor + 1) . '.0');
    }

    /**
     * Compare versions with operator
     */
    protected function compare(string $version1, string $version2, string $operator): bool
    {
        return version_compare($version1, $version2, $operator);
    }

    /**
     * Parse version into components
     */
    public function parse(string $version): array
    {
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)(?:-([a-zA-Z0-9\-]+))?(?:\+([a-zA-Z0-9\-]+))?$/', $version, $matches)) {
            throw new \InvalidArgumentException("Invalid version format: {$version}");
        }

        return [
            'major' => (int)$matches[1],
            'minor' => (int)$matches[2],
            'patch' => (int)$matches[3],
            'prerelease' => $matches[4] ?? null,
            'build' => $matches[5] ?? null,
            'full' => $version,
        ];
    }

    /**
     * Get next version based on bump type
     */
    public function bump(string $version, string $type = 'patch'): string
    {
        $parsed = $this->parse($version);

        switch ($type) {
            case 'major':
                return ($parsed['major'] + 1) . '.0.0';

            case 'minor':
                return $parsed['major'] . '.' . ($parsed['minor'] + 1) . '.0';

            case 'patch':
            default:
                return $parsed['major'] . '.' . $parsed['minor'] . '.' . ($parsed['patch'] + 1);
        }
    }

    /**
     * Check if version is stable (no prerelease)
     */
    public function isStable(string $version): bool
    {
        $parsed = $this->parse($version);
        return $parsed['prerelease'] === null;
    }
}
