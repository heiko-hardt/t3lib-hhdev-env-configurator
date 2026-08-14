<?php

declare(strict_types=1);

namespace HeikoHardt\Typo3EnvConfigurator\Tests;

use HeikoHardt\Typo3EnvConfigurator\Configurator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Configurator::class)]
final class ConfiguratorTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $envVarsToClear = [];

    protected function tearDown(): void
    {
        foreach ($this->envVarsToClear as $envVar) {
            putenv($envVar);
        }
        $this->envVarsToClear = [];
    }

    #[Test]
    public function fromEnvironmentIgnoresEnvVarsWithoutTypo3Prefix(): void
    {
        $this->setEnv('SOME_OTHER_VAR', 'value');

        $result = Configurator::fromEnvironment();

        self::assertArrayNotHasKey('SOME_OTHER_VAR', $result);
    }

    #[Test]
    public function fromEnvironmentMapsUntypedValueAsString(): void
    {
        $this->setEnv('TYPO3__DB__Connections__Default__host', 'db.example.com');

        $result = Configurator::fromEnvironment();

        self::assertSame('db.example.com', $result['DB']['Connections']['Default']['host']);
    }

    #[Test]
    public function fromEnvironmentMapsIntTypedValue(): void
    {
        $this->setEnv('TYPO3__DB__Connections__Default__port', 'INT:3306');

        $result = Configurator::fromEnvironment();

        self::assertSame(3306, $result['DB']['Connections']['Default']['port']);
    }

    #[Test]
    public function fromEnvironmentMapsFloatTypedValue(): void
    {
        $this->setEnv('TYPO3__EXTENSIONS__Example__precision', 'FLOAT:0.5');

        $result = Configurator::fromEnvironment();

        self::assertSame(0.5, $result['EXTENSIONS']['Example']['precision']);
    }

    #[Test]
    public function fromEnvironmentMapsBoolTypedValue(): void
    {
        $this->setEnv('TYPO3__SYS__devIPmask', 'BOOL:false');

        $result = Configurator::fromEnvironment();

        self::assertFalse($result['SYS']['devIPmask']);
    }

    #[Test]
    public function fromEnvironmentMapsArrayTypedValue(): void
    {
        $this->setEnv('TYPO3__SYS__trustedHostsPattern', 'ARRAY:foo.tld, bar.tld');

        $result = Configurator::fromEnvironment();

        self::assertSame(['foo.tld', 'bar.tld'], $result['SYS']['trustedHostsPattern']);
    }

    #[Test]
    public function fromEnvironmentMapsJsonTypedValue(): void
    {
        $this->setEnv('TYPO3__SYS__foo', 'JSON:{"a":1,"b":[2,3]}');

        $result = Configurator::fromEnvironment();

        self::assertSame(['a' => 1, 'b' => [2, 3]], $result['SYS']['foo']);
    }

    #[Test]
    public function fromEnvironmentMapsNullTypedValue(): void
    {
        $this->setEnv('TYPO3__SYS__foo', 'NULL:');

        $result = Configurator::fromEnvironment();

        self::assertNull($result['SYS']['foo']);
    }

    #[Test]
    public function fromEnvironmentMergesMultipleVarsRecursively(): void
    {
        $this->setEnv('TYPO3__DB__Connections__Default__host', 'localhost');
        $this->setEnv('TYPO3__DB__Connections__Default__port', 'INT:3306');

        $result = Configurator::fromEnvironment();

        self::assertSame(
            ['host' => 'localhost', 'port' => 3306],
            $result['DB']['Connections']['Default']
        );
    }

    #[Test]
    public function fromEnvironmentKeepsValueAsStringForUnknownTypePrefix(): void
    {
        $this->setEnv('TYPO3__SYS__foo', 'UNKNOWNTYPE:bar');

        $result = Configurator::fromEnvironment();

        self::assertSame('UNKNOWNTYPE:bar', $result['SYS']['foo']);
    }

    #[Test]
    public function fromEnvironmentTriggersWarningAndSkipsVarOnInvalidJson(): void
    {
        // Uses a section name that cannot collide with real TYPO3__* env vars
        // already present in the process (e.g. TYPO3__SYS__* from the host setup),
        // so the assertion below isn't contaminated by ambient environment state.
        $this->setEnv('TYPO3__ZZTESTONLY__foo', 'JSON:{invalid');

        set_error_handler(static function (int $errno, string $errstr): bool {
            self::assertStringContainsString('TYPO3__ZZTESTONLY__foo', $errstr);

            return true;
        }, E_USER_WARNING);

        try {
            $result = Configurator::fromEnvironment();
        } finally {
            restore_error_handler();
        }

        self::assertArrayNotHasKey('ZZTESTONLY', $result);
    }

    private function setEnv(string $key, string $value): void
    {
        putenv(sprintf('%s=%s', $key, $value));
        $this->envVarsToClear[] = $key;
    }
}
