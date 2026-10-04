<?php

declare(strict_types=1);

namespace Zhandos\QazaqInflector\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zhandos\QazaqInflector\QazaqNameInflector;

/**
 * Сверка с эталоном из Python-версии (tests/fixtures/generate.py).
 */
final class PythonReferenceTest extends TestCase
{
    private static ?array $reference = null;

    private static function reference(string $section): array
    {
        self::$reference ??= json_decode(
            (string) file_get_contents(__DIR__ . '/fixtures/python_reference.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return self::$reference[$section];
    }

    public static function inflectCases(): iterable
    {
        foreach (self::reference('inflect') as [$word, $case, $expected]) {
            yield "{$word} {$case}" => [$word, $case, $expected];
        }
    }

    public static function pluralizeCases(): iterable
    {
        foreach (self::reference('pluralize') as [$word, $expected]) {
            yield $word => [$word, $expected];
        }
    }

    public static function possessiveCases(): iterable
    {
        foreach (self::reference('possessive') as [$word, $person, $case, $plural, $expected]) {
            yield "{$word} {$person} {$case} " . ($plural ? 'pl' : 'sg') => [$word, $person, $case, $plural, $expected];
        }
    }

    public static function genitivePhraseCases(): iterable
    {
        foreach (self::reference('genitive_phrase') as [$owner, $thing, $case, $plural, $expected]) {
            yield "{$owner} {$thing} {$case} " . ($plural ? 'pl' : 'sg') => [$owner, $thing, $case, $plural, $expected];
        }
    }

    public static function predicateCases(): iterable
    {
        foreach (self::reference('predicate') as [$word, $person, $expected]) {
            yield "{$word} {$person}" => [$word, $person, $expected];
        }
    }

    #[DataProvider('inflectCases')]
    public function testInflect(string $word, string $case, string $expected): void
    {
        self::assertSame($expected, (new QazaqNameInflector())->inflect($word, $case));
    }

    #[DataProvider('pluralizeCases')]
    public function testPluralize(string $word, string $expected): void
    {
        self::assertSame($expected, (new QazaqNameInflector())->pluralize($word));
    }

    #[DataProvider('possessiveCases')]
    public function testPossessive(string $word, string $person, string $case, bool $plural, string $expected): void
    {
        self::assertSame($expected, (new QazaqNameInflector())->possessive($word, $person, $case, $plural));
    }

    #[DataProvider('genitivePhraseCases')]
    public function testGenitivePhrase(string $owner, string $thing, string $case, bool $plural, string $expected): void
    {
        self::assertSame($expected, (new QazaqNameInflector())->genitivePhrase($owner, $thing, $case, $plural));
    }

    #[DataProvider('predicateCases')]
    public function testPredicate(string $word, string $person, string $expected): void
    {
        self::assertSame($expected, (new QazaqNameInflector())->predicate($word, $person));
    }
}
