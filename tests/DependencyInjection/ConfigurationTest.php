<?php

declare(strict_types=1);

namespace Siganushka\ApiFactoryBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Siganushka\ApiFactory\AbstractConfiguration;
use Siganushka\ApiFactoryBundle\DependencyInjection\Configuration;
use Siganushka\ApiFactoryBundle\Tests\Fixtures\TestConfiguration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    private ?Processor $processor = null;
    private ?Configuration $configuration = null;

    protected function setUp(): void
    {
        $packages = [
            'vendor/test' => TestConfiguration::class,
        ];

        $this->processor = new Processor();
        $this->configuration = new Configuration($packages);
    }

    protected function tearDown(): void
    {
        $this->processor = null;
    }

    public function testAll(): void
    {
        static::assertEquals([
            'test' => [
                'enabled' => false,
                'configurations' => [],
            ],
        ], $this->processor->processConfiguration($this->configuration, []));

        static::assertEquals([
            'test' => [
                'enabled' => true,
                'default_configuration' => 'default',
                'configurations' => [
                    'default' => [
                        'foo' => 'hello',
                        'bar' => 123,
                    ],
                ],
            ],
        ], $this->processor->processConfiguration($this->configuration, [
            [
                'test' => [
                    'foo' => 'hello',
                ],
            ],
        ]));

        static::assertEquals([
            'test' => [
                'enabled' => true,
                'default_configuration' => 'test2',
                'configurations' => [
                    'test1' => [
                        'foo' => 'test1_foo',
                        'bar' => 1024,
                        'baz' => true,
                    ],
                    'test2' => [
                        'foo' => 'test2_foo',
                        'bar' => 2048,
                        'baz' => false,
                    ],
                ],
            ],
        ], $this->processor->processConfiguration($this->configuration, [
            [
                'test' => [
                    'default_configuration' => 'test2',
                    'configurations' => [
                        'test1' => [
                            'foo' => 'test1_foo',
                            'bar' => 1024,
                            'baz' => true,
                        ],
                        'test2' => [
                            'foo' => 'test2_foo',
                            'bar' => 2048,
                            'baz' => false,
                        ],
                    ],
                ],
            ],
        ]));
    }

    public function testNormalizePackageAlias(): void
    {
        static::assertSame('test', Configuration::normalizePackageAlias('test-api'));
        static::assertSame('foo', Configuration::normalizePackageAlias('vendor/foo'));
        static::assertSame('baz', Configuration::normalizePackageAlias('foo/bar/Baz'));
        static::assertSame('test_bundle', Configuration::normalizePackageAlias('test-bundle'));
        static::assertSame('test_bundle', Configuration::normalizePackageAlias('test/Test-Bundle-Api'));
    }

    public function testUnrecognizedOptions(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "test2" under "siganushka_api_factory". Did you mean "test"');

        $this->processor->processConfiguration($this->configuration, [
            [
                'test2' => [],
            ],
        ]);
    }

    public function testMissingOptions(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "siganushka_api_factory.test" (The required option "foo" is missing.)');

        $this->processor->processConfiguration($this->configuration, [
            [
                'test' => [
                    'enabled' => true,
                ],
            ],
        ]);
    }

    public function testDefaultConfigurationInvalid(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The specified "siganushka_api_factory.test.default_configuration" with value "invalid_default_configuration" is invalid. Available config are "test1", "test2"');

        $this->processor->processConfiguration($this->configuration, [
            [
                'test' => [
                    'default_configuration' => 'invalid_default_configuration',
                    'configurations' => [
                        'test1' => [
                            'foo' => 'test1_foo',
                            'bar' => 1024,
                            'baz' => true,
                        ],
                        'test2' => [
                            'foo' => 'test2_foo',
                            'bar' => 2048,
                            'baz' => false,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testConfigurationClassNotExists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The configuration class "NonExistingConfigurationClass" does not exists');

        $packages = [
            'vendor/test' => 'NonExistingConfigurationClass',
        ];

        $configuration = new Configuration($packages);
        $this->processor->processConfiguration($configuration, []);
    }

    public function testConfigurationClassInvalid(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(sprintf('Expected argument of type "%s", "stdClass" given', AbstractConfiguration::class));

        $packages = [
            'vendor/test' => \stdClass::class,
        ];

        $configuration = new Configuration($packages);
        $this->processor->processConfiguration($configuration, []);
    }
}
