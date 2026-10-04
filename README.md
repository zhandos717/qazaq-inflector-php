# Qazaq Inflector for PHP

[![Tests](https://github.com/zhandos717/qazaq-inflector-php/actions/workflows/tests.yml/badge.svg)](https://github.com/zhandos717/qazaq-inflector-php/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/zhandos717/qazaq-inflector.svg)](https://packagist.org/packages/zhandos717/qazaq-inflector)
[![PHP](https://img.shields.io/badge/php-8.1%2B-blue)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Dependency-free PHP library for declining Kazakh first names, full names and personal pronouns: seven cases, possessive forms, possessive phrases and predicate endings.

A port of the Python package [qazaq_inflector](https://github.com/zhandos717/qazaq_inflector). The test suite checks that both versions return identical forms.

## Installation

```bash
composer require zhandos717/qazaq-inflector
```

Requires PHP 8.1+ and `ext-mbstring`.

## Quick start

```php
use Zhandos\QazaqInflector\QazaqNameInflector as Inflector;

$inflector = new Inflector();

$inflector->inflect('Нұрлан', Inflector::GENITIVE);        // Нұрланның
$inflector->inflect('Нұрлан', Inflector::ABLATIVE);        // Нұрланнан
$inflector->inflect('Абай Құнанбаев', Inflector::DATIVE);  // Абайға Құнанбаевқа
$inflector->inflect('Ахметова', Inflector::DATIVE);        // Ахметоваға
$inflector->inflect('Мен', Inflector::ABLATIVE);           // Менен

$inflector->pluralize('Бақыт');                            // Бақыттар

$inflector->possessive('Арна', Inflector::PERSON_1SG, Inflector::DATIVE);  // Арнама
$inflector->possessive('Нұрлан', Inflector::PERSON_3, Inflector::GENITIVE); // Нұрланының
$inflector->possessive('Арна', Inflector::PERSON_1SG, plural: true);       // Арналарым

$inflector->genitivePhrase('Нұрлан', 'әке');                     // Нұрланның әкесі
$inflector->genitivePhrase('Нұрлан', 'әке', Inflector::DATIVE);  // Нұрланның әкесіне
$inflector->genitivePhrase('мен', 'кітап');                      // менің кітабым

$inflector->predicate('студент', Inflector::PERSON_1SG);  // студентпін
$inflector->predicate('Нұрлан', Inflector::PERSON_2SG);   // Нұрлансың

(new Inflector(strict: true))->inflect('Нұрлан', 'dativ'); // InvalidArgumentException
```

## API

| Method | Description |
|---|---|
| `inflect(?string $name, string $case): ?string` | Declines a name, full name or pronoun. Patronymics ending in `ұлы`/`қызы` stay unchanged; in a hyphenated double name only the last part changes. |
| `pluralize(string $name): string` | `-лар/-лер`, `-дар/-дер`, `-тар/-тер`. |
| `declension(string $name): array` | `[case => [singular, plural]]` for all seven cases. |
| `possessive(string $name, string $person = '3', string $case = 'nominative', bool $plural = false): string` | Possessive form in a case. |
| `genitivePhrase(string $owner, string $thing, string $case = 'nominative', bool $plural = false): string` | Owner in genitive + thing with the matching possessive suffix. A pronoun owner sets the person. |
| `predicate(string $word, string $person): string` | Personal predicate ending (жіктік жалғау). Person `3` adds nothing. |

**Cases:** `Inflector::NOMINATIVE`, `GENITIVE`, `DATIVE`, `ACCUSATIVE`, `LOCATIVE`, `ABLATIVE`, `INSTRUMENTAL`, or the same lowercase strings.

**Persons:** `PERSON_1SG` (мен), `PERSON_2SG` (сен), `PERSON_2SG_FORMAL` (сіз), `PERSON_1PL` (біз), `PERSON_2PL` (сендер), `PERSON_2PL_FORMAL` (сіздер), `PERSON_3` (ол/олар).

By default an unknown case or person returns the input unchanged; `strict: true` throws `InvalidArgumentException`.

## Laravel

No service provider is needed: the class has no dependencies, so the container resolves it directly.

```php
public function __construct(private QazaqNameInflector $inflector) {}
```

## Development

```bash
composer install
composer test
composer analyse
```

`tests/fixtures/python_reference.json` is generated from the Python package by `tests/fixtures/generate.py`; regenerate it after grammar changes in either version.

## License

[MIT](LICENSE) © Zhandos Zhandarbekov
